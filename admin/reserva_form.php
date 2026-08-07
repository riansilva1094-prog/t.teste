<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
exigirPermissao('reservas', $id ? 'editar' : 'criar');

$reserva = ['usuarioId' => '', 'veiculoId' => '', 'data_inicio' => '', 'data_fim' => '', 'valor_total' => '', 'status' => 'pendente'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if (!$encontrada) {
        flash('erro', 'Reserva nao encontrada.');
        redirecionar('reservas.php');
    }
    $reserva = $encontrada;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } else {
        $reserva['usuarioId'] = (int) ($_POST['usuarioId'] ?? 0);
        $reserva['veiculoId'] = (int) ($_POST['veiculoId'] ?? 0);
        $reserva['data_inicio'] = $_POST['data_inicio'] ?? '';
        $reserva['data_fim'] = $_POST['data_fim'] ?? '';
        $reserva['valor_total'] = str_replace(',', '.', $_POST['valor_total'] ?? '0');
        $reserva['status'] = $_POST['status'] ?? 'pendente';

        $statusValidos = ['pendente', 'confirmada', 'cancelada', 'finalizada'];
        $inicioObj = DateTime::createFromFormat('Y-m-d', $reserva['data_inicio']);
        $fimObj = DateTime::createFromFormat('Y-m-d', $reserva['data_fim']);

        if ($reserva['usuarioId'] <= 0 || $reserva['veiculoId'] <= 0) {
            $erro = 'Selecione o cliente e o veiculo.';
        } elseif (!$inicioObj || !$fimObj) {
            $erro = 'Datas invalidas.';
        } elseif ($fimObj <= $inicioObj) {
            $erro = 'A data de devolucao deve ser posterior a data de retirada.';
        } elseif (!is_numeric($reserva['valor_total']) || (float) $reserva['valor_total'] <= 0) {
            $erro = 'Valor total invalido.';
        } elseif (!in_array($reserva['status'], $statusValidos, true)) {
            $erro = 'Status invalido.';
        } else {
            $sqlOverlap = "
                SELECT COUNT(*) FROM reservas
                WHERE veiculoId = ? AND status IN ('pendente', 'confirmada')
                AND data_inicio <= ? AND data_fim >= ?
            ";
            $params = [$reserva['veiculoId'], $reserva['data_fim'], $reserva['data_inicio']];
            if ($id) {
                $sqlOverlap .= " AND id != ?";
                $params[] = $id;
            }
            $stmt = $pdo->prepare($sqlOverlap);
            $stmt->execute($params);

            if (in_array($reserva['status'], ['pendente', 'confirmada'], true) && (int) $stmt->fetchColumn() > 0) {
                $erro = 'Ja existe outra reserva ativa para este veiculo neste periodo.';
            } else {
                try {
                    if ($id) {
                        $stmt = $pdo->prepare("
                            UPDATE reservas SET usuarioId=?, veiculoId=?, data_inicio=?, data_fim=?, valor_total=?, status=?
                            WHERE id=?
                        ");
                        $stmt->execute([$reserva['usuarioId'], $reserva['veiculoId'], $reserva['data_inicio'], $reserva['data_fim'], $reserva['valor_total'], $reserva['status'], $id]);
                        flash('sucesso', 'Reserva atualizada com sucesso.');
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO reservas (usuarioId, veiculoId, data_inicio, data_fim, valor_total, status)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$reserva['usuarioId'], $reserva['veiculoId'], $reserva['data_inicio'], $reserva['data_fim'], $reserva['valor_total'], $reserva['status']]);
                        flash('sucesso', 'Reserva criada com sucesso.');
                    }
                    redirecionar('reservas.php');
                } catch (PDOException $e) {
                    $erro = 'Erro ao salvar reserva.';
                }
            }
        }
    }
}

$usuarios = $pdo->query("SELECT id, nome, email FROM usuarios WHERE situacao = 'ativo' ORDER BY nome")->fetchAll();
$veiculosLista = $pdo->query("SELECT id, marca, modelo, placa, diaria FROM veiculos ORDER BY marca, modelo")->fetchAll();
$csrfToken = gerarTokenCSRF();
$paginaAtual = 'reservas';
$tituloPagina = $id ? 'Editar Reserva' : 'Nova Reserva';
$subtituloPagina = $id ? 'Atualize os dados da reserva' : 'Registre uma reserva manualmente (ex.: solicitada por telefone)';

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="form-card">
    <?php if ($erro): ?>
        <div class="flash erro"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="form-grid">
            <div class="form-grupo">
                <label>Cliente</label>
                <select name="usuarioId" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo (string) $reserva['usuarioId'] === (string) $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nome'] . ' (' . $u['email'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label>Veiculo</label>
                <select name="veiculoId" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($veiculosLista as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo (string) $reserva['veiculoId'] === (string) $v['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' - ' . $v['placa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label>Data de retirada</label>
                <input type="date" name="data_inicio" required value="<?php echo htmlspecialchars($reserva['data_inicio']); ?>">
            </div>
            <div class="form-grupo">
                <label>Data de devolucao</label>
                <input type="date" name="data_fim" required value="<?php echo htmlspecialchars($reserva['data_fim']); ?>">
            </div>
            <div class="form-grupo">
                <label>Valor total (R$)</label>
                <input type="text" name="valor_total" required value="<?php echo htmlspecialchars($reserva['valor_total']); ?>">
            </div>
            <div class="form-grupo">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['pendente', 'confirmada', 'cancelada', 'finalizada'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $reserva['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a href="reservas.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
