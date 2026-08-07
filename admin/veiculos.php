<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
exigirPermissao('veiculos', 'ver');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    exigirPermissao('veiculos', 'deletar');

    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('veiculos.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = ?");
        $stmt->execute([$id]);
        flash('sucesso', 'Veiculo removido com sucesso.');
    } catch (PDOException $e) {
        flash('erro', 'Nao foi possivel remover: existem reservas vinculadas a este veiculo.');
    }
    redirecionar('veiculos.php');
}

$paginaAtual = 'veiculos';
$tituloPagina = 'Veiculos';
$subtituloPagina = 'Gerencie a frota disponivel para locacao';
$csrfToken = gerarTokenCSRF();

if (temPermissao('veiculos', 'criar')) {
    $acaoTopo = '<a href="veiculo_form.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo Veiculo</a>';
}

$veiculos = $pdo->query("
    SELECT v.*, c.nome AS categoria_nome
    FROM veiculos v
    JOIN categorias c ON v.categoriaId = c.id
    ORDER BY v.id DESC
")->fetchAll();

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="tabela-card">
    <?php if (empty($veiculos)): ?>
        <div class="sem-dados">Nenhum veiculo cadastrado ainda.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Veiculo</th>
                <th>Categoria</th>
                <th>Placa</th>
                <th>Ano</th>
                <th>Diaria</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($veiculos as $v): ?>
            <tr>
                <td><?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?></td>
                <td><?php echo htmlspecialchars($v['categoria_nome']); ?></td>
                <td><?php echo htmlspecialchars($v['placa']); ?></td>
                <td><?php echo (int) $v['ano']; ?></td>
                <td>R$ <?php echo number_format($v['diaria'], 2, ',', '.'); ?></td>
                <td><span class="badge <?php echo $v['status'] === 'disponivel' ? 'ativo' : 'inativo'; ?>"><?php echo htmlspecialchars($v['status']); ?></span></td>
                <td>
                    <div class="acoes-tabela">
                        <?php if (temPermissao('veiculos', 'editar')): ?>
                            <a href="veiculo_form.php?id=<?php echo $v['id']; ?>" class="btn-secondary btn-sm"><i class="bi bi-pencil-fill"></i> Editar</a>
                        <?php endif; ?>
                        <?php if (temPermissao('veiculos', 'deletar')): ?>
                            <form method="post" onsubmit="return confirm('Remover este veiculo? Esta acao nao pode ser desfeita.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                <button type="submit" class="btn-danger btn-sm"><i class="bi bi-trash-fill"></i> Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
