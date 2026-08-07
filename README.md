# LocaFacil - Sistema de Aluguel de Carros

## Sobre o Projeto
LocaFacil e um sistema completo de aluguel de veiculos desenvolvido com PHP, MySQL, JavaScript e CSS puro.

## Funcionalidades
- Listagem de veiculos com filtros por categoria
- Sistema de busca por nome
- Cadastro de usuarios
- Login/Logout com sessao
- Tema claro/escuro
- Design responsivo
- Menu mobile
- Protecao CSRF
- Sanitizacao de dados

## Tecnologias Utilizadas
- PHP 7.4+
- MySQL
- PDO
- JavaScript Vanilla
- CSS3
- Bootstrap Icons
- Google Fonts (Poppins)

## Estrutura do Projeto
```
locafacil/
├── index.php              # Pagina principal (SSR + listagem de veiculos)
├── api.php                # Endpoint JSON (cadastro/login/logout)
├── teste_db.php           # Script de teste de conexao com o banco
├── database.sql           # Schema + dados iniciais (MySQL)
├── includes/
│   ├── db.php              # Conexao PDO
│   ├── auth.php            # Cadastro/login/logout/sessao
│   ├── csrf.php            # Geracao/validacao de token CSRF
│   └── sanitize.php        # Sanitizacao e validadores de entrada
├── css/
│   └── veiculos.css        # Estilos (tema claro/escuro, responsivo)
├── js/
│   └── veiculos.js         # Filtros, busca, menu mobile, auth (fetch)
└── imagens/                # Logo, banner e fotos dos veiculos
```