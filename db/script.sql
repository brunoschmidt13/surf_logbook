/**
 * ====================================================================
 * SurfLog - Script SQL de Criação do Banco de Dados
 * ====================================================================
 * 
 * Este script cria TODAS as tabelas necessárias para o SurfLog funcionar.
 * 
 * COMO USAR:
 * 1. Abra phpMyAdmin em http://localhost/phpmyadmin
 * 2. Crie um novo banco de dados chamado "surflog"
 * 3. Abra a aba "SQL" do banco surflog
 * 4. Copie e cole TODO o conteúdo deste arquivo
 * 5. Clique em "Executar" (ou Ctrl+Enter)
 * 6. Pronto! As 3 tabelas serão criadas
 * 
 * OU via Terminal MySQL:
 * mysql -u root < script.sql
 */

-- ====================================================================
-- TABELA 1: USUÁRIOS
-- ====================================================================
-- Armazena informações de login e dados básicos dos usuários

CREATE TABLE usuarios (
    -- Identificador único e chave primária
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nome completo do usuário (obrigatório)
    nome VARCHAR(255) NOT NULL,
    
    -- Email único (cada usuário tem um email diferente)
    -- UNIQUE garante que não pode haver dois emails iguais
    email VARCHAR(255) NOT NULL UNIQUE,
    
    -- Senha hasheada com bcrypt (NUNCA armazenar em plaintext!)
    -- Hash bcrypt típico tem 60 caracteres
    senha VARCHAR(255) NOT NULL,
    
    -- Flag de permissão administrativa
    -- 0 = Usuário comum (pode adicionar pranchas e sessões)
    -- 1 = Administrador (pode gerenciar todos os usuários)
    is_admin TINYINT(1) DEFAULT 0,
    
    -- Data/hora de criação automática
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices para melhorar performance em buscas
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 2: PRANCHAS DE SURF
-- ====================================================================
-- Armazena as pranchas cadastradas por cada usuário

CREATE TABLE pranchas (
    -- Identificador único e chave primária
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Referência para o usuário proprietário da prancha
    -- FOREIGN KEY garante que toda prancha pertence a um usuário real
    usuario_id INT NOT NULL,
    
    -- Modelo/marca da prancha (ex: "Simões Funboard", "Prancha Vermelha")
    modelo VARCHAR(255),
    
    -- Medidas e volume (ex: "7.11 X 21 X 52L")
    medidas VARCHAR(255),
    
    -- Data/hora de cadastro automática
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chave estrangeira: prancha_id aponta para usuario.id
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices para melhorar performance em buscas
    INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 3: SESSÕES DE SURF
-- ====================================================================
-- Armazena cada sessão de surf (quando, onde, como foi, etc)

CREATE TABLE sessoes (
    -- Identificador único e chave primária
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Referência para o usuário que realizou a sessão
    usuario_id INT NOT NULL,
    
    -- Referência para a prancha utilizada (pode ser NULL se não informada)
    -- ON DELETE SET NULL = Se prancha for deletada, session.prancha_id vira NULL
    prancha_id INT,
    
    -- Data em que ocorreu a sessão de surf (ex: 2026-06-09)
    data_sessao DATE NOT NULL,
    
    -- Duração em minutos da sessão (ex: 120 = 2 horas)
    duracao_minutos INT,
    
    -- Nota pessoal da sessão (1-5 estrelas)
    -- Pode ser float para permitir 4.5, 3.5, etc
    nota FLOAT,
    
    -- Localização: Estado (ex: "SC", "Santa Catarina")
    estado VARCHAR(100),
    
    -- Localização: Cidade (ex: "Imbituba")
    cidade VARCHAR(100),
    
    -- Localização: Praia específica (ex: "Praia do Rosa - Norte")
    praia VARCHAR(255),
    
    -- Condição do mar: Altura da onda em metros
    -- Pode ser NULL se não informado
    -- Permite decimais (1.5 metros, 0.8 metros, etc)
    altura_onda FLOAT,
    
    -- Condição do mar: Período da onda em segundos
    -- Pode ser NULL se não informado
    -- Tipicamente inteiro (8, 9, 11, 13 segundos)
    periodo_onda INT,
    
    -- Campo de texto livre para observações gerais
    -- Ex: "Muita multidão", "Ótima sessão!", "Quedas frequentes"
    observacoes TEXT,
    
    -- Data/hora de criação automática
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chave estrangeira para usuário
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Chave estrangeira para prancha
    -- ON DELETE SET NULL = Se prancha for deletada, session.prancha_id vira NULL
    FOREIGN KEY (prancha_id) REFERENCES pranchas(id) ON DELETE SET NULL,
    
    -- Índices para melhorar performance em buscas
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_prancha_id (prancha_id),
    INDEX idx_data_sessao (data_sessao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- FIM DO SCRIPT SQL
-- ====================================================================

-- Após executar este script, o banco estará pronto para usar!
-- Você pode verificar as tabelas criadas executando:
-- SHOW TABLES;

-- E verificar a estrutura de cada tabela com:
-- DESCRIBE usuarios;
-- DESCRIBE pranchas;
-- DESCRIBE sessoes;

-- NEXT STEPS:
-- 1. Acesse http://localhost/surf_logbook/index.php
-- 2. Crie uma conta nova (Sign Up)
-- 3. Faça login
-- 4. Comece a adicionar pranchas e sessões de surf!
-- 5. (Opcional) Promova-se a admin via phpMyAdmin:
--    UPDATE usuarios SET is_admin = 1 WHERE email = 'seu@email.com';
