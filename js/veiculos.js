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

// ===== LOGIN MODAL =====
function toggleLogin() {
    const modal = document.getElementById('loginModal');
    modal.classList.toggle('ativo');
    document.body.style.overflow = modal.classList.contains('ativo') ? 'hidden' : 'auto';
}

// Fechar modal ao clicar fora
document.getElementById('loginModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        toggleLogin();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('loginModal');
        if (modal?.classList.contains('ativo')) {
            toggleLogin();
        }
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

// ===== INICIALIZACAO =====
document.addEventListener('DOMContentLoaded', () => {
    // Garantir que o filtro 'Todos' esteja ativo
    const btnTodos = document.querySelector('.filtros button[data-filter="todos"]');
    if (btnTodos) {
        btnTodos.classList.add('ativo');
    }
    filtrarVeiculos();
});