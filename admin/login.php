<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

if (getFuncionarioLogado()) {
    redirecionar('index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!validarTokenCSRF($csrfToken)) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } elseif (empty($email) || empty($senha)) {
        $erro = 'Informe e-mail e senha.';
    } else {
        $resultado = loginFuncionario($email, $senha);
        if ($resultado['sucesso']) {
            redirecionar('index.php');
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

$csrfToken = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel LocaFacil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1><i class="bi bi-shield-lock-fill"></i> Painel LocaFacil</h1>
        <p class="subtitulo">Acesso restrito a funcionarios.</p>

        <?php if ($erro): ?>
            <div class="flash erro"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="form-grupo">
                <label>E-mail</label>
                <input type="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-grupo">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>

        <div class="credenciais-demo">
            <strong>Contas de demonstracao</strong><br>
            Admin: admin@locafacil.com / Admin@123<br>
            Gerente (privilegios limitados): gerente@locafacil.com / Gerente@123
        </div>
    </div>
</body>
</html>
