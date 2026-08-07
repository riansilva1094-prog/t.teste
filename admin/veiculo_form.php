<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
exigirPermissao('veiculos', $id ? 'editar' : 'criar');

$veiculo = [
    'categoriaId' => '', 'marca' => '', 'modelo' => '', 'placa' => '', 'ano' => date('Y'),
    'transmissao' => 'automatico', 'combustivel' => 'flex', 'passageiros' => 5,
    'diaria' => '', 'descricao' => '', 'status' => 'disponivel',
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        flash('erro', 'Veiculo nao encontrado.');
        redirecionar('veiculos.php');
    }
    $veiculo = $encontrado;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } else {
        $veiculo['categoriaId'] = (int) ($_POST['categoriaId'] ?? 0);
        $veiculo['marca'] = trim($_POST['marca'] ?? '');
        $veiculo['modelo'] = trim($_POST['modelo'] ?? '');
        $veiculo['placa'] = strtoupper(trim($_POST['placa'] ?? ''));
        $veiculo['ano'] = (int) ($_POST['ano'] ?? 0);
        $veiculo['transmissao'] = $_POST['transmissao'] ?? '';
        $veiculo['combustivel'] = $_POST['combustivel'] ?? '';
        $veiculo['passageiros'] = (int) ($_POST['passageiros'] ?? 0);
        $veiculo['diaria'] = str_replace(',', '.', $_POST['diaria'] ?? '0');
        $veiculo['descricao'] = trim($_POST['descricao'] ?? '');
        $veiculo['status'] = $_POST['status'] ?? 'disponivel';

        $transmissoesValidas = ['automatico', 'manual'];
        $combustiveisValidos = ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido'];
        $statusValidos = ['disponivel', 'reservado', 'manutencao', 'inativo'];

        if (empty($veiculo['marca']) || empty($veiculo['modelo']) || empty($veiculo['placa'])) {
            $erro = 'Marca, modelo e placa sao obrigatorios.';
        } elseif ($veiculo['categoriaId'] <= 0) {
            $erro = 'Selecione uma categoria valida.';
        } elseif ($veiculo['ano'] < 1980 || $veiculo['ano'] > (int) date('Y') + 1) {
            $erro = 'Ano invalido.';
        } elseif (!in_array($veiculo['transmissao'], $transmissoesValidas, true)) {
            $erro = 'Transmissao invalida.';
        } elseif (!in_array($veiculo['combustivel'], $combustiveisValidos, true)) {
            $erro = 'Combustivel invalido.';
        } elseif ($veiculo['passageiros'] <= 0) {
            $erro = 'Numero de passageiros invalido.';
        } elseif (!is_numeric($veiculo['diaria']) || (float) $veiculo['diaria'] <= 0) {
            $erro = 'Valor da diaria invalido.';
        } elseif (!in_array($veiculo['status'], $statusValidos, true)) {
            $erro = 'Status invalido.';
        } else {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("
                        UPDATE veiculos SET categoriaId=?, marca=?, modelo=?, placa=?, ano=?, transmissao=?,
                            combustivel=?, passageiros=?, diaria=?, descricao=?, status=?
                        WHERE id=?
                    ");
                    $stmt->execute([
                        $veiculo['categoriaId'], $veiculo['marca'], $veiculo['modelo'], $veiculo['placa'],
                        $veiculo['ano'], $veiculo['transmissao'], $veiculo['combustivel'], $veiculo['passageiros'],
                        $veiculo['diaria'], $veiculo['descricao'], $veiculo['status'], $id,
                    ]);
                    flash('sucesso', 'Veiculo atualizado com sucesso.');
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO veiculos (categoriaId, marca, modelo, placa, ano, transmissao, combustivel, passageiros, diaria, descricao, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $veiculo['categoriaId'], $veiculo['marca'], $veiculo['modelo'], $veiculo['placa'],
                        $veiculo['ano'], $veiculo['transmissao'], $veiculo['combustivel'], $veiculo['passageiros'],
                        $veiculo['diaria'], $veiculo['descricao'], $veiculo['status'],
                    ]);
                    flash('sucesso', 'Veiculo cadastrado com sucesso.');
                }
                redirecionar('veiculos.php');
            } catch (PDOException $e) {
                $erro = str_contains($e->getMessage(), 'Duplicate') ? 'Ja existe um veiculo com esta placa.' : 'Erro ao salvar veiculo.';
            }
        }
    }
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome")->fetchAll();
$csrfToken = gerarTokenCSRF();
$paginaAtual = 'veiculos';
$tituloPagina = $id ? 'Editar Veiculo' : 'Novo Veiculo';
$subtituloPagina = $id ? 'Atualize os dados do veiculo' : 'Cadastre um novo veiculo na frota';

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
                <label>Marca</label>
                <input type="text" name="marca" required value="<?php echo htmlspecialchars($veiculo['marca']); ?>">
            </div>
            <div class="form-grupo">
                <label>Modelo</label>
                <input type="text" name="modelo" required value="<?php echo htmlspecialchars($veiculo['modelo']); ?>">
            </div>
            <div class="form-grupo">
                <label>Placa</label>
                <input type="text" name="placa" data-mask="maiusculas" required maxlength="8" value="<?php echo htmlspecialchars($veiculo['placa']); ?>">
            </div>
            <div class="form-grupo">
                <label>Categoria</label>
                <select name="categoriaId" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (string) $veiculo['categoriaId'] === (string) $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label>Ano</label>
                <input type="number" name="ano" required value="<?php echo htmlspecialchars($veiculo['ano']); ?>">
            </div>
            <div class="form-grupo">
                <label>Passageiros</label>
                <input type="number" name="passageiros" required min="1" value="<?php echo htmlspecialchars($veiculo['passageiros']); ?>">
            </div>
            <div class="form-grupo">
                <label>Transmissao</label>
                <select name="transmissao">
                    <option value="automatico" <?php echo $veiculo['transmissao'] === 'automatico' ? 'selected' : ''; ?>>Automatico</option>
                    <option value="manual" <?php echo $veiculo['transmissao'] === 'manual' ? 'selected' : ''; ?>>Manual</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Combustivel</label>
                <select name="combustivel">
                    <?php foreach (['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido'] as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo $veiculo['combustivel'] === $c ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label>Diaria (R$)</label>
                <input type="text" name="diaria" required value="<?php echo htmlspecialchars($veiculo['diaria']); ?>">
            </div>
            <div class="form-grupo">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['disponivel', 'reservado', 'manutencao', 'inativo'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $veiculo['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo full">
                <label>Descricao</label>
                <textarea name="descricao" rows="3"><?php echo htmlspecialchars($veiculo['descricao']); ?></textarea>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a href="veiculos.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
