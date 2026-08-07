-- =============================================
-- BANCO DE DADOS: locafacil
-- =============================================

CREATE DATABASE IF NOT EXISTS locafacil 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE locafacil;

-- =============================================
-- TABELA: categorias
-- =============================================
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL UNIQUE,
    descricao VARCHAR(300),
    ativa ENUM('sim', 'nao') DEFAULT 'sim' NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: usuarios
-- =============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    email VARCHAR(320) NOT NULL UNIQUE,
    telefone VARCHAR(24) NOT NULL,
    senhaHash VARCHAR(255) NOT NULL,
    situacao ENUM('ativo', 'inativo') DEFAULT 'ativo' NOT NULL,
    tipo_pessoa ENUM('fisica', 'juridica') DEFAULT 'fisica' NOT NULL,
    nivel_cliente ENUM('comum', 'vip', 'bloqueado') DEFAULT 'comum' NOT NULL,
    token_recuperacao VARCHAR(255) NULL,
    token_expira DATETIME NULL,
    tentativas_login INT DEFAULT 0 NOT NULL,
    bloqueado_ate DATETIME NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_situacao (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tentativas_login INT DEFAULT 0 NOT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS bloqueado_ate DATETIME NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tipo_pessoa ENUM('fisica', 'juridica') DEFAULT 'fisica' NOT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS nivel_cliente ENUM('comum', 'vip', 'bloqueado') DEFAULT 'comum' NOT NULL;

-- =============================================
-- TABELA: veiculos
-- =============================================
CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoriaId INT NOT NULL,
    marca VARCHAR(80) NOT NULL,
    modelo VARCHAR(120) NOT NULL,
    placa VARCHAR(12) NOT NULL UNIQUE,
    ano INT NOT NULL,
    transmissao ENUM('automatico', 'manual') NOT NULL,
    combustivel ENUM('flex', 'gasolina', 'diesel', 'eletrico', 'hibrido') NOT NULL,
    passageiros INT NOT NULL,
    diaria DECIMAL(10, 2) NOT NULL,
    imagemUrl VARCHAR(500),
    descricao TEXT,
    status ENUM('disponivel', 'reservado', 'manutencao', 'inativo') DEFAULT 'disponivel' NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoriaId) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_categoria (categoriaId),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: reservas
