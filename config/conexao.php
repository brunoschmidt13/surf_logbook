<?php
/**
 * ====================================================================
 * FILE: config/conexao.php
 * PURPOSE: Database Configuration and Connection
 * ====================================================================
 * 
 * This file centralizes all database connection settings.
 * It is imported via require_once in almost every PHP file.
 * 
 * Uses PDO (PHP Data Objects) for:
 * - Secure and consistent connection
 * - Protection against SQL Injection (prepared statements)
 * - Robust error handling
 * - Compatibility with different databases
 */

// ============= DATABASE SETTINGS =============
// MySQL Server (localhost = local machine)
$host = 'localhost';

// Name of database to be used
$db   = 'surflog';

// MySQL user (XAMPP default is 'root')
$user = 'root';

// User password (XAMPP default is empty)
$pass = '';

// Charset (utf8mb4 supports special characters, emojis, etc)
$charset = 'utf8mb4';

// ============= PDO OPTIONS (Advanced Settings) =============
$options = [
    // PDO::ATTR_ERRMODE = Defines how errors will be handled
    // PDO::ERRMODE_EXCEPTION = Throws exceptions (ideal for try-catch)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // PDO::ATTR_DEFAULT_FETCH_MODE = Defines default format of results
    // PDO::FETCH_ASSOC = Returns associative arrays ['column' => value]
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // PDO::ATTR_EMULATE_PREPARES = Disables prepared statement emulation
    // false = Uses real prepared statements from database (more secure)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ============= BUILD CONNECTION STRING (DSN) =============
// DSN (Data Source Name) = specifies how to connect to database
// Format: "mysql:host=localhost;dbname=surflog;charset=utf8mb4"
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// ============= CREATE PDO CONNECTION =============
try {
    // Tries to create new PDO instance with settings above
    // If successful, $pdo will be available for all queries
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If connection error (database offline, wrong user, etc)
    // Throws exception with error message
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>