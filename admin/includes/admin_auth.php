<?php
require_once __DIR__ . '/admin_session_init.php';
require_once __DIR__ . '/../../includes/db.php';

const MAX_TENTATIVAS_LOGIN_ADMIN = 5;
const BLOQUEIO_LOGIN_ADMIN_MINUTOS = 15;

// Retorna ['sucesso' => bool, 'mensagem' => string] em caso de falha
function loginFuncionario($email, $senha) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT id, nome, senhaHash, cargo, situacao, tentativas_login,
            (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS ainda_bloqueado
        FROM funcionarios WHERE email = ?
    ");
    $stmt->execute([$email]);
    $funcionario = $stmt->fetch();

    if (!$funcionario || $funcionario['situacao'] !== 'ativo') {
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos, ou conta inativa.'];
    }

    if ((bool) $funcionario['ainda_bloqueado']) {
        return ['sucesso' => false, 'mensagem' => 'Conta temporariamente bloqueada por multiplas tentativas incorretas. Tente novamente em alguns minutos.'];
    }

    if (!password_verify($senha, $funcionario['senhaHash'])) {
        $tentativas = (int) $funcionario['tentativas_login'] + 1;

        if ($tentativas >= MAX_TENTATIVAS_LOGIN_ADMIN) {
            $stmt = $pdo->prepare("UPDATE funcionarios SET tentativas_login = 0, bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
            $stmt->execute([BLOQUEIO_LOGIN_ADMIN_MINUTOS, $funcionario['id']]);
            return ['sucesso' => false, 'mensagem' => 'Conta bloqueada temporariamente por multiplas tentativas incorretas. Tente novamente em ' . BLOQUEIO_LOGIN_ADMIN_MINUTOS . ' minutos.'];
        }

        $stmt = $pdo->prepare("UPDATE funcionarios SET tentativas_login = ? WHERE id = ?");
        $stmt->execute([$tentativas, $funcionario['id']]);

        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos, ou conta inativa.'];
    }

    session_regenerate_id(true);
    $_SESSION['funcionario_id'] = $funcionario['id'];
    $_SESSION['funcionario_nome'] = $funcionario['nome'];
    $_SESSION['funcionario_cargo'] = $funcionario['cargo'];

    $stmt = $pdo->prepare("UPDATE funcionarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?");
    $stmt->execute([$funcionario['id']]);

    return ['sucesso' => true];
}

function logoutFuncionario() {
    unset($_SESSION['funcionario_id'], $_SESSION['funcionario_nome'], $_SESSION['funcionario_cargo']);
    session_regenerate_id(true);
}

function getFuncionarioLogado() {
    if (!isset($_SESSION['funcionario_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['funcionario_id'],
        'nome' => $_SESSION['funcionario_nome'],
        'cargo' => $_SESSION['funcionario_cargo'],
    ];
}

function exigirLogin() {
    if (!getFuncionarioLogado()) {
        header('Location: login.php');
        exit;
    }
}

// $acao: 'ver' | 'criar' | 'editar' | 'deletar'
function temPermissao($modulo, $acao) {
    global $pdo;

    $funcionario = getFuncionarioLogado();
    if (!$funcionario) {
        return false;
    }

    if ($funcionario['cargo'] === 'admin') {
        return true;
    }

    $colunas = [
        'ver' => 'pode_ver',
        'criar' => 'pode_criar',
        'editar' => 'pode_editar',
        'deletar' => 'pode_deletar',
    ];

    if (!isset($colunas[$acao])) {
        return false;
    }

    $coluna = $colunas[$acao];
    $stmt = $pdo->prepare("SELECT $coluna FROM funcionario_permissoes WHERE funcionarioId = ? AND modulo = ?");
    $stmt->execute([$funcionario['id'], $modulo]);
    $row = $stmt->fetch();

    return $row && (bool) $row[$coluna];
}

// Conta quantos funcionarios com cargo='admin' e situacao='ativo' existem, opcionalmente excluindo um id
function contarAdminsAtivos($excluirId = null) {
    global $pdo;

    if ($excluirId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM funcionarios WHERE cargo = 'admin' AND situacao = 'ativo' AND id != ?");
        $stmt->execute([$excluirId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM funcionarios WHERE cargo = 'admin' AND situacao = 'ativo'");
    }

    return (int) $stmt->fetchColumn();
}

function exigirPermissao($modulo, $acao) {
    exigirLogin();
    if (!temPermissao($modulo, $acao)) {
        http_response_code(403);
        require __DIR__ . '/acesso_negado.php';
        exit;
    }
}
?>
