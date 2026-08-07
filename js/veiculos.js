// ===== MASCARAS DE INPUT =====
function mascararTelefone(valor) {
    valor = valor.replace(/\D/g, '').slice(0, 11);
    if (valor.length === 0) return '';
    if (valor.length <= 2) return '(' + valor;
    if (valor.length <= 6) return '(' + valor.slice(0, 2) + ') ' + valor.slice(2);
    if (valor.length <= 10) return '(' + valor.slice(0, 2) + ') ' + valor.slice(2, 6) + '-' + valor.slice(6);
    return '(' + valor.slice(0, 2) + ') ' + valor.slice(2, 7) + '-' + valor.slice(7);
}

function aplicarMascaras() {
    document.querySelectorAll('input[data-mask="telefone"]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = mascararTelefone(input.value);
        });
    });
}

aplicarMascaras();

// ===== TEMA CLARO/ESCURO =====
function atualizarIconeTema() {
    const escuro = document.documentElement.classList.contains('dark-theme');
    document.querySelectorAll('.theme-toggle i, .theme-toggle-mobile i').forEach((icon) => {
        icon.className = escuro ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    });
}

function atualizarBannerHero() {
    const hero = document.querySelector('.hero');
    if (!hero) return;
    const escuro = document.documentElement.classList.contains('dark-theme');
    const banner = escuro ? hero.dataset.bannerEscuro : hero.dataset.bannerClaro;
    if (banner) {
        hero.style.backgroundImage = `url('${banner}')`;
    }
}

function toggleTheme() {
    document.documentElement.classList.toggle('dark-theme');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light');
    atualizarIconeTema();
    atualizarBannerHero();
}

if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-theme');
}
document.addEventListener('DOMContentLoaded', () => {
    atualizarIconeTema();
    atualizarBannerHero();
});

// ===== HEADER SCROLL =====
window.addEventListener('scroll', () => {
    const header = document.getElementById('header');
    if (window.scrollY > 50) {
        header.classList.add('ativo');
    } else {
        header.classList.remove('ativo');
    }
});

// ===== BOTAO "VER VEICULOS" =====
document.querySelector('.btn-primary')?.addEventListener('click', (e) => {
    e.preventDefault();
    const target = document.querySelector('#veiculos');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
    }
});

// ===== MENU SANDUICHE =====
const menuToggle = document.getElementById('menuToggle');
const menuMobile = document.getElementById('menu-mobile');

menuToggle?.addEventListener('click', () => {
    menuMobile.classList.toggle('ativo');
    const icon = menuToggle.querySelector('i');
    if (menuMobile.classList.contains('ativo')) {
        icon.className = 'bi bi-x-lg';
    } else {
        icon.className = 'bi bi-list';
    }
});

// Fechar menu ao clicar em um link
document.querySelectorAll('#menu-mobile a').forEach(link => {
    link.addEventListener('click', () => {
        menuMobile.classList.remove('ativo');
        const icon = menuToggle?.querySelector('i');
        if (icon) icon.className = 'bi bi-list';
    });
});

// ===== MODAIS (login, esqueci senha, reserva, configuracoes) =====
const MODAIS = ['loginModal', 'esqueciSenhaModal', 'reservaModal', 'configuracoesModal'];

function toggleModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.toggle('ativo');
    document.body.style.overflow = modal.classList.contains('ativo') ? 'hidden' : 'auto';
}

function toggleLogin() { toggleModal('loginModal'); }
function toggleEsqueciSenha() { toggleModal('esqueciSenhaModal'); }
function toggleReserva() { toggleModal('reservaModal'); }
function toggleConfiguracoes() { toggleModal('configuracoesModal'); }

// ===== MENU DO USUARIO (dropdown) =====
function toggleUserMenu() {
    document.getElementById('userMenu')?.classList.toggle('ativo');
}

function fecharUserMenu() {
    document.getElementById('userMenu')?.classList.remove('ativo');
}

document.addEventListener('click', (e) => {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && userMenu.classList.contains('ativo') && !userMenu.contains(e.target)) {
        fecharUserMenu();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharUserMenu();
    }
});

// Fechar modal ao clicar fora
MODAIS.forEach((id) => {
    document.getElementById(id)?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            toggleModal(id);
        }
    });
});

// Fechar modal com ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        MODAIS.forEach((id) => {
            const modal = document.getElementById(id);
            if (modal?.classList.contains('ativo')) {
                toggleModal(id);
            }
        });
    }
});

// ===== FILTROS (BOTOES) =====
const filtros = document.querySelectorAll('.filtros button');
const categoriaSelect = document.getElementById('categoriaSelect');
const pesquisaInput = document.getElementById('pesquisaVeiculo');
const gridVeiculos = document.getElementById('gridVeiculos');
const noResults = document.getElementById('no-results');

