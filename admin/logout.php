<?php
require_once __DIR__ . '/includes/admin_auth.php';
logoutFuncionario();
header('Location: login.php');
exit;
?>
