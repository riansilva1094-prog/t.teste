# LocaFacil - Sistema de Aluguel de Carros

## Sobre o Projeto
LocaFacil e um sistema completo de aluguel de veiculos desenvolvido com PHP, MySQL, JavaScript e CSS puro, com site publico e painel administrativo com controle de privilegios.

## Funcionalidades

### Site publico
- Listagem de veiculos com filtros por categoria e busca por nome
- Cadastro/Login/Logout de clientes com sessao
- Reserva de veiculos com verificacao de disponibilidade por periodo
- "Minhas Reservas": acompanhamento e cancelamento de reservas
- Recuperacao de senha por e-mail (link com token de uso unico)
- Bloqueio temporario de conta apos multiplas tentativas de login incorretas
- Tema claro/escuro, design responsivo, menu mobile
- Protecao CSRF e sanitizacao de dados

### Painel administrativo (`/admin`)
- Login de funcionarios com sessao isolada do site publico
- CRUD de veiculos, motoristas, usuarios e reservas (confirmar/cancelar/finalizar)
- CRUD de funcionarios com matriz de privilegios granular por modulo (ver/criar/editar/deletar)
- Guards contra bloqueio acidental: nao permite auto-exclusao nem remover o unico admin ativo
- Bloqueio temporario apos multiplas tentativas de login incorretas

## Tecnologias Utilizadas
- PHP 7.4+
- MySQL / MariaDB
- PDO
- JavaScript Vanilla
- CSS3
- Bootstrap Icons
- Google Fonts (Poppins)

## Estrutura do Projeto
```
locafacil/
├── index.php                  # Pagina principal (SSR + veiculos + reservas)
├── api.php                    # Endpoint JSON (cadastro/login/logout/reservas/recuperacao)
├── redefinir_senha.php        # Pagina de redefinicao de senha (via link com token)
├── database.sql               # Schema + dados iniciais (MySQL)
├── .gitignore
├── includes/
│   ├── db.php                  # Conexao PDO
│   ├── session_init.php        # Configuracao segura de sessao (site publico)
│   ├── auth.php                # Cadastro/login/logout/recuperacao de senha
│   ├── reservas.php             # Criar/listar/cancelar reservas (regras de negocio)
│   ├── csrf.php                # Geracao/validacao de token CSRF
│   └── sanitize.php             # Sanitizacao e validadores de entrada
├── css/
│   └── veiculos.css            # Estilos (tema claro/escuro, responsivo)
├── js/
│   └── veiculos.js             # Filtros, busca, modais, auth e reservas (fetch)
├── imagens/                    # Logo, banner e fotos dos veiculos
└── admin/
    ├── login.php / logout.php / index.php (dashboard)
    ├── veiculos.php + veiculo_form.php
    ├── motoristas.php + motorista_form.php
    ├── usuarios.php + usuario_form.php
    ├── reservas.php + reserva_form.php
    ├── funcionarios.php + funcionario_form.php   # matriz de privilegios
    ├── includes/
    │   ├── admin_session_init.php   # Sessao separada do site publico
    │   ├── admin_auth.php           # Login/permissoes/guards anti-lockout
    │   ├── functions.php            # Flash messages/redirect
    │   ├── layout_topo.php / layout_rodape.php
    │   └── acesso_negado.php
    └── css/admin.css
```

## Configuracao local (XAMPP)
1. Importe `database.sql` no MySQL (cria o banco `locafacil`, tabelas e dados de demonstracao).
2. Acesse `http://localhost/projeto-locafacil/` para o site publico.
3. Acesse `http://localhost/projeto-locafacil/admin/login.php` para o painel administrativo.

### Contas de demonstracao
| Area | E-mail | Senha | Observacao |
|---|---|---|---|
| Site publico | admin@locafacil.com | Admin@123 | Cliente de demonstracao |
| Painel admin | admin@locafacil.com | Admin@123 | Cargo admin, acesso total |
| Painel admin | gerente@locafacil.com | Gerente@123 | Cargo gerente, privilegios limitados |

> A recuperacao de senha usa `mail()` do PHP. Sem um relay SMTP configurado (nao vem configurado por padrao no XAMPP), o e-mail nao e enviado de fato; o link de redefinicao e sempre gravado no log de erros do PHP para permitir testes locais.
