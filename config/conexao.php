<?php
/**
 * ====================================================================
 * FILE: config/conexao.php
 * PURPOSE: Configuração e Conexão com Banco de Dados MySQL
 * ====================================================================
 * 
 * Este arquivo centraliza todas as configurações de conexão com o banco
 * de dados. É importado por require_once em quase todos os arquivos PHP.
 * 
 * Usa PDO (PHP Data Objects) para:
 * - Conexão segura e consistente
 * - Proteção contra SQL Injection (prepared statements)
 * - Tratamento robusto de erros
 * - Compatibilidade com diferentes bancos de dados
 */

// ============= CONFIGURAÇÕES DO BANCO DE DADOS =============
// Servidor MySQL (localhost = máquina local)
$host = 'localhost';

// Nome do banco de dados a ser usado
$db   = 'surflog';

// Usuário do MySQL (XAMPP padrão é 'root')
$user = 'root';

// Senha do usuário (XAMPP padrão é vazio)
$pass = '';

// Charset (utf8mb4 suporta caracteres especiais, emojis, etc)
$charset = 'utf8mb4';

// ============= OPÇÕES PDO (Configurações avançadas) =============
$options = [
    // PDO::ATTR_ERRMODE = Define como erros serão tratados
    // PDO::ERRMODE_EXCEPTION = Lança exceções (ideal para try-catch)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // PDO::ATTR_DEFAULT_FETCH_MODE = Define formato padrão dos resultados
    // PDO::FETCH_ASSOC = Retorna arrays associativos ['coluna' => valor]
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // PDO::ATTR_EMULATE_PREPARES = Desabilita emulação de prepared statements
    // false = Usa prepared statements reais do banco (mais seguro)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ============= CONSTRUIR STRING DE CONEXÃO (DSN) =============
// DSN (Data Source Name) = especifica como conectar ao banco
// Formato: "mysql:host=localhost;dbname=surflog;charset=utf8mb4"
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// ============= CRIAR CONEXÃO PDO =============
try {
    // Tenta criar uma nova instância de PDO com as configurações acima
    // Se bem-sucedido, $pdo ficará disponível para todas as queries
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Se houver erro na conexão (banco offline, usuário errado, etc)
    // Lança uma exceção com a mensagem de erro
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>