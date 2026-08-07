<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

function criarReserva($usuarioId, $veiculoId, $dataInicio, $dataFim, $csrfToken) {
    global $pdo;

    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de seguranca invalido."];
    }

    $veiculoId = (int) $veiculoId;
    $dataInicioObj = DateTime::createFromFormat('Y-m-d', $dataInicio);
    $dataFimObj = DateTime::createFromFormat('Y-m-d', $dataFim);

    if (!$dataInicioObj || !$dataFimObj) {
        return ["sucesso" => false, "mensagem" => "Datas invalidas."];
    }

    $hoje = new DateTime('today');

    if ($dataInicioObj < $hoje) {
        return ["sucesso" => false, "mensagem" => "A data de inicio nao pode ser no passado."];
    }

    if ($dataFimObj <= $dataInicioObj) {
        return ["sucesso" => false, "mensagem" => "A data de devolucao deve ser posterior a data de retirada."];
    }

    $stmt = $pdo->prepare("SELECT id, diaria, status FROM veiculos WHERE id = ?");
    $stmt->execute([$veiculoId]);
    $veiculo = $stmt->fetch();

    if (!$veiculo) {
        return ["sucesso" => false, "mensagem" => "Veiculo nao encontrado."];
    }

    if ($veiculo['status'] !== 'disponivel') {
        return ["sucesso" => false, "mensagem" => "Este veiculo nao esta disponivel para locacao no momento."];
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservas
        WHERE veiculoId = ? AND status IN ('pendente', 'confirmada')
        AND data_inicio <= ? AND data_fim >= ?
    ");
    $stmt->execute([$veiculoId, $dataFim, $dataInicio]);

    if ((int) $stmt->fetchColumn() > 0) {
        return ["sucesso" => false, "mensagem" => "Este veiculo ja esta reservado neste periodo. Escolha outras datas."];
    }

    $dias = max(1, (int) $dataInicioObj->diff($dataFimObj)->days);
    $valorTotal = round($dias * (float) $veiculo['diaria'], 2);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO reservas (usuarioId, veiculoId, data_inicio, data_fim, valor_total, status)
            VALUES (?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([$usuarioId, $veiculoId, $dataInicio, $dataFim, $valorTotal]);
        $id = $pdo->lastInsertId();

        try {
            $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuarioId, 'reserva_criada', "Reserva #$id criada para o veiculo #$veiculoId", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar log de reserva: " . $e->getMessage());
        }

        return ["sucesso" => true, "mensagem" => "Reserva realizada com sucesso! Aguarde a confirmacao.", "id" => $id, "valor_total" => $valorTotal, "dias" => $dias];
    } catch (PDOException $e) {
        error_log("Erro ao criar reserva: " . $e->getMessage());
        return ["sucesso" => false, "mensagem" => "Erro ao criar reserva. Tente novamente."];
    }
}

function listarReservasDoUsuario($usuarioId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.*, v.marca, v.modelo, v.placa
        FROM reservas r
        JOIN veiculos v ON r.veiculoId = v.id
        WHERE r.usuarioId = ?
        ORDER BY r.createdAt DESC
    ");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

function cancelarReservaUsuario($usuarioId, $reservaId, $csrfToken) {
    global $pdo;

    if (!validarTokenCSRF($csrfToken)) {
        return ["sucesso" => false, "mensagem" => "Token de seguranca invalido."];
    }

    $stmt = $pdo->prepare("SELECT id, status FROM reservas WHERE id = ? AND usuarioId = ?");
    $stmt->execute([(int) $reservaId, $usuarioId]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        return ["sucesso" => false, "mensagem" => "Reserva nao encontrada."];
    }

    if (!in_array($reserva['status'], ['pendente', 'confirmada'], true)) {
        return ["sucesso" => false, "mensagem" => "Esta reserva nao pode mais ser cancelada."];
    }

    $stmt = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
    $stmt->execute([$reserva['id']]);

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_atividades (usuarioId, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuarioId, 'reserva_cancelada', "Reserva #{$reserva['id']} cancelada pelo cliente", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log de cancelamento de reserva: " . $e->getMessage());
    }

    return ["sucesso" => true, "mensagem" => "Reserva cancelada com sucesso."];
}
?>
