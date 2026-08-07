<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
exigirPermissao('reservas', 'ver');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Token de seguranca invalido.');
        redirecionar('reservas.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'deletar') {
        exigirPermissao('reservas', 'deletar');
        $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = ?");
        $stmt->execute([$id]);
        flash('sucesso', 'Reserva removida com sucesso.');
    } elseif (in_array($acao, ['confirmar', 'cancelar', 'finalizar'], true)) {
        exigirPermissao('reservas', 'editar');
        $novoStatus = ['confirmar' => 'confirmada', 'cancelar' => 'cancelada', 'finalizar' => 'finalizada'][$acao];
        $stmt = $pdo->prepare("UPDATE reservas SET status = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $id]);
        flash('sucesso', 'Status da reserva atualizado.');
    }

    redirecionar('reservas.php');
}

$paginaAtual = 'reservas';
$tituloPagina = 'Reservas';
$subtituloPagina = 'Acompanhe e gerencie as locacoes solicitadas pelos clientes';
$csrfToken = gerarTokenCSRF();

if (temPermissao('reservas', 'criar')) {
    $acaoTopo = '<a href="reserva_form.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Nova Reserva</a>';
}

$filtroStatus = $_GET['status'] ?? 'todos';
$statusValidos = ['pendente', 'confirmada', 'cancelada', 'finalizada'];

$sql = "
    SELECT r.*, u.nome AS usuario_nome, u.email AS usuario_email, v.marca, v.modelo, v.placa
    FROM reservas r
    JOIN usuarios u ON r.usuarioId = u.id
    JOIN veiculos v ON r.veiculoId = v.id
";
if (in_array($filtroStatus, $statusValidos, true)) {
    $sql .= " WHERE r.status = " . $pdo->quote($filtroStatus);
}
$sql .= " ORDER BY r.createdAt DESC";

$reservas = $pdo->query($sql)->fetchAll();

require __DIR__ . '/includes/layout_topo.php';
?>

<div style="margin-bottom: 18px; display:flex; gap:8px; flex-wrap:wrap;">
    <a href="reservas.php" class="btn-secondary btn-sm <?php echo $filtroStatus === 'todos' ? 'ativo' : ''; ?>">Todos</a>
    <?php foreach ($statusValidos as $s): ?>
        <a href="reservas.php?status=<?php echo $s; ?>" class="btn-secondary btn-sm <?php echo $filtroStatus === $s ? 'ativo' : ''; ?>"><?php echo ucfirst($s); ?></a>
    <?php endforeach; ?>
</div>

<div class="tabela-card">
    <?php if (empty($reservas)): ?>
        <div class="sem-dados">Nenhuma reserva encontrada.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Veiculo</th>
                <th>Periodo</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['usuario_nome']); ?><br><small style="color:var(--gray);"><?php echo htmlspecialchars($r['usuario_email']); ?></small></td>
                <td><?php echo htmlspecialchars($r['marca'] . ' ' . $r['modelo']); ?><br><small style="color:var(--gray);"><?php echo htmlspecialchars($r['placa']); ?></small></td>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($r['data_inicio']))); ?> a <?php echo htmlspecialchars(date('d/m/Y', strtotime($r['data_fim']))); ?></td>
                <td>R$ <?php echo number_format($r['valor_total'], 2, ',', '.'); ?></td>
                <td><span class="badge <?php echo $r['status'] === 'cancelada' ? 'inativo' : 'ativo'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                <td>
                    <div class="acoes-tabela">
                        <?php if (temPermissao('reservas', 'editar')): ?>
                            <a href="reserva_form.php?id=<?php echo $r['id']; ?>" class="btn-secondary btn-sm"><i class="bi bi-pencil-fill"></i></a>
                            <?php if ($r['status'] === 'pendente'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="acao" value="confirmar">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-secondary btn-sm" title="Confirmar"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'confirmada'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="acao" value="finalizar">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-secondary btn-sm" title="Finalizar"><i class="bi bi-flag-fill"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($r['status'], ['pendente', 'confirmada'], true)): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Cancelar esta reserva?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="acao" value="cancelar">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-danger btn-sm" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (temPermissao('reservas', 'deletar')): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Remover esta reserva permanentemente?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <button type="submit" class="btn-danger btn-sm"><i class="bi bi-trash-fill"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
