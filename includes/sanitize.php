<?php
function sanitizarEntrada($dados) {
    if (is_array($dados)) {
        return array_map('sanitizarEntrada', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validarTelefone($telefone) {
    return preg_match('/^[0-9\s\-\(\)\+]{10,20}$/', $telefone);
}

function validarSenha($senha) {
    return strlen($senha) >= 8 && preg_match('/[A-Z]/', $senha) && preg_match('/[0-9]/', $senha);
}

function validarCNH($cnh) {
    return preg_match('/^[0-9]{9,20}$/', $cnh);
}
?>