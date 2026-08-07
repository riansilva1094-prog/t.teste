<?php
require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/sanitize.php';

const MAX_TENTATIVAS_LOGIN = 5;
const BLOQUEIO_LOGIN_MINUTOS = 15;

function cadastrarUsuario($nome, $email, $telefone, $senha, $csrfToken) {
    global $pdo;
    
    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de segurança invalido."];
    }
    
    $nome = sanitizarEntrada($nome);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $telefone = sanitizarEntrada($telefone);
    
    if (empty($nome) || empty($email) || empty($telefone) || empty($senha)) {
        return ["sucesso" => false, "mensagem" => "Todos os campos sao obrigatorios."];
    }
    
    if (!validarEmail($email)) {
        return ["sucesso" => false, "mensagem" => "E-mail invalido."];
    }
    
    if (!validarTelefone($telefone)) {
        return ["sucesso" => false, "mensagem" => "Telefone invalido."];
    }
    
    if (!validarSenha($senha)) {
        return ["sucesso" => false, "mensagem" => "A senha deve ter pelo menos 8 caracteres, uma letra maiuscula e um numero."];
    }
    
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ["sucesso" => false, "mensagem" => "Este e-mail ja esta cadastrado."];
    }
    
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senhaHash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $telefone, $senhaHash]);
        
        $id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, 'cadastro', "Novo usuario cadastrado: $email", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        
        $pdo->commit();
        
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_telefone'] = $telefone;
        $_SESSION['usuario_nivel'] = 'comum';

        return ["sucesso" => true, "nome" => $nome];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro no cadastro: " . $e->getMessage());
        return ["sucesso" => false, "mensagem" => "Erro ao realizar cadastro. Tente novamente."];
    }
}

function loginUsuario($email, $senha, $csrfToken) {
    global $pdo;
    
    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de segurança invalido."];
    }
    
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!validarEmail($email)) {
        return ["sucesso" => false, "mensagem" => "E-mail invalido."];
    }
    
    $stmt = $pdo->prepare("
        SELECT id, nome, telefone, senhaHash, situacao, nivel_cliente, tentativas_login,
            (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS ainda_bloqueado
        FROM usuarios WHERE email = ?
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return ["sucesso" => false, "mensagem" => "E-mail ou senha incorretos."];
    }

    if ($usuario['situacao'] !== 'ativo') {
        return ["sucesso" => false, "mensagem" => "Conta inativa. Entre em contato com o suporte."];
    }

    if ($usuario['nivel_cliente'] === 'bloqueado') {
        return ["sucesso" => false, "mensagem" => "Esta conta foi bloqueada. Entre em contato com o suporte."];
    }

    if ((bool) $usuario['ainda_bloqueado']) {
        return ["sucesso" => false, "mensagem" => "Conta temporariamente bloqueada por multiplas tentativas incorretas. Tente novamente em alguns minutos."];
    }

    if (password_verify($senha, $usuario['senhaHash'])) {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_telefone'] = $usuario['telefone'];
        $_SESSION['usuario_nivel'] = $usuario['nivel_cliente'];

        $stmt = $pdo->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?");
        $stmt->execute([$usuario['id']]);

        try {
            $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuario['id'], 'login', "Login realizado", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar log de login: " . $e->getMessage());
        }

        return ["sucesso" => true, "nome" => $usuario['nome']];
    }

    $tentativas = (int) $usuario['tentativas_login'] + 1;

    if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
        $stmt = $pdo->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
        $stmt->execute([BLOQUEIO_LOGIN_MINUTOS, $usuario['id']]);
        return ["sucesso" => false, "mensagem" => "Conta bloqueada temporariamente por multiplas tentativas incorretas. Tente novamente em " . BLOQUEIO_LOGIN_MINUTOS . " minutos."];
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET tentativas_login = ? WHERE id = ?");
    $stmt->execute([$tentativas, $usuario['id']]);

    return ["sucesso" => false, "mensagem" => "E-mail ou senha incorretos."];
}

