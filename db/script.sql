/**
 * ====================================================================
 * SurfLog - Database Creation SQL Script
 * ====================================================================
 * 
 * This script creates ALL tables necessary for SurfLog to function.
 * 
 * HOW TO USE:
 * 1. Open phpMyAdmin at http://localhost/phpmyadmin
 * 2. Create new database called "surflog"
 * 3. Open "SQL" tab of surflog database
 * 4. Copy and paste ALL content of this file
 * 5. Click "Execute" (or Ctrl+Enter)
 * 6. Done! The 3 tables will be created
 * 
 * OR via MySQL Terminal:
 * mysql -u root < script.sql
 */

-- ====================================================================
-- TABLE 1: USERS
-- ====================================================================
-- Stores login information and basic user data

CREATE TABLE usuarios (
    -- Unique identifier and primary key
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- User full name (required)
    nome VARCHAR(255) NOT NULL,
    
    -- Unique email (each user has different email)
    -- UNIQUE ensures no two equal emails can exist
    email VARCHAR(255) NOT NULL UNIQUE,
    
    -- Hashed password with bcrypt (NEVER store in plaintext!)
    -- Typical bcrypt hash has 60 characters
    senha VARCHAR(255) NOT NULL,
    
    -- Administrative permission flag
    -- 0 = Regular user (can add boards and sessions)
    -- 1 = Administrator (can manage all users)
    is_admin TINYINT(1) DEFAULT 0,
    
    -- Auto creation date/time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes to improve search performance
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 2: SURF BOARDS
-- ====================================================================
-- Stores boards registered by each user

CREATE TABLE pranchas (
    -- Unique identifier and primary key
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Reference to owner user
    -- FOREIGN KEY ensures every board belongs to real user
    usuario_id INT NOT NULL,
    
    -- Board model/brand (ex: "Simões Funboard", "Red Board")
    modelo VARCHAR(255),
    
    -- Measurements and volume (ex: "7.11 X 21 X 52L")
    medidas VARCHAR(255),
    
    -- Auto creation date/time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key: prancha_id points to usuario.id
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Indexes to improve search performance
    INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 3: SURF SESSIONS
-- ====================================================================
-- Stores each surf session (when, where, how it was, etc)

CREATE TABLE sessoes (
    -- Unique identifier and primary key
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Reference to user who did the session
    usuario_id INT NOT NULL,
    
    -- Reference to board used (can be NULL if not informed)
    -- ON DELETE SET NULL = If board deleted, session.prancha_id becomes NULL
    prancha_id INT,
    
    -- Date when surf session occurred (ex: 2026-06-09)
    data_sessao DATE NOT NULL,
    
    -- Session duration in minutes (ex: 120 = 2 hours)
    duracao_minutos INT,
    
    -- Personal session rating (1-5 stars)
    -- Can be float to allow 4.5, 3.5, etc
    nota FLOAT,
    
    -- Location: State (ex: "SC", "Santa Catarina")
    estado VARCHAR(100),
    
    -- Location: City (ex: "Imbituba")
    cidade VARCHAR(100),
    
    -- Location: Specific beach (ex: "Rosa Beach - North")
    praia VARCHAR(255),
    
    -- Sea condition: Wave height in meters
    -- Can be NULL if not informed
    -- Allows decimals (1.5 meters, 0.8 meters, etc)
    altura_onda FLOAT,
    
    -- Sea condition: Wave period in seconds
    -- Can be NULL if not informed
    -- Typically integer (8, 9, 11, 13 seconds)
    periodo_onda INT,
    
    -- Free text field for general notes
    -- Ex: "Crowded", "Great session!", "Frequent wipeouts"
    observacoes TEXT,
    
    -- Auto creation date/time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key for user
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Foreign key for board
    -- ON DELETE SET NULL = If board deleted, session.prancha_id becomes NULL
    FOREIGN KEY (prancha_id) REFERENCES pranchas(id) ON DELETE SET NULL,
    
    -- Indexes to improve search performance
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_prancha_id (prancha_id),
    INDEX idx_data_sessao (data_sessao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- END OF SQL SCRIPT
-- ====================================================================

-- After running this script, database will be ready to use!
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
