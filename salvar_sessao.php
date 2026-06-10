<?php
/**
 * ====================================================================
 * FILE: salvar_sessao.php
 * PURPOSE: Save New Surf Session (Session Log)
 * ====================================================================
 * 
 * This page receives data of a surf session filled in
 * dashboard form and inserts it in database.
 * 
 * A "surf session" includes:
 * - Session date
 * - Duration (in minutes)
 * - Location (state, city, beach)
 * - Board used
 * - Sea conditions (wave height and period)
 * - Personal rating (how it was)
 * - General notes
 */

// Imports database connection
require_once 'config/conexao.php';

// Starts session to verify if user is logged in
session_start();

// ============= SECURITY VERIFICATION =============
// If user is not logged in (has no ID in session)
if (!isset($_SESSION['usuario_id'])) {
    // Redirect to login page
    header("Location: index.php");
    exit; // Stop execution here
}

// ============= PROCESS FORM SUBMISSION =============
// Verifies if request was a POST (not GET, PUT, etc)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gets logged-in user ID from session
    $usuario_id = $_SESSION['usuario_id'];
    
    // ============= BASIC FIELDS =============
    // Date of surf session (format YYYY-MM-DD)
    $data_sessao     = $_POST['data_sessao'];
    
    // Duration in minutes converted to integer (removes decimals if any)
    $duracao_minutos = intval($_POST['duracao_minutos']);
    
    // Session rating converted to float (allows decimals like 4.5)
    $nota            = floatval($_POST['nota']);
    
    // ============= LOCATION =============
    // State (ex: "Santa Catarina")
    $estado          = $_POST['estado'];
    
    // City (ex: "Imbituba")
    $cidade          = $_POST['cidade'];
    
    // Specific beach (ex: "Rosa Beach - North")
    $praia           = $_POST['praia'];
    
    // ============= NOTES =============
    // Free notes from user about session
    $observacoes     = $_POST['observacoes'];
    
    // ============= BOARD AND SEA CONDITIONS =============
    // ID of board used (can be null if left blank)
    $prancha_id      = !empty($_POST['prancha_id']) ? intval($_POST['prancha_id']) : null;

    // NEW: Wave height in meters (can have decimals like 1.5)
    // If not filled, becomes NULL
    $altura_onda     = !empty($_POST['altura_onda']) ? floatval($_POST['altura_onda']) : null;
    
    // NEW: Wave period in seconds (should be integer like 11)
    // If not filled, becomes NULL
    $periodo_onda    = !empty($_POST['periodo_onda']) ? intval($_POST['periodo_onda']) : null;

    // ============= BASIC VALIDATION =============
    // Verifies if required fields were filled
    if ($data_sessao && $duracao_minutos) {
        // Prepares INSERT query with placeholders (?) for security
        $stmt = $pdo->prepare("
            INSERT INTO sessoes (usuario_id, prancha_id, data_sessao, duracao_minutos, nota, estado, cidade, praia, altura_onda, periodo_onda, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Executes query replacing placeholders with real values
        // The order MUST match the ? in query above
        $stmt->execute([
            $usuario_id,      // Logged-in user ID
            $prancha_id,      // Board ID (can be NULL)
            $data_sessao,     // Session date
            $duracao_minutos, // Duration in minutes
            $nota,            // Session rating
            $estado,          // State
            $cidade,          // City
            $praia,           // Beach
            $altura_onda,     // Wave height in meters (can be NULL)
            $periodo_onda,    // Period in seconds (can be NULL)
            $observacoes      // Free notes
        ]);
    }
}

// ============= REDIRECIONAMENTO =============
// Após salvar (ou tentar salvar), redireciona para o dashboard
// O usuário verá a sessão adicionada imediatamente
header("Location: dashboard.php");

// Interrompe qualquer código posterior
exit;