function solicitarRecuperacaoSenha($email, $csrfToken) {
    global $pdo;

    $mensagemPadrao = "Se o e-mail informado estiver cadastrado, enviaremos instrucoes de recuperacao.";

    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de seguranca invalido."];
    }

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!validarEmail($email)) {
        return ["sucesso" => false, "mensagem" => "E-mail invalido."];
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND situacao = 'ativo'");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Resposta identica exista ou nao o e-mail, para nao permitir enumeracao de contas
    if (!$usuario) {
        return ["sucesso" => true, "mensagem" => $mensagemPadrao];
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("UPDATE usuarios SET token_recuperacao = ?, token_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $stmt->execute([$tokenHash, $usuario['id']]);

    $esquema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/\\');
    $link = "$esquema://{$_SERVER['HTTP_HOST']}$base/redefinir_senha.php?token=$token";

    // Em ambiente local sem SMTP configurado, mail() normalmente falha silenciosamente;
    // o link tambem vai para o log do servidor para permitir testes sem envio real de e-mail.
    @mail($email, 'Recuperacao de senha - LocaFacil', "Clique no link para redefinir sua senha (valido por 1 hora):\n\n$link");
    error_log("[LocaFacil] Link de recuperacao de senha para $email: $link");

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario['id'], 'recuperacao_senha_solicitada', "Solicitacao de recuperacao de senha", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log de recuperacao de senha: " . $e->getMessage());
    }

    return ["sucesso" => true, "mensagem" => $mensagemPadrao];
}

function redefinirSenha($token, $novaSenha, $csrfToken) {
    global $pdo;

    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de seguranca invalido."];
    }

    if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return ["sucesso" => false, "mensagem" => "Link invalido ou expirado. Solicite uma nova recuperacao."];
    }

    if (!validarSenha($novaSenha)) {
        return ["sucesso" => false, "mensagem" => "A senha deve ter pelo menos 8 caracteres, uma letra maiuscula e um numero."];
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_expira > NOW() AND situacao = 'ativo'");
    $stmt->execute([$tokenHash]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return ["sucesso" => false, "mensagem" => "Link invalido ou expirado. Solicite uma nova recuperacao."];
    }

    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET senhaHash = ?, token_recuperacao = NULL, token_expira = NULL, tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?");
    $stmt->execute([$senhaHash, $usuario['id']]);

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario['id'], 'senha_redefinida', "Senha redefinida via recuperacao", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log de redefinicao de senha: " . $e->getMessage());
    }

    return ["sucesso" => true, "mensagem" => "Senha redefinida com sucesso. Voce ja pode fazer login."];
}

function logoutUsuario() {
    if (isset($_SESSION['usuario_id'])) {
        global $pdo;

        // O registro de auditoria nunca deve impedir o logout de completar
        try {
            $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], 'logout', "Logout realizado", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar log de logout: " . $e->getMessage());
        }
    }

    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    return ["sucesso" => true];
}

function getUsuarioLogado() {
    if (isset($_SESSION['usuario_id'])) {
        return [
            "id" => $_SESSION['usuario_id'],
            "nome" => $_SESSION['usuario_nome'],
            "email" => $_SESSION['usuario_email'] ?? '',
            "telefone" => $_SESSION['usuario_telefone'] ?? '',
            "nivel" => $_SESSION['usuario_nivel'] ?? 'comum'
        ];
    }
    return null;
}

function atualizarPerfilUsuario($usuarioId, $nome, $telefone, $novaSenha, $senhaAtual, $csrfToken) {
    global $pdo;

    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de seguranca invalido."];
    }

    $nome = sanitizarEntrada($nome);
    $telefone = sanitizarEntrada($telefone);

    if (empty($nome) || empty($telefone) || empty($senhaAtual)) {
        return ["sucesso" => false, "mensagem" => "Nome, telefone e senha atual sao obrigatorios."];
    }

    if (!validarTelefone($telefone)) {
        return ["sucesso" => false, "mensagem" => "Telefone invalido."];
    }

    if (!empty($novaSenha) && !validarSenha($novaSenha)) {
        return ["sucesso" => false, "mensagem" => "A nova senha deve ter pelo menos 8 caracteres, uma letra maiuscula e um numero."];
    }

    $stmt = $pdo->prepare("SELECT senhaHash FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senhaAtual, $usuario['senhaHash'])) {
        return ["sucesso" => false, "mensagem" => "Senha atual incorreta."];
    }

    try {
        if (!empty($novaSenha)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, telefone = ?, senhaHash = ? WHERE id = ?");
            $stmt->execute([$nome, $telefone, password_hash($novaSenha, PASSWORD_DEFAULT), $usuarioId]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?");
            $stmt->execute([$nome, $telefone, $usuarioId]);
        }

        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_telefone'] = $telefone;

        try {
            $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuarioId, 'perfil_atualizado', "Dados da conta atualizados", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar log de atualizacao de perfil: " . $e->getMessage());
        }

        return ["sucesso" => true, "mensagem" => "Dados atualizados com sucesso.", "nome" => $nome];
    } catch (PDOException $e) {
        error_log("Erro ao atualizar perfil: " . $e->getMessage());
        return ["sucesso" => false, "mensagem" => "Erro ao atualizar dados. Tente novamente."];
    }
}

function estaLogado() {
    return isset($_SESSION['usuario_id']);
}
?>