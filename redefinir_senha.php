<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/csrf.php';

$token = $_GET['token'] ?? '';
$tokenValido = (bool) preg_match('/^[a-f0-9]{64}$/', $token);
$csrfToken = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - LocaFácil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/veiculos.css">
</head>
<body>
    <div class="pagina-recuperacao">
        <div class="recuperacao-box">
            <div class="logo">
                <img src="imagens/carro png.png" alt="LocaFácil" />
                <span>Loca<span>Fácil</span></span>
            </div>

            <?php if (!$tokenValido): ?>
                <div class="form-group">
                    <p><i class="bi bi-exclamation-triangle-fill"></i> Link invalido ou incompleto. Solicite uma nova recuperacao de senha na pagina inicial.</p>
                </div>
                <a href="index.php" class="btn-primary"><i class="bi bi-house-fill"></i> Voltar para a Home</a>
            <?php else: ?>
                <h2><i class="bi bi-key-fill"></i> Criar nova senha</h2>
                <p>Escolha uma nova senha para sua conta.</p>

                <form id="formRedefinirSenha" onsubmit="handleRedefinirSenha(event)">
                    <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" id="csrf_token_redefinir" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Nova senha</label>
                        <div class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="nova-senha" placeholder="Minimo 8 caracteres" required minlength="8" />
                        </div>
                        <small><i class="bi bi-info-circle-fill"></i> Minimo 8 caracteres, com letra maiuscula e numero.</small>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Confirmar nova senha</label>
                        <div class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="confirmar-senha" placeholder="Repita a nova senha" required minlength="8" />
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-check-circle-fill"></i> Redefinir senha</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/veiculos.js"></script>
</body>
</html>
