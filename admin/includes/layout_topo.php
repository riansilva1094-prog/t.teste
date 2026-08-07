<?php
// Espera que a pagina que inclui isto ja tenha chamado exigirLogin()/exigirPermissao()
// e definido: $paginaAtual, $tituloPagina, $subtituloPagina (opcional)
$funcionario = getFuncionarioLogado();
$flash = pegarFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina ?? 'Painel'); ?> - Painel LocaFacil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-app">
        <aside class="sidebar">
            <div class="logo">Loca<span>Facil</span> Admin</div>
            <nav>
                <a href="index.php" class="<?php echo $paginaAtual === 'dashboard' ? 'ativo' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <?php if (temPermissao('veiculos', 'ver')): ?>
                <a href="veiculos.php" class="<?php echo $paginaAtual === 'veiculos' ? 'ativo' : ''; ?>">
                    <i class="bi bi-car-front-fill"></i> Veiculos
                </a>
                <?php endif; ?>
                <?php if (temPermissao('motoristas', 'ver')): ?>
                <a href="motoristas.php" class="<?php echo $paginaAtual === 'motoristas' ? 'ativo' : ''; ?>">
                    <i class="bi bi-person-badge-fill"></i> Motoristas
                </a>
                <?php endif; ?>
                <?php if (temPermissao('reservas', 'ver')): ?>
                <a href="reservas.php" class="<?php echo $paginaAtual === 'reservas' ? 'ativo' : ''; ?>">
                    <i class="bi bi-journal-check"></i> Reservas
                </a>
                <?php endif; ?>
                <?php if (temPermissao('usuarios', 'ver')): ?>
                <a href="usuarios.php" class="<?php echo $paginaAtual === 'usuarios' ? 'ativo' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Usuarios
                </a>
                <?php endif; ?>
                <?php if (temPermissao('funcionarios', 'ver')): ?>
                <a href="funcionarios.php" class="<?php echo $paginaAtual === 'funcionarios' ? 'ativo' : ''; ?>">
                    <i class="bi bi-person-vcard-fill"></i> Funcionarios
                </a>
                <?php endif; ?>
            </nav>
            <div class="rodape-sidebar">
                <div><?php echo htmlspecialchars($funcionario['nome']); ?></div>
                <span class="cargo"><?php echo htmlspecialchars($funcionario['cargo']); ?></span>
                <br>
                <a href="logout.php" class="sair"><i class="bi bi-box-arrow-right"></i> Sair</a>
            </div>
        </aside>

        <main class="conteudo">
            <div class="conteudo-header">
                <div>
                    <h1><?php echo htmlspecialchars($tituloPagina ?? ''); ?></h1>
                    <?php if (!empty($subtituloPagina)): ?>
                        <p><?php echo htmlspecialchars($subtituloPagina); ?></p>
                    <?php endif; ?>
                </div>
                <div class="conteudo-header-acoes">
                    <?php if (!empty($acaoTopo)): echo $acaoTopo; endif; ?>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="flash <?php echo $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro'; ?>">
                    <i class="bi bi-<?php echo $flash['tipo'] === 'sucesso' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                    <?php echo htmlspecialchars($flash['mensagem']); ?>
                </div>
            <?php endif; ?>
