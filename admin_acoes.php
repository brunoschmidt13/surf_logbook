<?php
/**
 * ====================================================================
 * FILE: admin_acoes.php
 * PURPOSE: Execute Administration Actions (Promote, Demote, Delete)
 * ====================================================================
 * * This file processes admin actions via GET parameters:
 * * Supported Actions:
 * 1. toggle_role: Switches between Admin and Regular User
 * 2. delete_user: Completely deletes user and all data
 * (boards, sessions, etc)
 * * URL Format:
 * - admin_acoes.php?action=toggle_role&id=123
 * - admin_acoes.php?action=delete_user&id=123
 * * SECURITY: All actions require user to be admin
 */

// Imports database connection
require_once 'config/conexao.php';

// Starts session to verify permissions
session_start();

// ============= MAXIMUM SECURITY VERIFICATION =============
// Verifies if user is logged in (has ID in session)
if (!isset($_SESSION['usuario_id'])) {
    // If not, redirect to login
    header("Location: index.php");
    exit;
}

// Gets logged-in user information to verify if admin
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

// If user doesn't exist or is not admin
if (!$user_atual || $user_atual['is_admin'] != 1) {
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Lógica de Idioma baseada na sessão (Disponível caso queira adicionar mensagens na URL futuramente)
$lang = $_SESSION['lang'] ?? 'en';

// ============= CAPTURE URL PARAMETERS =============
// Gets 'action' parameter that specifies which action to execute
$action = $_GET['action'] ?? '';

// Gets and validates target user ID (must be integer)
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Verifies if both parameters were provided
if ($user_id && $action) {
    
    // ============= ACTION 1: SWITCH ROLE (ADMIN <-> USER) =============
    if ($action === 'toggle_role') {
        // Gets current level of target user
        $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch();
        
        // If user exists
        if ($target_user) {
            // If admin (1), changes to regular user (0)
            // If regular user (0), changes to admin (1)
            $novo_cargo = $target_user['is_admin'] == 1 ? 0 : 1;
            
            // Updates database with new role
            $update = $pdo->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
            $update->execute([$novo_cargo, $user_id]);
        }
    }

    // ============= ACTION 2: DELETE USER AND ALL DATA =============
    if ($action === 'delete_user') {
        // Starts transaction to ensure data integrity
        // If all goes well, confirms (commit)
        // If error, undoes everything (rollback) to not leave orphaned data
        $pdo->beginTransaction();
        try {
            // STEP 1: Deletes all user's surf sessions
            $del_sessoes = $pdo->prepare("DELETE FROM sessoes WHERE usuario_id = ?");
            $del_sessoes->execute([$user_id]);

            // STEP 2: Deletes all user's boards
            $del_pranchas = $pdo->prepare("DELETE FROM pranchas WHERE usuario_id = ?");
            $del_pranchas->execute([$user_id]);

            // STEP 3: Finally, deletes user account
            $del_usuario = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $del_usuario->execute([$user_id]);

            // If reached here without errors, confirms all deletions
            $pdo->commit();
        } catch (Exception $e) {
            // If any error in process
            // Undoes ALL operations to not leave inconsistent data
            $pdo->rollBack();
        }
    }
}

// ============= REDIRECT =============
// After executing action, redirects back to admin panel
// so admin sees updated list
header("Location: admin.php");
exit;