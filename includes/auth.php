<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/sanitize.php';

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
    
    $stmt = $pdo->prepare("SELECT id, nome, senhaHash, situacao FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        return ["sucesso" => false, "mensagem" => "E-mail ou senha incorretos."];
    }
    
    if ($usuario['situacao'] !== 'ativo') {
        return ["sucesso" => false, "mensagem" => "Conta inativa. Entre em contato com o suporte."];
    }
    
    if (password_verify($senha, $usuario['senhaHash'])) {
        session_regenerate_id(true);
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $email;
        
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario['id'], 'login', "Login realizado", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        
        return ["sucesso" => true, "nome" => $usuario['nome']];
    }
    
    return ["sucesso" => false, "mensagem" => "E-mail ou senha incorretos."];
}

function logoutUsuario() {
    if (isset($_SESSION['usuario_id'])) {
        global $pdo;
        
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], 'logout', "Logout realizado", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
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
            "email" => $_SESSION['usuario_email'] ?? ''
        ];
    }
    return null;
}

function estaLogado() {
    return isset($_SESSION['usuario_id']);
}
?>