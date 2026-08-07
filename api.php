<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cadastrar':
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = cadastrarUsuario($data['nome'], $data['email'], $data['telefone'], $data['senha'], $csrfToken);
        echo json_encode($res);
        break;
        
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = loginUsuario($data['email'], $data['senha'], $csrfToken);
        echo json_encode($res);
        break;
        
    case 'logout':
        $res = logoutUsuario();
        echo json_encode($res);
        break;
        
    default:
        echo json_encode(["erro" => "Acao invalida."]);
        break;
}
?>