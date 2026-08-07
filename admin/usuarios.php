<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
exigirPermissao('usuarios', 'ver');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    exigirPermissao('usuarios', 'deletar');

    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('usuarios.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    flash('sucesso', 'Usuario removido com sucesso (reservas vinculadas tambem foram removidas).');
    redirecionar('usuarios.php');
}

$paginaAtual = 'usuarios';
$tituloPagina = 'Usuarios';
$subtituloPagina = 'Clientes cadastrados na plataforma';
$csrfToken = gerarTokenCSRF();

if (temPermissao('usuarios', 'criar')) {
    $acaoTopo = '<a href="usuario_form.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo Usuario</a>';
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll();

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="tabela-card">
    <?php if (empty($usuarios)): ?>
        <div class="sem-dados">Nenhum usuario cadastrado ainda.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Nivel</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['telefone']); ?></td>
                <td><span class="badge <?php echo $u['tipo_pessoa']; ?>"><?php echo $u['tipo_pessoa'] === 'fisica' ? 'PF' : 'PJ'; ?></span></td>
                <td><span class="badge <?php echo $u['nivel_cliente']; ?>"><?php echo htmlspecialchars($u['nivel_cliente']); ?></span></td>
                <td><span class="badge <?php echo $u['situacao'] === 'ativo' ? 'ativo' : 'inativo'; ?>"><?php echo htmlspecialchars($u['situacao']); ?></span></td>
                <td>
                    <div class="acoes-tabela">
                        <?php if (temPermissao('usuarios', 'editar')): ?>
                            <a href="usuario_form.php?id=<?php echo $u['id']; ?>" class="btn-secondary btn-sm"><i class="bi bi-pencil-fill"></i> Editar</a>
                        <?php endif; ?>
                        <?php if (temPermissao('funcionarios', 'criar')): ?>
                            <a href="promover_funcionario.php?id=<?php echo $u['id']; ?>" class="btn-secondary btn-sm" title="Promover a funcionario"><i class="bi bi-person-up"></i> Promover</a>
                        <?php endif; ?>
                        <?php if (temPermissao('usuarios', 'deletar')): ?>
                            <form method="post" onsubmit="return confirm('Remover este usuario? Reservas vinculadas tambem serao removidas.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
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
