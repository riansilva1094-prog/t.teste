<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
exigirPermissao('funcionarios', 'ver');

$funcionarioLogado = getFuncionarioLogado();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    exigirPermissao('funcionarios', 'deletar');

    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('funcionarios.php');
    }

    $id = (int) ($_POST['id'] ?? 0);

    if ($id === (int) $funcionarioLogado['id']) {
        flash('erro', 'Voce nao pode excluir a sua propria conta.');
        redirecionar('funcionarios.php');
    }

    $stmt = $pdo->prepare("SELECT cargo, situacao FROM funcionarios WHERE id = ?");
    $stmt->execute([$id]);
    $alvo = $stmt->fetch();

    if ($alvo && $alvo['cargo'] === 'admin' && $alvo['situacao'] === 'ativo' && contarAdminsAtivos($id) === 0) {
        flash('erro', 'Nao e possivel excluir o unico administrador ativo do painel.');
        redirecionar('funcionarios.php');
    }

    $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ?");
    $stmt->execute([$id]);
    flash('sucesso', 'Funcionario removido com sucesso.');
    redirecionar('funcionarios.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'tornar_admin') {
    exigirPermissao('funcionarios', 'editar');

    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('funcionarios.php');
    }

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("UPDATE funcionarios SET cargo = 'admin' WHERE id = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM funcionario_permissoes WHERE funcionarioId = ?");
    $stmt->execute([$id]);

    if ($id === (int) $funcionarioLogado['id']) {
        $_SESSION['funcionario_cargo'] = 'admin';
    }

    flash('sucesso', 'Funcionario promovido a administrador.');
    redirecionar('funcionarios.php');
}

$paginaAtual = 'funcionarios';
$tituloPagina = 'Funcionarios';
$subtituloPagina = 'Equipe do painel e seus privilegios de acesso';
$csrfToken = gerarTokenCSRF();

if (temPermissao('funcionarios', 'criar')) {
    $acaoTopo = '<a href="funcionario_form.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo Funcionario</a>';
}

$funcionarios = $pdo->query("SELECT * FROM funcionarios ORDER BY nome")->fetchAll();

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="tabela-card">
    <?php if (empty($funcionarios)): ?>
        <div class="sem-dados">Nenhum funcionario cadastrado ainda.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Cargo</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($funcionarios as $f): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($f['nome']); ?>
                    <?php if ((int) $f['id'] === (int) $funcionarioLogado['id']): ?>
                        <span class="badge atendente">voce</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($f['email']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($f['cargo']); ?>"><?php echo htmlspecialchars($f['cargo']); ?></span></td>
                <td><span class="badge <?php echo $f['situacao'] === 'ativo' ? 'ativo' : 'inativo'; ?>"><?php echo htmlspecialchars($f['situacao']); ?></span></td>
                <td>
                    <div class="acoes-tabela">
                        <?php if (temPermissao('funcionarios', 'editar')): ?>
                            <a href="funcionario_form.php?id=<?php echo $f['id']; ?>" class="btn-secondary btn-sm"><i class="bi bi-pencil-fill"></i> Editar</a>
                            <?php if ($f['cargo'] !== 'admin'): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Tornar <?php echo htmlspecialchars(addslashes($f['nome'])); ?> administrador? Isso da acesso total ao painel.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="acao" value="tornar_admin">
                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" class="btn-secondary btn-sm" title="Tornar administrador"><i class="bi bi-shield-fill-up"></i> Tornar Admin</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (temPermissao('funcionarios', 'deletar') && (int) $f['id'] !== (int) $funcionarioLogado['id']): ?>
                            <form method="post" onsubmit="return confirm('Remover este funcionario?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
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
