<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$MODULOS = ['veiculos', 'motoristas', 'usuarios', 'funcionarios', 'reservas'];
$ACOES = ['ver' => 'pode_ver', 'criar' => 'pode_criar', 'editar' => 'pode_editar', 'deletar' => 'pode_deletar'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
exigirPermissao('funcionarios', $id ? 'editar' : 'criar');

$funcionarioLogado = getFuncionarioLogado();
$funcionario = ['nome' => '', 'email' => '', 'telefone' => '', 'cargo' => 'atendente', 'situacao' => 'ativo'];
$permissoesAtuais = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        flash('erro', 'Funcionario nao encontrado.');
        redirecionar('funcionarios.php');
    }
    $funcionario = $encontrado;

    $stmt = $pdo->prepare("SELECT * FROM funcionario_permissoes WHERE funcionarioId = ?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $linha) {
        $permissoesAtuais[$linha['modulo']] = $linha;
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de seguranca invalido. Tente novamente.';
    } else {
        $funcionario['nome'] = trim($_POST['nome'] ?? '');
        $funcionario['email'] = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $funcionario['telefone'] = trim($_POST['telefone'] ?? '');
        $funcionario['cargo'] = $_POST['cargo'] ?? 'atendente';
        $funcionario['situacao'] = $_POST['situacao'] ?? 'ativo';
        $senha = $_POST['senha'] ?? '';
        $permissoesPost = $_POST['permissoes'] ?? [];

        $cargosValidos = ['admin', 'gerente', 'atendente'];

        if (empty($funcionario['nome']) || empty($funcionario['email'])) {
            $erro = 'Nome e e-mail sao obrigatorios.';
        } elseif (!validarEmail($funcionario['email'])) {
            $erro = 'E-mail invalido.';
        } elseif (!in_array($funcionario['cargo'], $cargosValidos, true)) {
            $erro = 'Cargo invalido.';
        } elseif (!$id && empty($senha)) {
            $erro = 'Senha e obrigatoria para novos funcionarios.';
        } elseif (!empty($senha) && !validarSenha($senha)) {
            $erro = 'A senha deve ter pelo menos 8 caracteres, uma letra maiuscula e um numero.';
        } elseif ($id && $encontrado['cargo'] === 'admin' && $encontrado['situacao'] === 'ativo'
            && ($funcionario['cargo'] !== 'admin' || $funcionario['situacao'] !== 'ativo')
            && contarAdminsAtivos($id) === 0) {
            $erro = 'Nao e possivel remover o cargo/status do unico administrador ativo do painel.';
        } else {
            try {
                $pdo->beginTransaction();

                if ($id) {
                    if (!empty($senha)) {
                        $stmt = $pdo->prepare("UPDATE funcionarios SET nome=?, email=?, telefone=?, cargo=?, situacao=?, senhaHash=? WHERE id=?");
                        $stmt->execute([$funcionario['nome'], $funcionario['email'], $funcionario['telefone'], $funcionario['cargo'], $funcionario['situacao'], password_hash($senha, PASSWORD_DEFAULT), $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE funcionarios SET nome=?, email=?, telefone=?, cargo=?, situacao=? WHERE id=?");
                        $stmt->execute([$funcionario['nome'], $funcionario['email'], $funcionario['telefone'], $funcionario['cargo'], $funcionario['situacao'], $id]);
                    }
                    $funcionarioId = $id;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, email, telefone, senhaHash, cargo, situacao) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$funcionario['nome'], $funcionario['email'], $funcionario['telefone'], password_hash($senha, PASSWORD_DEFAULT), $funcionario['cargo'], $funcionario['situacao']]);
                    $funcionarioId = (int) $pdo->lastInsertId();
                }

                // Sincroniza privilegios granulares (irrelevantes para cargo=admin, que tem acesso total)
                $stmt = $pdo->prepare("DELETE FROM funcionario_permissoes WHERE funcionarioId = ?");
                $stmt->execute([$funcionarioId]);

                if ($funcionario['cargo'] !== 'admin') {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO funcionario_permissoes (funcionarioId, modulo, pode_ver, pode_criar, pode_editar, pode_deletar)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($MODULOS as $modulo) {
                        $vals = $permissoesPost[$modulo] ?? [];
                        $ver = !empty($vals['ver']) ? 1 : 0;
                        $criar = !empty($vals['criar']) ? 1 : 0;
                        $editar = !empty($vals['editar']) ? 1 : 0;
                        $deletar = !empty($vals['deletar']) ? 1 : 0;
                        if ($ver || $criar || $editar || $deletar) {
                            $stmtInsert->execute([$funcionarioId, $modulo, $ver, $criar, $editar, $deletar]);
                        }
                    }
                }

                $pdo->commit();

                if ($id && (int) $id === (int) $funcionarioLogado['id']) {
                    $_SESSION['funcionario_nome'] = $funcionario['nome'];
                    $_SESSION['funcionario_cargo'] = $funcionario['cargo'];
                }

                flash('sucesso', $id ? 'Funcionario atualizado com sucesso.' : 'Funcionario cadastrado com sucesso.');
                redirecionar('funcionarios.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = str_contains($e->getMessage(), 'Duplicate') ? 'Ja existe um funcionario com este e-mail.' : 'Erro ao salvar funcionario.';
            }
        }

        // Mantem os checkboxes marcados como enviados caso haja erro de validacao
        if ($erro) {
            foreach ($MODULOS as $modulo) {
                $vals = $permissoesPost[$modulo] ?? [];
                $permissoesAtuais[$modulo] = [
                    'pode_ver' => !empty($vals['ver']) ? 1 : 0,
                    'pode_criar' => !empty($vals['criar']) ? 1 : 0,
                    'pode_editar' => !empty($vals['editar']) ? 1 : 0,
                    'pode_deletar' => !empty($vals['deletar']) ? 1 : 0,
                ];
            }
        }
    }
}

