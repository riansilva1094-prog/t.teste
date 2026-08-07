<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

exigirPermissao('usuarios', 'ver');
exigirPermissao('funcionarios', 'criar');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    flash('erro', 'Cliente nao encontrado.');
    redirecionar('usuarios.php');
}

$stmt = $pdo->prepare("SELECT id FROM funcionarios WHERE email = ?");
$stmt->execute([$cliente['email']]);
$jaEhFuncionario = (bool) $stmt->fetch();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } elseif ($jaEhFuncionario) {
        $erro = 'Ja existe um funcionario com este e-mail.';
    } else {
        $cargo = $_POST['cargo'] ?? 'atendente';
        $cargosValidos = ['admin', 'gerente', 'atendente'];

        if (!in_array($cargo, $cargosValidos, true)) {
            $erro = 'Cargo invalido.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO funcionarios (nome, email, telefone, senhaHash, cargo, situacao)
                    VALUES (?, ?, ?, ?, ?, 'ativo')
                ");
                // Reaproveita o hash de senha do cliente: ele consegue entrar no painel
                // com a mesma senha que ja usa no site, sem precisar definir uma nova.
                $stmt->execute([$cliente['nome'], $cliente['email'], $cliente['telefone'], $cliente['senhaHash'], $cargo]);

                $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cliente['id'], 'promovido_a_funcionario', "Cliente promovido a funcionario (cargo: $cargo)", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

                flash('sucesso', "{$cliente['nome']} agora tambem e funcionario do painel (cargo: $cargo). A conta de cliente foi mantida.");
                redirecionar('funcionarios.php');
            } catch (PDOException $e) {
                $erro = str_contains($e->getMessage(), 'Duplicate') ? 'Ja existe um funcionario com este e-mail.' : 'Erro ao promover cliente.';
            }
        }
    }
}

$csrfToken = gerarTokenCSRF();
$paginaAtual = 'usuarios';
$tituloPagina = 'Promover Cliente a Funcionario';
$subtituloPagina = 'O cliente continua com acesso normal ao site; uma conta de funcionario separada sera criada';

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="form-card">
    <?php if ($erro): ?>
        <div class="flash erro"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if ($jaEhFuncionario): ?>
        <div class="flash erro"><i class="bi bi-exclamation-triangle-fill"></i> Ja existe um funcionario cadastrado com o e-mail <?php echo htmlspecialchars($cliente['email']); ?>.</div>
        <a href="usuarios.php" class="btn-secondary">Voltar</a>
    <?php else: ?>
        <p style="margin-bottom: 20px; color: var(--gray);">
            Voce esta prestes a criar uma conta de funcionario para <strong><?php echo htmlspecialchars($cliente['nome']); ?></strong>
            (<?php echo htmlspecialchars($cliente['email']); ?>). A senha atual do cliente sera reaproveitada, e a conta de cliente continua ativa normalmente.
        </p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
            <div class="form-grupo">
                <label>Cargo no painel</label>
                <select name="cargo">
                    <option value="atendente">Atendente</option>
                    <option value="gerente">Gerente</option>
                    <option value="admin">Admin (acesso total)</option>
                </select>
            </div>
            <div class="form-acoes">
                <button type="submit" class="btn-primary"><i class="bi bi-person-up"></i> Promover a Funcionario</button>
                <a href="usuarios.php" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