let filtroAtual = 'todos';
let pesquisaAtual = '';

function filtrarVeiculos() {
    const cards = gridVeiculos.querySelectorAll('.card-veiculo');
    let visiveis = 0;

    cards.forEach(card => {
        const categoria = card.dataset.categoria;
        const nome = card.querySelector('h3')?.textContent.toLowerCase() || '';
        
        const matchCategoria = filtroAtual === 'todos' || categoria === filtroAtual;
        const matchPesquisa = nome.includes(pesquisaAtual.toLowerCase());

        if (matchCategoria && matchPesquisa) {
            card.style.display = 'block';
            visiveis++;
        } else {
            card.style.display = 'none';
        }
    });

    // Mostrar/ocultar mensagem de nenhum resultado
    if (noResults) {
        noResults.style.display = visiveis === 0 ? 'block' : 'none';
    }
}

// Event listeners dos botoes de filtro
filtros.forEach(botao => {
    botao.addEventListener('click', () => {
        // Remover classe 'ativo' de todos
        filtros.forEach(btn => btn.classList.remove('ativo'));
        // Adicionar ao clicado
        botao.classList.add('ativo');
        // Atualizar filtro
        filtroAtual = botao.dataset.filter;
        // Atualizar select
        if (categoriaSelect) categoriaSelect.value = filtroAtual;
        // Aplicar filtros
        filtrarVeiculos();
    });
});

// Event listener do select
categoriaSelect?.addEventListener('change', (e) => {
    filtroAtual = e.target.value;
    // Atualizar botoes
    filtros.forEach(btn => {
        btn.classList.toggle('ativo', btn.dataset.filter === filtroAtual);
    });
    filtrarVeiculos();
});

// Event listener da pesquisa
pesquisaInput?.addEventListener('input', (e) => {
    pesquisaAtual = e.target.value;
    filtrarVeiculos();
});

// ===== RESET FILTROS =====
function resetFilters() {
    filtroAtual = 'todos';
    pesquisaAtual = '';
    if (pesquisaInput) pesquisaInput.value = '';
    if (categoriaSelect) categoriaSelect.value = 'todos';
    filtros.forEach(btn => {
        btn.classList.toggle('ativo', btn.dataset.filter === 'todos');
    });
    filtrarVeiculos();
}

// ===== ANIMACOES DOS CARDS =====
document.querySelectorAll('.card-veiculo').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
});

