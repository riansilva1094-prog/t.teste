<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

$usuario = getUsuarioLogado();

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
    <link rel="stylesheet" href="php.css">
    
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
                <a href="#cadastro"><i class="bi bi-person-plus-fill"></i> Cadastro</a>
                <?php if ($usuario): ?>
                    <a href="javascript:void(0)" onclick="logout()" class="btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                <?php else: ?>
                    <a href="#login" onclick="toggleLogin()"><i class="bi bi-person-fill"></i> Login</a>
                <?php endif; ?>
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
            <a href="#cadastro"><i class="bi bi-person-plus-fill"></i> Cadastro</a>
            <?php if ($usuario): ?>
                <a href="javascript:void(0)" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Sair</a>
            <?php else: ?>
                <a href="#login" onclick="toggleLogin()"><i class="bi bi-person-fill"></i> Login</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <!-- ===== HOME / HERO ===== -->
        <section id="home" class="hero" style="background-image: url('imagens/banner.jpg');">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="subtitulo"><i class="bi bi-stars"></i> LOCACAO PREMIUM</span>
                <h1>O veiculo certo para uma <span>jornada excepcional.</span></h1>
                <p>Uma frota selecionada, condicoes transparentes e atendimento dedicado para que cada trajeto seja memoravel.</p>
                <div class="hero-buttons">
                    <a href="#veiculos" class="btn-primary">
                        <i class="bi bi-car-front-fill"></i> Ver veiculos
                    </a>
                    <a href="#cadastro" class="btn-secondary">
                        <i class="bi bi-person-plus-fill"></i> Cadastre-se
                    </a>
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
                                <a href="#" onclick="alert('Funcionalidade em desenvolvimento')" data-id="<?php echo $v['id']; ?>"><i class="bi bi-arrow-right-circle-fill"></i> Alugar Agora</a>
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
                    <?php if (!$usuario): ?>
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
                                <input type="tel" id="reg-telefone" placeholder="(00) 00000-0000" required />
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
                    <?php else: ?>
                    <div class="welcome-card">
                        <i class="bi bi-person-check-fill"></i>
                        <h3>Bem-vindo(a), <?php echo htmlspecialchars($usuario['nome']); ?>!</h3>
                        <p>Voce ja esta logado na LocaFacil.</p>
                        <button onclick="logout()" class="btn-secondary"><i class="bi bi-box-arrow-right"></i> Sair da conta</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

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
                    <p class="form-footer">Nao tem conta? <a href="#cadastro" onclick="toggleLogin()">Cadastre-se</a></p>
                </form>
            </div>
        </div>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-brand">
                <img src="imagens/carro png.png" alt="LocaFácil" />
                <span>Loca<span>Fácil</span></span>
            </div>
            <p><i class="bi bi-c-circle"></i> <?php echo date('Y'); ?> LocaFacil Aluguel de Carros. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/veiculos.js"></script>
    
    <script>
        // Tema escuro/claro
        function toggleTheme() {
            document.documentElement.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light');
        }

        // Inicializar tema
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        });
    </script>
</body>
</html>