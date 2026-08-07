<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$paginaAtual = 'dashboard';
$tituloPagina = 'Dashboard';
$subtituloPagina = 'Visao geral do sistema LocaFacil';

$stats = [];

if (temPermissao('veiculos', 'ver')) {
    $stats['veiculos'] = $pdo->query("SELECT COUNT(*) FROM veiculos")->fetchColumn();
}
if (temPermissao('motoristas', 'ver')) {
    $stats['motoristas'] = $pdo->query("SELECT COUNT(*) FROM motoristas WHERE situacao = 'ativo'")->fetchColumn();
}
if (temPermissao('usuarios', 'ver')) {
    $stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE situacao = 'ativo'")->fetchColumn();
}
if (temPermissao('funcionarios', 'ver')) {
    $stats['funcionarios'] = $pdo->query("SELECT COUNT(*) FROM funcionarios WHERE situacao = 'ativo'")->fetchColumn();
}
if (temPermissao('reservas', 'ver')) {
    $stats['reservas'] = $pdo->query("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'")->fetchColumn();
}

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="stat-grid">
    <?php if (isset($stats['veiculos'])): ?>
    <div class="stat-card">
        <div class="icone"><i class="bi bi-car-front-fill"></i></div>
        <div>
            <div class="numero"><?php echo (int) $stats['veiculos']; ?></div>
            <div class="rotulo">Veiculos cadastrados</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($stats['motoristas'])): ?>
    <div class="stat-card">
        <div class="icone"><i class="bi bi-person-badge-fill"></i></div>
        <div>
            <div class="numero"><?php echo (int) $stats['motoristas']; ?></div>
            <div class="rotulo">Motoristas ativos</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($stats['usuarios'])): ?>
    <div class="stat-card">
        <div class="icone"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="numero"><?php echo (int) $stats['usuarios']; ?></div>
            <div class="rotulo">Usuarios ativos</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($stats['funcionarios'])): ?>
    <div class="stat-card">
        <div class="icone"><i class="bi bi-person-vcard-fill"></i></div>
        <div>
            <div class="numero"><?php echo (int) $stats['funcionarios']; ?></div>
            <div class="rotulo">Funcionarios ativos</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($stats['reservas'])): ?>
    <div class="stat-card">
        <div class="icone"><i class="bi bi-journal-check"></i></div>
        <div>
            <div class="numero"><?php echo (int) $stats['reservas']; ?></div>
            <div class="rotulo">Reservas pendentes</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($stats)): ?>
    <div class="tabela-card"><div class="sem-dados">Voce nao tem privilegios para visualizar dados ainda. Fale com um administrador.</div></div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