-- =============================================
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NOT NULL,
    veiculoId INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'confirmada', 'cancelada', 'finalizada') DEFAULT 'pendente' NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculoId) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuarioId),
    INDEX idx_veiculo (veiculoId),
    INDEX idx_datas (data_inicio, data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: logs_atividades
-- =============================================
CREATE TABLE IF NOT EXISTS logs_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NULL,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuarioId) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_log (usuarioId),
    INDEX idx_acao (acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: motoristas
-- =============================================
CREATE TABLE IF NOT EXISTS motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    email VARCHAR(320) UNIQUE,
    telefone VARCHAR(24) NOT NULL,
    cnh VARCHAR(20) NOT NULL UNIQUE,
    cnh_categoria VARCHAR(5) NOT NULL,
    cnh_validade DATE NOT NULL,
    situacao ENUM('ativo', 'inativo') DEFAULT 'ativo' NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_situacao_motorista (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: funcionarios
-- =============================================
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    email VARCHAR(320) NOT NULL UNIQUE,
    telefone VARCHAR(24),
    senhaHash VARCHAR(255) NOT NULL,
    cargo ENUM('admin', 'gerente', 'atendente') DEFAULT 'atendente' NOT NULL,
    situacao ENUM('ativo', 'inativo') DEFAULT 'ativo' NOT NULL,
    tentativas_login INT DEFAULT 0 NOT NULL,
    bloqueado_ate DATETIME NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email_funcionario (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS tentativas_login INT DEFAULT 0 NOT NULL;
ALTER TABLE funcionarios ADD COLUMN IF NOT EXISTS bloqueado_ate DATETIME NULL;

-- =============================================
-- TABELA: funcionario_permissoes
-- Privilegios granulares por modulo (ignorados para cargo='admin', que tem acesso total)
-- =============================================
CREATE TABLE IF NOT EXISTS funcionario_permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionarioId INT NOT NULL,
    modulo ENUM('veiculos', 'motoristas', 'usuarios', 'funcionarios', 'reservas') NOT NULL,
    pode_ver TINYINT(1) DEFAULT 0 NOT NULL,
    pode_criar TINYINT(1) DEFAULT 0 NOT NULL,
    pode_editar TINYINT(1) DEFAULT 0 NOT NULL,
    pode_deletar TINYINT(1) DEFAULT 0 NOT NULL,
    FOREIGN KEY (funcionarioId) REFERENCES funcionarios(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_funcionario_modulo (funcionarioId, modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE funcionario_permissoes MODIFY modulo ENUM('veiculos', 'motoristas', 'usuarios', 'funcionarios', 'reservas') NOT NULL;

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Inserir Categorias
INSERT INTO categorias (nome, slug, descricao) VALUES 
('Sedan', 'sedan', 'Carros confortaveis e espacosos ideais para viagens e uso diario.'),
('SUV', 'suv', 'Veiculos utilitarios esportivos com alto desempenho e espaco.'),
('Esportivo', 'esportivo', 'Carros de alta performance para momentos especiais.'),
('Pickup', 'pickup', 'Veiculos robustos para trabalho e aventura.'),
('Hatch', 'hatch', 'Carros compactos, ageis e economicos para a cidade.')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inserir Veiculos
INSERT INTO veiculos (id, categoriaId, marca, modelo, placa, ano, transmissao, combustivel, passageiros, diaria, descricao) VALUES 
(1, 1, 'Honda', 'Civic Touring', 'ABC-1234', 2025, 'automatico', 'flex', 5, 300.00, 'Precisao e conforto para trajetos urbanos e executivos.'),
(2, 2, 'Jeep', 'Compass', 'DEF-5678', 2025, 'automatico', 'flex', 5, 400.00, 'Versatilidade sofisticada para descobrir novos caminhos.'),
(3, 3, 'Porsche', '911', 'GHI-9012', 2025, 'automatico', 'gasolina', 2, 900.00, 'Um icone de desempenho para ocasioes inesqueciveis.'),
(4, 4, 'Toyota', 'Hilux', 'JKL-3456', 2025, 'automatico', 'diesel', 5, 900.00, 'Robustez refinada para trabalho, lazer e aventura.'),
(5, 5, 'VW', 'Golf', 'MNO-7890', 2024, 'automatico', 'gasolina', 5, 250.00, 'Agilidade, design e prazer ao dirigir em equilibrio.'),
(6, 2, 'Mercedes-Benz', 'Classe S', 'PQR-1234', 2025, 'automatico', 'hibrido', 5, 1200.00, 'Presenca marcante, acabamento premium e tecnologia.')
ON DUPLICATE KEY UPDATE modelo = VALUES(modelo);

-- Usuario Admin (Senha: Admin@123)
INSERT INTO usuarios (nome, email, telefone, senhaHash, situacao)
VALUES ('Administrador', 'admin@locafacil.com', '(11) 99999-9999', '$2y$10$lojWHR7EsntrFxoA7PFOnu2cOYn2f9zK6DVx1zXJMVKxUQEk0Cp6q', 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), senhaHash = VALUES(senhaHash), tentativas_login = 0, bloqueado_ate = NULL;

-- Motoristas (frota interna)
INSERT INTO motoristas (nome, email, telefone, cnh, cnh_categoria, cnh_validade) VALUES
('Carlos Eduardo Silva', 'carlos.silva@locafacil.com', '(11) 98888-1111', '12345678900', 'B', '2028-05-10'),
('Fernanda Oliveira Souza', 'fernanda.souza@locafacil.com', '(11) 98888-2222', '23456789011', 'AB', '2027-11-22')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Funcionario Admin do painel (Senha: Admin@123) - acesso total, ignora funcionario_permissoes
INSERT INTO funcionarios (nome, email, telefone, senhaHash, cargo, situacao)
VALUES ('Administrador do Painel', 'admin@locafacil.com', '(11) 99999-0000', '$2y$10$M1ela98chtn7dIc9HgHJWOc.i0yJwtNYNn.HGERBLYQcgb93h/yfm', 'admin', 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Funcionario Gerente de exemplo (Senha: Gerente@123) - privilegios limitados
INSERT INTO funcionarios (nome, email, telefone, senhaHash, cargo, situacao)
VALUES ('Gerente de Frota', 'gerente@locafacil.com', '(11) 99999-0001', '$2y$10$996sXxIxuUD/VXJbFKufHOYiLVG6o6HHNVMJ0bmm8UUP3TVaaLSTC', 'gerente', 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Privilegios do Gerente de exemplo: gerencia veiculos e motoristas, so visualiza usuarios, sem acesso a funcionarios
INSERT INTO funcionario_permissoes (funcionarioId, modulo, pode_ver, pode_criar, pode_editar, pode_deletar)
SELECT id, 'veiculos', 1, 1, 1, 0 FROM funcionarios WHERE email = 'gerente@locafacil.com'
ON DUPLICATE KEY UPDATE pode_ver = VALUES(pode_ver), pode_criar = VALUES(pode_criar), pode_editar = VALUES(pode_editar), pode_deletar = VALUES(pode_deletar);

INSERT INTO funcionario_permissoes (funcionarioId, modulo, pode_ver, pode_criar, pode_editar, pode_deletar)
SELECT id, 'motoristas', 1, 1, 1, 0 FROM funcionarios WHERE email = 'gerente@locafacil.com'
ON DUPLICATE KEY UPDATE pode_ver = VALUES(pode_ver), pode_criar = VALUES(pode_criar), pode_editar = VALUES(pode_editar), pode_deletar = VALUES(pode_deletar);

INSERT INTO funcionario_permissoes (funcionarioId, modulo, pode_ver, pode_criar, pode_editar, pode_deletar)
SELECT id, 'usuarios', 1, 0, 0, 0 FROM funcionarios WHERE email = 'gerente@locafacil.com'
ON DUPLICATE KEY UPDATE pode_ver = VALUES(pode_ver), pode_criar = VALUES(pode_criar), pode_editar = VALUES(pode_editar), pode_deletar = VALUES(pode_deletar);

INSERT INTO funcionario_permissoes (funcionarioId, modulo, pode_ver, pode_criar, pode_editar, pode_deletar)
SELECT id, 'reservas', 1, 1, 1, 0 FROM funcionarios WHERE email = 'gerente@locafacil.com'
ON DUPLICATE KEY UPDATE pode_ver = VALUES(pode_ver), pode_criar = VALUES(pode_criar), pode_editar = VALUES(pode_editar), pode_deletar = VALUES(pode_deletar);