<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
exigirPermissao('motoristas', 'ver');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    exigirPermissao('motoristas', 'deletar');

    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('motoristas.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM motoristas WHERE id = ?");
    $stmt->execute([$id]);
    flash('sucesso', 'Motorista removido com sucesso.');
    redirecionar('motoristas.php');
}

$paginaAtual = 'motoristas';
$tituloPagina = 'Motoristas';
$subtituloPagina = 'Cadastro da frota de motoristas da empresa';
$csrfToken = gerarTokenCSRF();

if (temPermissao('motoristas', 'criar')) {
    $acaoTopo = '<a href="motorista_form.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo Motorista</a>';
}

$motoristas = $pdo->query("SELECT * FROM motoristas ORDER BY nome")->fetchAll();

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="tabela-card">
    <?php if (empty($motoristas)): ?>
        <div class="sem-dados">Nenhum motorista cadastrado ainda.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CNH</th>
                <th>Categoria</th>
                <th>Validade CNH</th>
                <th>Telefone</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($motoristas as $m): ?>
            <tr>
                <td><?php echo htmlspecialchars($m['nome']); ?></td>
                <td><?php echo htmlspecialchars($m['cnh']); ?></td>
                <td><?php echo htmlspecialchars($m['cnh_categoria']); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($m['cnh_validade']))); ?></td>
                <td><?php echo htmlspecialchars($m['telefone']); ?></td>
                <td><span class="badge <?php echo $m['situacao'] === 'ativo' ? 'ativo' : 'inativo'; ?>"><?php echo htmlspecialchars($m['situacao']); ?></span></td>
                <td>
                    <div class="acoes-tabela">
                        <?php if (temPermissao('motoristas', 'editar')): ?>
                            <a href="motorista_form.php?id=<?php echo $m['id']; ?>" class="btn-secondary btn-sm"><i class="bi bi-pencil-fill"></i> Editar</a>
                        <?php endif; ?>
                        <?php if (temPermissao('motoristas', 'deletar')): ?>
                            <form method="post" onsubmit="return confirm('Remover este motorista?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
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
