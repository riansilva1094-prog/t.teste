<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/reservas.php';

$usuario = getUsuarioLogado();
$minhasReservas = $usuario ? listarReservasDoUsuario($usuario['id']) : [];

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM categorias WHERE ativa = 'sim' ORDER BY nome");
$categorias = $stmt->fetchAll();

// Buscar veículos iniciais (todos disponíveis)
$stmt = $pdo->query("SELECT v.*, c.nome as categoria_nome, c.slug as categoria_slug 
                    FROM veiculos v 
                    JOIN categorias c ON v.categoriaId = c.id 
                    WHERE v.status = 'disponivel' 
                    ORDER BY v.id DESC");
$veiculos = $stmt->fetchAll();

$csrfToken = gerarTokenCSRF();

// Mapeamento de imagens dos veículos
$imagensVeiculos = [
    1 => 'honda.webp',
    2 => 'jeep.webp',
    3 => 'porsche-911.jpg',
    4 => 'Toyota.jpg',
    5 => 'hatch.jpg',
    6 => 'luxo.jpg'
];

// Função para obter imagem do veículo
function getImagemVeiculo($id) {
    global $imagensVeiculos;
    return isset($imagensVeiculos[$id]) ? 'imagens/' . $imagensVeiculos[$id] : 'imagens/carro-padrao.jpg';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocaFácil - Aluguel de Carros</title>
    <meta name="description" content="LocaFácil - Aluguel de carros premium com segurança e conforto.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="css/veiculos.css">
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header id="header">
        <div class="header-container">
            <div class="logo">
                <img src="imagens/carro png.png" alt="LocaFácil" />
                <span>Loca<span>Fácil</span></span>
            </div>
            
            <nav id="nav-desktop">
                <a href="#home"><i class="bi bi-house-fill"></i> Home</a>
                <a href="#veiculos"><i class="bi bi-car-front-fill"></i> Veículos</a>
                <a href="#historia"><i class="bi bi-clock-fill"></i> Nossa História</a>
                <a href="#processo"><i class="bi bi-diagram-3-fill"></i> Processo</a>
                <?php if (!$usuario): ?>
                    <a href="#cadastro"><i class="bi bi-person-plus-fill"></i> Cadastro</a>
                <?php endif; ?>
                <?php if ($usuario): ?>
                    <div class="user-menu" id="userMenu">
                        <button class="user-menu-toggle" onclick="toggleUserMenu()">
                            <i class="bi bi-person-circle"></i>
                            <span><?php echo htmlspecialchars($usuario['nome']); ?></span>
                            <?php if ($usuario['nivel'] === 'vip'): ?>
                                <i class="bi bi-star-fill selo-vip" title="Cliente VIP"></i>
                            <?php endif; ?>
                            <i class="bi bi-chevron-down seta"></i>
                        </button>
                        <div class="user-menu-dropdown" id="userMenuDropdown">
                            <div class="user-menu-info">
                                <strong>
                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                    <?php if ($usuario['nivel'] === 'vip'): ?>
                                        <span class="badge-vip"><i class="bi bi-star-fill"></i> VIP</span>
                                    <?php endif; ?>
                                </strong>
                                <small><?php echo htmlspecialchars($usuario['email']); ?></small>
                            </div>
                            <a href="#reservas" onclick="fecharUserMenu()"><i class="bi bi-journal-check"></i> Minhas Reservas</a>
                            <a href="javascript:void(0)" onclick="fecharUserMenu(); toggleConfiguracoes();"><i class="bi bi-gear-fill"></i> Configurações</a>
                            <a href="javascript:void(0)" onclick="logout()" class="sair"><i class="bi bi-box-arrow-right"></i> Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="#login" onclick="toggleLogin()"><i class="bi bi-person-fill"></i> Login</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" aria-label="Alternar tema claro/escuro">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>
            </nav>

            <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <!-- Menu Mobile -->
        <div id="menu-mobile">
            <a href="#home"><i class="bi bi-house-fill"></i> Home</a>
            <a href="#veiculos"><i class="bi bi-car-front-fill"></i> Veículos</a>
            <a href="#historia"><i class="bi bi-clock-fill"></i> Nossa História</a>
            <a href="#processo"><i class="bi bi-diagram-3-fill"></i> Processo</a>
            <?php if (!$usuario): ?>
                <a href="#cadastro"><i class="bi bi-person-plus-fill"></i> Cadastro</a>
            <?php endif; ?>
            <?php if ($usuario): ?>
                <div class="user-menu-info-mobile">
                    <i class="bi bi-person-circle"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                        <small><?php echo htmlspecialchars($usuario['email']); ?></small>
                    </div>
                </div>
                <a href="#reservas"><i class="bi bi-journal-check"></i> Minhas Reservas</a>
                <a href="javascript:void(0)" onclick="toggleConfiguracoes()"><i class="bi bi-gear-fill"></i> Configurações</a>
                <a href="javascript:void(0)" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Sair</a>
            <?php else: ?>
                <a href="#login" onclick="toggleLogin()"><i class="bi bi-person-fill"></i> Login</a>
            <?php endif; ?>
            <a href="javascript:void(0)" onclick="toggleTheme()" class="theme-toggle-mobile">
                <i class="bi bi-moon-stars-fill"></i> Alternar tema
            </a>
        </div>
    </header>

    <main>
        <!-- ===== HOME / HERO ===== -->
        <section id="home" class="hero" data-banner-claro="imagens/banner-light.jpg" data-banner-escuro="imagens/banner.jpg" style="background-image: url('imagens/banner.jpg');">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="subtitulo"><i class="bi bi-stars"></i> LOCACAO PREMIUM</span>
                <h1>O veiculo certo para uma <span>jornada excepcional.</span></h1>
                <p>Uma frota selecionada, condicoes transparentes e atendimento dedicado para que cada trajeto seja memoravel.</p>
                <div class="hero-buttons">
                    <a href="#veiculos" class="btn-primary">
                        <i class="bi bi-car-front-fill"></i> Ver veiculos
                    </a>
                    <?php if ($usuario): ?>
                        <a href="#reservas" class="btn-secondary">
                            <i class="bi bi-journal-check"></i> Minhas Reservas
                        </a>
                    <?php else: ?>
                        <a href="#cadastro" class="btn-secondary">
                            <i class="bi bi-person-plus-fill"></i> Cadastre-se
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hero-assurances">
                    <span><i class="bi bi-shield-check"></i> Frota revisada</span>
                    <span><i class="bi bi-headset"></i> Atendimento dedicado</span>
                </div>
            </div>
        </section>

        <div class="section-divider"></div>

        <!-- ===== VEICULOS ===== -->
        <section id="veiculos" class="veiculos">
            <div class="titulo-veiculos">
                <span><i class="bi bi-car-front-fill"></i> Frota selecionada</span>
                <h2>Nossos <strong>Veiculos</strong></h2>
                <p>Encontre o equilibrio perfeito entre design, performance e conveniencia.</p>
            </div>

            <!-- Filtros -->
            <div class="filtros" id="filtros">
                <button class="ativo" data-filter="todos"><i class="bi bi-grid-3x3-gap-fill"></i> Todos</button>
                <?php foreach ($categorias as $cat): ?>
                    <button data-filter="<?php echo htmlspecialchars($cat['slug']); ?>">
                        <i class="bi bi-car-front-fill"></i> <?php echo htmlspecialchars($cat['nome']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Pesquisa e Select -->
            <div class="pesquisa-filtros">
                <div class="campo-busca">
                    <i class="bi bi-search"></i>
                    <input type="text" id="pesquisaVeiculo" placeholder="Buscar por modelo ou marca..." />
                </div>
                <div class="campo-select">
                    <i class="bi bi-sliders2"></i>
                    <select id="categoriaSelect">
                        <option value="todos">Todas Categorias</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>">
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Grid de Veiculos -->
            <div class="grid-veiculos" id="gridVeiculos">
                <?php foreach ($veiculos as $v): 
                    $imagem = getImagemVeiculo($v['id']);
                ?>
                    <div class="card-veiculo" data-categoria="<?php echo htmlspecialchars($v['categoria_slug']); ?>" data-id="<?php echo $v['id']; ?>">
                        <img src="<?php echo $imagem; ?>" alt="<?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?>" loading="lazy" />
                        <div class="info">
                            <h3><?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?></h3>
                            <p><i class="bi bi-gear-wide-connected"></i> <?php echo $v['transmissao'] == 'automatico' ? 'Automatico' : 'Manual'; ?> <i class="bi bi-fuel-pump"></i> <?php echo ucfirst($v['combustivel']); ?> <i class="bi bi-calendar3"></i> <?php echo $v['ano']; ?></p>
                            <span><i class="bi bi-currency-dollar"></i> R$ <?php echo number_format($v['diaria'], 0, ',', '.'); ?> / dia <br> R$ <?php echo number_format($v['diaria'] * 7, 0, ',', '.'); ?> / semana</span>
                            <?php if ($usuario): ?>
                                <a href="#" onclick='abrirReserva(<?php echo (int) $v['id']; ?>, <?php echo json_encode($v['marca'] . ' ' . $v['modelo'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>, <?php echo (float) $v['diaria']; ?>); return false;'><i class="bi bi-arrow-right-circle-fill"></i> Alugar Agora</a>
                            <?php else: ?>
                                <a href="#" onclick="toggleLogin()"><i class="bi bi-lock-fill"></i> Alugar Agora</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="no-results" class="no-results" style="display: none;">
                <i class="bi bi-car-front-fill"></i>
                <p>Nenhum veiculo encontrado com estes filtros.</p>
                <button onclick="resetFilters()"><i class="bi bi-arrow-counterclockwise"></i> Limpar filtros</button>
            </div>
        </section>

        <?php if ($usuario): ?>
        <div class="section-divider"></div>

        <!-- ===== MINHAS RESERVAS ===== -->
        <section id="reservas" class="minhas-reservas">
            <div class="titulo-veiculos">
                <span><i class="bi bi-journal-check"></i> Suas locacoes</span>
                <h2>Minhas <strong>Reservas</strong></h2>
                <p>Acompanhe o status das suas reservas de veiculos.</p>
            </div>

            <?php if (empty($minhasReservas)): ?>
                <div id="listaReservas" class="lista-reservas" data-csrf="<?php echo htmlspecialchars($csrfToken); ?>">
                    <p style="text-align:center; color: var(--gray);">Voce ainda nao possui reservas. Escolha um veiculo acima para comecar.</p>
                </div>
            <?php else: ?>
                <div id="listaReservas" class="lista-reservas" data-csrf="<?php echo htmlspecialchars($csrfToken); ?>">
                    <?php foreach ($minhasReservas as $r): ?>
                        <div class="reserva-item" data-reserva-id="<?php echo $r['id']; ?>">
                            <div class="reserva-info">
                                <h4><?php echo htmlspecialchars($r['marca'] . ' ' . $r['modelo']); ?> <span class="status-reserva <?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></h4>
                                <p>
                                    <i class="bi bi-calendar3"></i> <?php echo htmlspecialchars(date('d/m/Y', strtotime($r['data_inicio']))); ?>
                                    ate <?php echo htmlspecialchars(date('d/m/Y', strtotime($r['data_fim']))); ?>
                                    &nbsp;·&nbsp; R$ <?php echo number_format($r['valor_total'], 2, ',', '.'); ?>
                                </p>
                            </div>
                            <div class="reserva-acoes">
                                <?php if (in_array($r['status'], ['pendente', 'confirmada'], true)): ?>
                                    <button class="btn-cancelar-reserva" onclick="handleCancelarReserva(<?php echo $r['id']; ?>)">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <div class="section-divider"></div>

        <!-- ===== NOSSA HISTORIA ===== -->
        <section id="historia" class="about">
            <div class="about-container">
                <div class="about-image">
                    <img src="imagens/botão.png" alt="Nossa Historia" loading="lazy" />
                    <div class="experience">
                        <h2>10+</h2>
                        <span>ANOS</span>
                    </div>
                </div>
                <div class="about-content">
                    <small><i class="bi bi-clock-history"></i> Nossa trajetoria</small>
                    <h2>Nossa <span>Historia</span></h2>
                    <p>Nascemos da conviccao de que alugar um veiculo pode ser uma experiencia simples, elegante e tao bem conduzida quanto o proprio caminho.</p>
                    <div class="info">
                        <div>
                            <div class="icon"><i class="bi bi-compass-fill"></i></div>
                            <div>
                                <h3>Como comecou</h3>
                                <p>Com escuta atenta e uma selecao criteriosa de veiculos para quem valoriza cada detalhe.</p>
                            </div>
                        </div>
                        <div>
                            <div class="icon"><i class="bi bi-bullseye"></i></div>
                            <div>
                                <h3>Nossos objetivos</h3>
                                <p>Oferecer mobilidade confiavel, transparente e alinhada ao ritmo de cada cliente.</p>
                            </div>
                        </div>
                        <div>
                            <div class="icon"><i class="bi bi-eye-fill"></i></div>
                            <div>
                                <h3>Nossa visao</h3>
                                <p>Ser a escolha natural para experiencias de locacao com padrao superior.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="section-divider"></div>

        <!-- ===== PROCESSO ===== -->
        <section id="processo">
            <div class="container2">
                <h2><i class="bi bi-diagram-3-fill"></i> Processo</h2>
                <p>Da escolha a retirada, cada etapa foi desenhada para ser clara e objetiva.</p>
                <div class="processo-passos">
                    <div class="passo">
                        <span class="numero">01</span>
                        <i class="bi bi-car-front-fill"></i>
                        <h3>Escolha o veiculo</h3>
                        <p>Explore a frota e encontre a opcao ideal para sua agenda.</p>
                    </div>
                    <div class="passo">
                        <span class="numero">02</span>
                        <i class="bi bi-person-vcard"></i>
                        <h3>Faca seu cadastro</h3>
                        <p>Informe seus dados de forma segura e conclua seu acesso.</p>
                    </div>
                    <div class="passo">
                        <span class="numero">03</span>
                        <i class="bi bi-pencil-square"></i>
                        <h3>Assine o contrato</h3>
                        <p>Tudo digital, rapido e com total transparencia nas condicoes.</p>
                    </div>
                    <div class="passo">
                        <span class="numero">04</span>
                        <i class="bi bi-key-fill"></i>
                        <h3>Retire o veiculo</h3>
                        <p>Sua jornada comeca aqui com toda a seguranca e suporte.</p>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!$usuario): ?>
        <!-- ===== CADASTRO ===== -->
        <section id="cadastro" class="signup-section">
            <div class="container2">
                <div class="signup-grid">
                    <div class="signup-intro">
                        <span class="section-tag"><i class="bi bi-person-plus-fill"></i> Comece agora</span>
                        <h2>Crie sua <span>conta</span></h2>
                        <p>Junte-se a LocaFacil e tenha acesso a uma experiencia de locacao simplificada e exclusiva.</p>
                        <div class="signup-benefits">
                            <span><i class="bi bi-check-circle-fill"></i> Acesso rapido a reservas</span>
                            <span><i class="bi bi-check-circle-fill"></i> Historico de locacoes</span>
                            <span><i class="bi bi-check-circle-fill"></i> Atendimento prioritario</span>
                        </div>
                    </div>
                    <form class="signup-form" onsubmit="handleCadastro(event)">
                        <?php echo criarCampoCSRF(); ?>
                        <div class="form-group">
                            <label><i class="bi bi-person-fill"></i> Nome Completo</label>
                            <div class="input-icon">
                                <i class="bi bi-person-fill"></i>
                                <input type="text" id="reg-nome" placeholder="Digite seu nome completo" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-envelope-fill"></i> E-mail</label>
                            <div class="input-icon">
                                <i class="bi bi-envelope-fill"></i>
                                <input type="email" id="reg-email" placeholder="Digite seu e-mail" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-telephone-fill"></i> Telefone</label>
                            <div class="input-icon">
                                <i class="bi bi-telephone-fill"></i>
                                <input type="tel" id="reg-telefone" data-mask="telefone" placeholder="(00) 00000-0000" required />
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label><i class="bi bi-lock-fill"></i> Senha</label>
                            <div class="input-icon">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" id="reg-senha" placeholder="Minimo 8 caracteres" required minlength="8" />
                            </div>
                            <small><i class="bi bi-info-circle-fill"></i> Minimo 8 caracteres, com letra maiuscula e numero.</small>
                        </div>
                        <button type="submit" class="btn-submit"><i class="bi bi-person-check-fill"></i> Criar minha conta</button>
                        <p class="form-footer">Ja possui conta? <a href="#" onclick="toggleLogin()">Fazer login</a></p>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ===== LOGIN MODAL ===== -->
        <div id="loginModal" class="login-modal">
            <div class="login-modal-content">
                <button class="close-modal" onclick="toggleLogin()"><i class="bi bi-x-lg"></i></button>
                <h2><i class="bi bi-person-fill"></i> Login</h2>
                <form onsubmit="handleLogin(event)">
                    <?php echo criarCampoCSRF(); ?>
                    <div class="form-group">
                        <label><i class="bi bi-envelope-fill"></i> E-mail</label>
                        <div class="input-icon">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" id="login-email" placeholder="Digite seu e-mail" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Senha</label>
                        <div class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="login-senha" placeholder="Digite sua senha" required />
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
                    <p class="form-footer">
                        <a href="#" onclick="toggleLogin(); toggleEsqueciSenha();">Esqueci minha senha</a>
                        &nbsp;·&nbsp;
                        Nao tem conta? <a href="#cadastro" onclick="toggleLogin()">Cadastre-se</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- ===== ESQUECI MINHA SENHA MODAL ===== -->
        <div id="esqueciSenhaModal" class="login-modal">
            <div class="login-modal-content">
                <button class="close-modal" onclick="toggleEsqueciSenha()"><i class="bi bi-x-lg"></i></button>
                <h2><i class="bi bi-key-fill"></i> Recuperar Senha</h2>
                <form onsubmit="handleEsqueciSenha(event)">
                    <input type="hidden" id="esqueci-csrf" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <div class="form-group">
                        <label><i class="bi bi-envelope-fill"></i> E-mail cadastrado</label>
                        <div class="input-icon">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" id="esqueci-email" placeholder="Digite seu e-mail" required />
                        </div>
                        <small><i class="bi bi-info-circle-fill"></i> Enviaremos um link de redefinicao para este e-mail.</small>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-send-fill"></i> Enviar instrucoes</button>
                </form>
            </div>
        </div>

        <?php if ($usuario): ?>
        <!-- ===== CONFIGURACOES MODAL ===== -->
        <div id="configuracoesModal" class="login-modal">
            <div class="login-modal-content">
                <button class="close-modal" onclick="toggleConfiguracoes()"><i class="bi bi-x-lg"></i></button>
                <h2><i class="bi bi-gear-fill"></i> Configurações da Conta</h2>
                <form onsubmit="handleAtualizarPerfil(event)">
                    <input type="hidden" id="config-csrf" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <div class="form-group">
                        <label><i class="bi bi-person-fill"></i> Nome Completo</label>
                        <div class="input-icon">
                            <i class="bi bi-person-fill"></i>
                            <input type="text" id="config-nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-envelope-fill"></i> E-mail</label>
                        <div class="input-icon">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled />
                        </div>
                        <small><i class="bi bi-info-circle-fill"></i> O e-mail nao pode ser alterado.</small>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-telephone-fill"></i> Telefone</label>
                        <div class="input-icon">
                            <i class="bi bi-telephone-fill"></i>
                            <input type="tel" id="config-telefone" data-mask="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" placeholder="(00) 00000-0000" required />
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="bi bi-lock-fill"></i> Nova Senha (opcional)</label>
                        <div class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="config-nova-senha" placeholder="Deixe em branco para manter a atual" minlength="8" />
                        </div>
                        <small><i class="bi bi-info-circle-fill"></i> Se preenchida: minimo 8 caracteres, com letra maiuscula e numero.</small>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="bi bi-shield-lock-fill"></i> Senha Atual</label>
                        <div class="input-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                            <input type="password" id="config-senha-atual" placeholder="Confirme sua senha atual para salvar" required />
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-check-circle-fill"></i> Salvar Alterações</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== RESERVA MODAL ===== -->
        <div id="reservaModal" class="login-modal">
            <div class="login-modal-content">
                <button class="close-modal" onclick="toggleReserva()"><i class="bi bi-x-lg"></i></button>
                <h2><i class="bi bi-car-front-fill"></i> Alugar Veiculo</h2>
                <form onsubmit="handleReserva(event)">
                    <input type="hidden" id="reserva-csrf" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" id="reserva-veiculo-id">
                    <p id="reserva-veiculo-nome" style="text-align:center; font-weight:600; margin-bottom: 16px;"></p>
                    <div class="form-group">
                        <label><i class="bi bi-calendar-event"></i> Data de retirada</label>
                        <div class="input-icon">
                            <i class="bi bi-calendar-event"></i>
                            <input type="date" id="reserva-data-inicio" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-calendar-check"></i> Data de devolucao</label>
                        <div class="input-icon">
                            <i class="bi bi-calendar-check"></i>
                            <input type="date" id="reserva-data-fim" required />
                        </div>
                    </div>
                    <div class="resumo-reserva" id="resumoReserva" style="display:none;">
                        <div class="linha"><span>Diarias</span><span id="resumo-dias">-</span></div>
                        <div class="linha total"><span>Total</span><span id="resumo-total">R$ 0</span></div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-check-circle-fill"></i> Confirmar Reserva</button>
                </form>
            </div>
        </div>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-colunas">
                <div class="footer-col footer-sobre">
                    <div class="footer-brand">
                        <img src="imagens/carro png.png" alt="LocaFácil" />
                        <span>Loca<span>Fácil</span></span>
                    </div>
                    <p>Locação de veículos com segurança, transparência e atendimento dedicado em cada trajeto.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Links Rápidos</h4>
                    <a href="#home"><i class="bi bi-chevron-right"></i> Home</a>
                    <a href="#veiculos"><i class="bi bi-chevron-right"></i> Veículos</a>
                    <a href="#historia"><i class="bi bi-chevron-right"></i> Nossa História</a>
                    <a href="#processo"><i class="bi bi-chevron-right"></i> Processo</a>
                </div>

                <div class="footer-col">
                    <h4>Contato</h4>
                    <a href="tel:+558740001234"><i class="bi bi-telephone-fill"></i> (87) 4000-1234</a>
                    <a href="mailto:contato@locafacil.com"><i class="bi bi-envelope-fill"></i> contato@locafacil.com</a>
                    <span><i class="bi bi-geo-alt-fill"></i> Buique PE</span>
                    <span><i class="bi bi-clock-fill"></i> Seg a Sab, 8h as 20h</span>
                </div>
            </div>

            <div class="footer-divisor"></div>

            <p class="footer-copyright"><i class="bi bi-c-circle"></i> <?php echo date('Y'); ?> LocaFacil Aluguel de Carros. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/veiculos.js"></script>
</body>
</html>