$csrfToken = gerarTokenCSRF();
$paginaAtual = 'funcionarios';
$tituloPagina = $id ? 'Editar Funcionario' : 'Novo Funcionario';
$subtituloPagina = 'Defina o cargo e, para cargos nao-admin, os privilegios de cada modulo';

require __DIR__ . '/includes/layout_topo.php';
?>

<div class="form-card" style="max-width: 900px;">
    <?php if ($erro): ?>
        <div class="flash erro"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="form-grid">
            <div class="form-grupo">
                <label>Nome completo</label>
                <input type="text" name="nome" required value="<?php echo htmlspecialchars($funcionario['nome']); ?>">
            </div>
            <div class="form-grupo">
                <label>E-mail</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($funcionario['email']); ?>">
            </div>
            <div class="form-grupo">
                <label>Telefone</label>
                <input type="text" name="telefone" data-mask="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($funcionario['telefone'] ?? ''); ?>">
            </div>
            <div class="form-grupo">
                <label>Cargo</label>
                <select name="cargo" id="campoCargo">
                    <option value="admin" <?php echo $funcionario['cargo'] === 'admin' ? 'selected' : ''; ?>>Admin (acesso total)</option>
                    <option value="gerente" <?php echo $funcionario['cargo'] === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                    <option value="atendente" <?php echo $funcionario['cargo'] === 'atendente' ? 'selected' : ''; ?>>Atendente</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Situacao</label>
                <select name="situacao">
                    <option value="ativo" <?php echo $funcionario['situacao'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $funcionario['situacao'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Senha <?php echo $id ? '(deixe em branco para manter a atual)' : ''; ?></label>
                <input type="password" name="senha" minlength="8" <?php echo $id ? '' : 'required'; ?>>
                <small>Minimo 8 caracteres, com letra maiuscula e numero.</small>
            </div>

            <div class="permissoes-box" id="blocoPermissoes">
                <label><i class="bi bi-key-fill"></i> Privilegios por modulo (ignorado para cargo Admin)</label>
                <table class="matriz-permissoes">
                    <thead>
                        <tr>
                            <th>Modulo</th>
                            <th>Ver</th>
                            <th>Criar</th>
                            <th>Editar</th>
                            <th>Deletar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($MODULOS as $modulo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($modulo); ?></td>
                            <?php foreach (['ver' => 'pode_ver', 'criar' => 'pode_criar', 'editar' => 'pode_editar', 'deletar' => 'pode_deletar'] as $acao => $coluna): ?>
                            <td>
                                <input type="checkbox" name="permissoes[<?php echo $modulo; ?>][<?php echo $acao; ?>]" value="1"
                                    <?php echo !empty($permissoesAtuais[$modulo][$coluna]) ? 'checked' : ''; ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a href="funcionarios.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Esconde a matriz de privilegios quando o cargo for Admin (acesso total, nao precisa de privilegios granulares)
    const campoCargo = document.getElementById('campoCargo');
    const blocoPermissoes = document.getElementById('blocoPermissoes');
    function atualizarVisibilidadePermissoes() {
        blocoPermissoes.style.display = campoCargo.value === 'admin' ? 'none' : 'block';
    }
    campoCargo.addEventListener('change', atualizarVisibilidadePermissoes);
    atualizarVisibilidadePermissoes();
</script>

<?php require __DIR__ . '/includes/layout_rodape.php'; ?>
