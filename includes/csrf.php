<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

function criarCampoCSRF() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(gerarTokenCSRF()) . '">';
}
?>