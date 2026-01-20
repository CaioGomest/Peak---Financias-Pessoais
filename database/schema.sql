-- =====================================================
-- SISTEMA DE GESTÃO FINANCEIRA PESSOAL
-- Estrutura do Banco de Dados MySQL
-- =====================================================

-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS gestao_financeira 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE gestao_financeira;

-- =====================================================
-- TABELAS DE AUTENTICAÇÃO E USUÁRIOS
-- =====================================================

-- Tabela de planos de assinatura
CREATE TABLE planos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    limite_transacoes INT DEFAULT NULL, -- NULL = ilimitado
    limite_categorias INT DEFAULT NULL, -- NULL = ilimitado
    recursos JSON, -- recursos específicos do plano
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255),
    plano_id INT DEFAULT 1, -- Plano gratuito por padrão
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    email_verificado BOOLEAN DEFAULT FALSE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- Tabela de assinaturas
CREATE TABLE assinaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    plano_id INT NOT NULL,
    status ENUM('ativa', 'cancelada', 'expirada', 'pendente') DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    valor_pago DECIMAL(10,2),
    metodo_pagamento VARCHAR(50),
    gateway_transacao_id VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- Tabela de tokens de autenticação
CREATE TABLE tokens_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    tipo ENUM('login', 'reset_senha', 'verificacao_email') NOT NULL,
    expira_em TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- =====================================================
-- TABELAS PRINCIPAIS DO SISTEMA FINANCEIRO
-- =====================================================

-- Tabela de contas bancárias/carteiras
CREATE TABLE contas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('corrente', 'poupanca', 'cartao_credito', 'cartao_debito', 'dinheiro', 'investimento') NOT NULL,
    banco VARCHAR(100),
    saldo_inicial DECIMAL(15,2) DEFAULT 0.00,
    saldo_atual DECIMAL(15,2) DEFAULT 0.00,
    cor VARCHAR(7) DEFAULT '#2196F3', -- Cor em hexadecimal
    ativa BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de categorias
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    icone VARCHAR(50) DEFAULT 'fas fa-circle',
    cor VARCHAR(7) DEFAULT '#666666',
    ativa BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_categoria_usuario (usuario_id, nome, tipo)
);

-- Tabela de transações
CREATE TABLE transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    categoria_id INT,
    conta_id INT NOT NULL,
    data_transacao DATE NOT NULL,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE
);

-- =====================================================
-- TABELAS DE CONFIGURAÇÕES E PREFERÊNCIAS
-- =====================================================

-- Tabela de configurações do usuário
CREATE TABLE configuracoes_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    moeda VARCHAR(3) DEFAULT 'BRL',
    simbolo_moeda VARCHAR(5) DEFAULT 'R$',
    formato_data VARCHAR(10) DEFAULT 'd/m/Y',
    tema ENUM('claro', 'escuro') DEFAULT 'escuro',
    mostrar_saldo BOOLEAN DEFAULT TRUE,
    notificacoes_email BOOLEAN DEFAULT TRUE,
    notificacoes_push BOOLEAN DEFAULT FALSE,
    lembretes BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_config_usuario (usuario_id)
);

-- =====================================================
-- TABELAS AUXILIARES
-- =====================================================

-- Tabela de orçamentos
CREATE TABLE orcamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    valor_limite DECIMAL(15,2) NOT NULL,
    periodo ENUM('mensal', 'anual') DEFAULT 'mensal',
    mes INT, -- 1-12 para orçamento mensal
    ano INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_orcamento (usuario_id, categoria_id, periodo, mes, ano)
);

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para transações (consultas mais frequentes)
CREATE INDEX idx_transacoes_usuario_data ON transacoes(usuario_id, data_transacao);
CREATE INDEX idx_transacoes_categoria ON transacoes(categoria_id);
CREATE INDEX idx_transacoes_conta ON transacoes(conta_id);
CREATE INDEX idx_transacoes_tipo ON transacoes(tipo);

-- Índices para categorias
CREATE INDEX idx_categorias_usuario_tipo ON categorias(usuario_id, tipo);

-- Índices para contas
CREATE INDEX idx_contas_usuario ON contas(usuario_id);

-- Índices para autenticação
CREATE INDEX idx_tokens_usuario ON tokens_auth(usuario_id);
CREATE INDEX idx_tokens_expiracao ON tokens_auth(expira_em);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir planos padrão
INSERT INTO planos (nome, descricao, preco, limite_transacoes, limite_categorias, recursos) VALUES
('Gratuito', 'Plano básico gratuito', 0.00, 100, 10, '{"relatorios_basicos": true, "backup": false, "suporte": "comunidade"}'),
('Premium', 'Plano premium com recursos avançados', 19.90, NULL, NULL, '{"relatorios_avancados": true, "backup": true, "suporte": "prioritario", "metas": true, "orcamentos": true}'),
('Empresarial', 'Plano para pequenas empresas', 49.90, NULL, NULL, '{"multi_usuarios": true, "relatorios_avancados": true, "backup": true, "suporte": "dedicado", "api": true}');

-- =====================================================
-- TRIGGERS PARA ATUALIZAÇÃO AUTOMÁTICA
-- =====================================================

-- Trigger para atualizar saldo das contas após inserção de transação
DELIMITER //
CREATE TRIGGER atualizar_saldo_insert AFTER INSERT ON transacoes
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual + NEW.valor WHERE id = NEW.conta_id;
    ELSEIF NEW.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual - NEW.valor WHERE id = NEW.conta_id;
    END IF;
END//
DELIMITER ;

-- Trigger para atualizar saldo das contas após atualização de transação
DELIMITER //
CREATE TRIGGER atualizar_saldo_update AFTER UPDATE ON transacoes
FOR EACH ROW
BEGIN
    -- Reverter transação antiga
    IF OLD.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual - OLD.valor WHERE id = OLD.conta_id;
    ELSEIF OLD.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual + OLD.valor WHERE id = OLD.conta_id;
    END IF;
    
    -- Aplicar nova transação
    IF NEW.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual + NEW.valor WHERE id = NEW.conta_id;
    ELSEIF NEW.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual - NEW.valor WHERE id = NEW.conta_id;
    END IF;
END//
DELIMITER ;

-- Trigger para atualizar saldo das contas após exclusão de transação
DELIMITER //
CREATE TRIGGER atualizar_saldo_delete AFTER DELETE ON transacoes
FOR EACH ROW
BEGIN
    IF OLD.tipo = 'receita' THEN
        UPDATE contas SET saldo_atual = saldo_atual - OLD.valor WHERE id = OLD.conta_id;
    ELSEIF OLD.tipo = 'despesa' THEN
        UPDATE contas SET saldo_atual = saldo_atual + OLD.valor WHERE id = OLD.conta_id;
    END IF;
END//
DELIMITER ;