<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
exigirPermissao('usuarios', $id ? 'editar' : 'criar');

$usuario = ['nome' => '', 'email' => '', 'telefone' => '', 'situacao' => 'ativo', 'tipo_pessoa' => 'fisica', 'nivel_cliente' => 'comum'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        flash('erro', 'Usuario nao encontrado.');
        redirecionar('usuarios.php');
    }
    $usuario = $encontrado;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } else {
        $usuario['nome'] = trim($_POST['nome'] ?? '');
        $usuario['email'] = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $usuario['telefone'] = trim($_POST['telefone'] ?? '');
        $usuario['situacao'] = $_POST['situacao'] ?? 'ativo';
        $usuario['tipo_pessoa'] = $_POST['tipo_pessoa'] ?? 'fisica';
        $usuario['nivel_cliente'] = $_POST['nivel_cliente'] ?? 'comum';
        $senha = $_POST['senha'] ?? '';

        $tiposValidos = ['fisica', 'juridica'];
        $niveisValidos = ['comum', 'vip', 'bloqueado'];

        if (empty($usuario['nome']) || empty($usuario['email']) || empty($usuario['telefone'])) {
            $erro = 'Nome, e-mail e telefone sao obrigatorios.';
        } elseif (!validarEmail($usuario['email'])) {
            $erro = 'E-mail invalido.';
        } elseif (!validarTelefone($usuario['telefone'])) {
            $erro = 'Telefone invalido.';
        } elseif (!in_array($usuario['tipo_pessoa'], $tiposValidos, true)) {
            $erro = 'Tipo de pessoa invalido.';
        } elseif (!in_array($usuario['nivel_cliente'], $niveisValidos, true)) {
            $erro = 'Nivel de cliente invalido.';
        } elseif (!$id && empty($senha)) {
            $erro = 'Senha e obrigatoria para novos usuarios.';
        } elseif (!empty($senha) && !validarSenha($senha)) {
            $erro = 'A senha deve ter pelo menos 8 caracteres, uma letra maiuscula e um numero.';
        } else {
            try {
                if ($id) {
                    if (!empty($senha)) {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, telefone=?, situacao=?, tipo_pessoa=?, nivel_cliente=?, senhaHash=? WHERE id=?");
                        $stmt->execute([$usuario['nome'], $usuario['email'], $usuario['telefone'], $usuario['situacao'], $usuario['tipo_pessoa'], $usuario['nivel_cliente'], password_hash($senha, PASSWORD_DEFAULT), $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, telefone=?, situacao=?, tipo_pessoa=?, nivel_cliente=? WHERE id=?");
                        $stmt->execute([$usuario['nome'], $usuario['email'], $usuario['telefone'], $usuario['situacao'], $usuario['tipo_pessoa'], $usuario['nivel_cliente'], $id]);
                    }
                    flash('sucesso', 'Usuario atualizado com sucesso.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senhaHash, situacao, tipo_pessoa, nivel_cliente) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$usuario['nome'], $usuario['email'], $usuario['telefone'], password_hash($senha, PASSWORD_DEFAULT), $usuario['situacao'], $usuario['tipo_pessoa'], $usuario['nivel_cliente']]);
                    flash('sucesso', 'Usuario cadastrado com sucesso.');
                }
                redirecionar('usuarios.php');
            } catch (PDOException $e) {
                $erro = str_contains($e->getMessage(), 'Duplicate') ? 'Ja existe um usuario com este e-mail.' : 'Erro ao salvar usuario.';
            }
        }
    }
}

$csrfToken = gerarTokenCSRF();
$paginaAtual = 'usuarios';
$tituloPagina = $id ? 'Editar Usuario' : 'Novo Usuario';
$subtituloPagina = $id ? 'Atualize os dados do cliente' : 'Cadastre um novo cliente';

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
                <input type="text" name="nome" required value="<?php echo htmlspecialchars($usuario['nome']); ?>">
            </div>
            <div class="form-grupo">
                <label>E-mail</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
            </div>
            <div class="form-grupo">
                <label>Telefone</label>
                <input type="text" name="telefone" data-mask="telefone" required placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($usuario['telefone']); ?>">
            </div>
            <div class="form-grupo">
                <label>Situacao</label>
                <select name="situacao">
                    <option value="ativo" <?php echo $usuario['situacao'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $usuario['situacao'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Tipo de pessoa</label>
                <select name="tipo_pessoa">
                    <option value="fisica" <?php echo $usuario['tipo_pessoa'] === 'fisica' ? 'selected' : ''; ?>>Pessoa Fisica</option>
                    <option value="juridica" <?php echo $usuario['tipo_pessoa'] === 'juridica' ? 'selected' : ''; ?>>Pessoa Juridica</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Nivel do cliente</label>
                <select name="nivel_cliente">
                    <option value="comum" <?php echo $usuario['nivel_cliente'] === 'comum' ? 'selected' : ''; ?>>Comum</option>
                    <option value="vip" <?php echo $usuario['nivel_cliente'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                    <option value="bloqueado" <?php echo $usuario['nivel_cliente'] === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                </select>
                <small>Bloqueado impede novos logins do cliente no site.</small>
            </div>
            <div class="form-grupo full">
                <label>Senha <?php echo $id ? '(deixe em branco para manter a atual)' : ''; ?></label>
                <input type="password" name="senha" minlength="8" <?php echo $id ? '' : 'required'; ?>>
                <small>Minimo 8 caracteres, com letra maiuscula e numero.</small>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a href="usuarios.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