// ===== AUTH HANDLERS =====
async function handleCadastro(e) {
    e.preventDefault();
    const nome = document.getElementById('reg-nome').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const telefone = document.getElementById('reg-telefone').value.trim();
    const senha = document.getElementById('reg-senha').value;
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    if (nome.length < 2) {
        alert('Nome deve ter pelo menos 2 caracteres.');
        return;
    }

    if (senha.length < 8) {
        alert('A senha deve ter pelo menos 8 caracteres.');
        return;
    }

    try {
        const res = await fetch('api.php?action=cadastrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, email, telefone, senha, csrf_token: csrfToken })
        });
        const data = await res.json();

        if (data.sucesso) {
            alert('Cadastro realizado com sucesso! Bem-vindo, ' + data.nome);
            location.reload();
        } else {
            alert(data.mensagem || 'Erro ao realizar cadastro.');
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;
    const csrfToken = document.querySelector('#loginModal input[name="csrf_token"]').value;

    if (!email || !senha) {
        alert('Preencha todos os campos.');
        return;
    }

    try {
        const res = await fetch('api.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, senha, csrf_token: csrfToken })
        });
        const data = await res.json();

        if (data.sucesso) {
            alert('Login realizado com sucesso! Olá, ' + data.nome);
            location.reload();
        } else {
            alert(data.mensagem || 'E-mail ou senha incorretos.');
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

async function logout() {
    try {
        const res = await fetch('api.php?action=logout');
        const data = await res.json();
        if (data.sucesso) {
            location.reload();
        }
    } catch (error) {
        alert('Erro ao realizar logout.');
    }
}

// ===== CONFIGURACOES DA CONTA =====
async function handleAtualizarPerfil(e) {
    e.preventDefault();
    const nome = document.getElementById('config-nome').value.trim();
    const telefone = document.getElementById('config-telefone').value.trim();
    const novaSenha = document.getElementById('config-nova-senha').value;
    const senhaAtual = document.getElementById('config-senha-atual').value;
    const csrfToken = document.getElementById('config-csrf').value;

    try {
        const res = await fetch('api.php?action=atualizar_perfil', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, telefone, novaSenha, senhaAtual, csrf_token: csrfToken })
        });
        const data = await res.json();

        if (data.sucesso) {
            alert(data.mensagem || 'Dados atualizados com sucesso.');
            location.reload();
        } else {
            alert(data.mensagem || 'Nao foi possivel atualizar os dados.');
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

// ===== RESERVAS =====
let reservaDiaria = 0;

function abrirReserva(veiculoId, nomeVeiculo, diaria) {
    reservaDiaria = diaria;
    document.getElementById('reserva-veiculo-id').value = veiculoId;
    document.getElementById('reserva-veiculo-nome').textContent = nomeVeiculo;

    const hoje = new Date().toISOString().split('T')[0];
    const dataInicio = document.getElementById('reserva-data-inicio');
    const dataFim = document.getElementById('reserva-data-fim');
    dataInicio.min = hoje;
    dataInicio.value = '';
    dataFim.value = '';
    document.getElementById('resumoReserva').style.display = 'none';

    toggleModal('reservaModal');
}

function atualizarResumoReserva() {
    const dataInicio = document.getElementById('reserva-data-inicio').value;
    const dataFim = document.getElementById('reserva-data-fim').value;
    const resumo = document.getElementById('resumoReserva');

    if (!dataInicio || !dataFim) {
        resumo.style.display = 'none';
        return;
    }

    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);
    const dias = Math.round((fim - inicio) / 86400000);

    if (dias <= 0) {
        resumo.style.display = 'none';
        return;
    }

    const total = dias * reservaDiaria;
    document.getElementById('resumo-dias').textContent = dias + (dias === 1 ? ' diaria' : ' diarias');
    document.getElementById('resumo-total').textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    resumo.style.display = 'block';
}

document.getElementById('reserva-data-inicio')?.addEventListener('change', (e) => {
    document.getElementById('reserva-data-fim').min = e.target.value;
    atualizarResumoReserva();
});
document.getElementById('reserva-data-fim')?.addEventListener('change', atualizarResumoReserva);

async function handleReserva(e) {
    e.preventDefault();
    const veiculoId = document.getElementById('reserva-veiculo-id').value;
    const dataInicio = document.getElementById('reserva-data-inicio').value;
    const dataFim = document.getElementById('reserva-data-fim').value;
    const csrfToken = document.getElementById('reserva-csrf').value;

    if (!dataInicio || !dataFim) {
        alert('Selecione as datas de retirada e devolucao.');
        return;
    }

    try {
        const res = await fetch('api.php?action=reservar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ veiculoId, data_inicio: dataInicio, data_fim: dataFim, csrf_token: csrfToken })
        });
        const data = await res.json();

        if (data.sucesso) {
            alert(data.mensagem || 'Reserva realizada com sucesso!');
            location.reload();
        } else {
            alert(data.mensagem || 'Nao foi possivel concluir a reserva.');
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

async function handleCancelarReserva(id) {
    if (!confirm('Deseja realmente cancelar esta reserva?')) {
        return;
    }

    const csrfToken = document.getElementById('listaReservas')?.dataset.csrf;

    try {
        const res = await fetch('api.php?action=cancelar_reserva', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, csrf_token: csrfToken })
        });
        const data = await res.json();

        if (data.sucesso) {
            location.reload();
        } else {
            alert(data.mensagem || 'Nao foi possivel cancelar a reserva.');
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

// ===== RECUPERACAO DE SENHA =====
async function handleEsqueciSenha(e) {
    e.preventDefault();
    const email = document.getElementById('esqueci-email').value.trim();
    const csrfToken = document.getElementById('esqueci-csrf').value;

    try {
        const res = await fetch('api.php?action=esqueci_senha', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, csrf_token: csrfToken })
        });
        const data = await res.json();
        alert(data.mensagem || 'Se o e-mail existir, enviaremos instrucoes.');
        if (data.sucesso) {
            toggleEsqueciSenha();
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

async function handleRedefinirSenha(e) {
    e.preventDefault();
    const token = document.getElementById('token').value;
    const csrfToken = document.getElementById('csrf_token_redefinir').value;
    const novaSenha = document.getElementById('nova-senha').value;
    const confirmarSenha = document.getElementById('confirmar-senha').value;

    if (novaSenha !== confirmarSenha) {
        alert('As senhas nao coincidem.');
        return;
    }

    try {
        const res = await fetch('api.php?action=redefinir_senha', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, senha: novaSenha, csrf_token: csrfToken })
        });
        const data = await res.json();
        alert(data.mensagem || 'Erro ao redefinir senha.');
        if (data.sucesso) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        alert('Erro de conexão. Tente novamente.');
    }
}

// ===== INICIALIZACAO =====
document.addEventListener('DOMContentLoaded', () => {
    // Garantir que o filtro 'Todos' esteja ativo
    const btnTodos = document.querySelector('.filtros button[data-filter="todos"]');
    if (btnTodos) {
        btnTodos.classList.add('ativo');
    }
    filtrarVeiculos();
});