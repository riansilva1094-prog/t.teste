<?php
// API JSON: nunca deixar warnings/notices do PHP vazarem para a resposta (quebraria o
// JSON no cliente); eles vao para o log de erros em vez de serem impressos na tela.
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP [$errno]: $errstr em $errfile:$errline");
    return true;
});

require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/reservas.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$action = $_GET['action'] ?? '';

try {

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

    case 'esqueci_senha':
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = solicitarRecuperacaoSenha($data['email'] ?? '', $csrfToken);
        echo json_encode($res);
        break;

    case 'redefinir_senha':
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = redefinirSenha($data['token'] ?? '', $data['senha'] ?? '', $csrfToken);
        echo json_encode($res);
        break;

    case 'reservar':
        if (!estaLogado()) {
            echo json_encode(["sucesso" => false, "mensagem" => "Voce precisa estar logado para reservar."]);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = criarReserva($_SESSION['usuario_id'], $data['veiculoId'] ?? 0, $data['data_inicio'] ?? '', $data['data_fim'] ?? '', $csrfToken);
        echo json_encode($res);
        break;

    case 'cancelar_reserva':
        if (!estaLogado()) {
            echo json_encode(["sucesso" => false, "mensagem" => "Voce precisa estar logado."]);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = cancelarReservaUsuario($_SESSION['usuario_id'], $data['id'] ?? 0, $csrfToken);
        echo json_encode($res);
        break;

    case 'atualizar_perfil':
        if (!estaLogado()) {
            echo json_encode(["sucesso" => false, "mensagem" => "Voce precisa estar logado."]);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $data['csrf_token'] ?? '';
        $res = atualizarPerfilUsuario(
            $_SESSION['usuario_id'],
            $data['nome'] ?? '',
            $data['telefone'] ?? '',
            $data['novaSenha'] ?? '',
            $data['senhaAtual'] ?? '',
            $csrfToken
        );
        echo json_encode($res);
        break;

    default:
        echo json_encode(["erro" => "Acao invalida."]);
        break;
}

} catch (Throwable $e) {
    // Ultima linha de defesa: qualquer erro inesperado ainda assim retorna JSON valido,
    // nunca uma pagina de erro em HTML que quebraria o fetch().json() no navegador.
    error_log("Erro inesperado em api.php (action=$action): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["sucesso" => false, "mensagem" => "Erro interno. Tente novamente."]);
}
?>