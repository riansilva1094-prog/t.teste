<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
exigirPermissao('motoristas', $id ? 'editar' : 'criar');

$motorista = [
    'nome' => '', 'email' => '', 'telefone' => '', 'cnh' => '',
    'cnh_categoria' => 'B', 'cnh_validade' => '', 'situacao' => 'ativo',
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM motoristas WHERE id = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        flash('erro', 'Motorista nao encontrado.');
        redirecionar('motoristas.php');
    }
    $motorista = $encontrado;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } else {
        $motorista['nome'] = trim($_POST['nome'] ?? '');
        $motorista['email'] = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $motorista['telefone'] = trim($_POST['telefone'] ?? '');
        $motorista['cnh'] = trim($_POST['cnh'] ?? '');
        $motorista['cnh_categoria'] = strtoupper(trim($_POST['cnh_categoria'] ?? ''));
        $motorista['cnh_validade'] = $_POST['cnh_validade'] ?? '';
        $motorista['situacao'] = $_POST['situacao'] ?? 'ativo';

        if (empty($motorista['nome']) || empty($motorista['telefone']) || empty($motorista['cnh'])) {
            $erro = 'Nome, telefone e CNH sao obrigatorios.';
        } elseif (!empty($motorista['email']) && !validarEmail($motorista['email'])) {
            $erro = 'E-mail invalido.';
        } elseif (!validarTelefone($motorista['telefone'])) {
            $erro = 'Telefone invalido.';
        } elseif (!validarCNH($motorista['cnh'])) {
            $erro = 'Numero de CNH invalido (apenas numeros, 9 a 20 digitos).';
        } elseif (empty($motorista['cnh_validade'])) {
            $erro = 'Informe a validade da CNH.';
        } else {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("
                        UPDATE motoristas SET nome=?, email=?, telefone=?, cnh=?, cnh_categoria=?, cnh_validade=?, situacao=?
                        WHERE id=?
                    ");
                    $stmt->execute([
                        $motorista['nome'], $motorista['email'] ?: null, $motorista['telefone'], $motorista['cnh'],
                        $motorista['cnh_categoria'], $motorista['cnh_validade'], $motorista['situacao'], $id,
                    ]);
                    flash('sucesso', 'Motorista atualizado com sucesso.');
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO motoristas (nome, email, telefone, cnh, cnh_categoria, cnh_validade, situacao)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $motorista['nome'], $motorista['email'] ?: null, $motorista['telefone'], $motorista['cnh'],
                        $motorista['cnh_categoria'], $motorista['cnh_validade'], $motorista['situacao'],
                    ]);
                    flash('sucesso', 'Motorista cadastrado com sucesso.');
                }
                redirecionar('motoristas.php');
            } catch (PDOException $e) {
                $erro = str_contains($e->getMessage(), 'Duplicate') ? 'Ja existe um motorista com esta CNH ou e-mail.' : 'Erro ao salvar motorista.';
            }
        }
    }
}

$csrfToken = gerarTokenCSRF();
$paginaAtual = 'motoristas';
$tituloPagina = $id ? 'Editar Motorista' : 'Novo Motorista';
$subtituloPagina = $id ? 'Atualize os dados do motorista' : 'Cadastre um novo motorista';

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
                <label>Nome completo</label>
                <input type="text" name="nome" required value="<?php echo htmlspecialchars($motorista['nome']); ?>">
            </div>
            <div class="form-grupo">
                <label>E-mail</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($motorista['email'] ?? ''); ?>">
            </div>
            <div class="form-grupo">
                <label>Telefone</label>
                <input type="text" name="telefone" data-mask="telefone" required placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($motorista['telefone']); ?>">
            </div>
            <div class="form-grupo">
                <label>Numero da CNH</label>
                <input type="text" name="cnh" data-mask="numeros" data-mask-length="20" required value="<?php echo htmlspecialchars($motorista['cnh']); ?>">
            </div>
            <div class="form-grupo">
                <label>Categoria CNH</label>
                <select name="cnh_categoria">
                    <?php foreach (['A', 'B', 'AB', 'C', 'D', 'E'] as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $motorista['cnh_categoria'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label>Validade da CNH</label>
                <input type="date" name="cnh_validade" required value="<?php echo htmlspecialchars($motorista['cnh_validade']); ?>">
            </div>
            <div class="form-grupo">
                <label>Situacao</label>
                <select name="situacao">
                    <option value="ativo" <?php echo $motorista['situacao'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $motorista['situacao'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a href="motoristas.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
