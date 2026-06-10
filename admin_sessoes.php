<?php
/**
 * ====================================================================
 * FILE: admin_sessoes.php
 * PURPOSE: View and Manage Surf Sessions of a User
 * ====================================================================
 * 
 * This page allows admin to:
 * 1. See ALL surf sessions of a specific user
 * 2. View complete details of each session
 * 3. Delete individual sessions from history
 * 
 * Flow:
 * 1. Admin clicks "Sessions" of a user in admin.php
 * 2. Gets redirected to admin_sessoes.php?usuario_id=123
 * 3. Sees table with history of all sessions of that user
 * 4. Can delete sessions via action in table
 * 
 * SECURITY: Requires being admin AND providing valid user ID
 */

// Imports database connection
require_once 'config/conexao.php';

// Starts session to verify permissions
session_start();

// ============= ACCESS VERIFICATION - PROTECTION 1 =============
// Verifies if user is logged in
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Gets logged-in user information to verify if admin
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

// If not admin, redirect
if (!$user_atual || $user_atual['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit;
}

// ============= VALIDATE TARGET USER ID =============
// Gets and validates ID of user whose sessions we want to see
$usuario_alvo_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

// If ID is not valid, redirect to admin.php
if (!$usuario_alvo_id) {
    header("Location: admin.php");
    exit;
}

// ============= FETCH TARGET USER DATA =============
// Gets user name to display on page title
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_alvo_id]);
$usuario_alvo = $stmt_user->fetch();

// ============= DELETE SESSION IF REQUESTED =============
// Checks if there's a delete parameter in URL
if (isset($_GET['deletar_sessao'])) {
    // Gets and validates ID of session to delete
    $sessao_id = filter_input(INPUT_GET, 'deletar_sessao', FILTER_VALIDATE_INT);
    
    // Deletes session (checks double security: session ID AND owner ID)
    $stmt_del = $pdo->prepare("DELETE FROM sessoes WHERE id = ? AND usuario_id = ?");
    $stmt_del->execute([$sessao_id, $usuario_alvo_id]);
    
    // Redirects to reload page without deleted session
    header("Location: admin_sessoes.php?usuario_id=" . $usuario_alvo_id);
    exit;
}

// ============= FETCH ALL USER'S SESSIONS =============
// Gets all sessions combining data with board model used
// LEFT JOIN allows displaying sessions even if board was deleted
// Orders by session date (most recent first)
$stmt_sessoes = $pdo->prepare("
    SELECT s.*, p.modelo AS prancha_nome 
    FROM sessoes s 
    LEFT JOIN pranchas p ON s.prancha_id = p.id 
    WHERE s.usuario_id = ? 
    ORDER BY s.data_sessao DESC
");
$stmt_sessoes->execute([$usuario_alvo_id]);
$sessoes = $stmt_sessoes->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Sessions of <?= htmlspecialchars($usuario_alvo['nome']) ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f1f5f9; margin: 0; padding: 40px; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #64748b; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        
        .table-container { background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; padding: 12px 20px; text-align: left; font-size: 13px; text-transform: uppercase; color: #64748b; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        .btn-del { color: #ef4444; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header-area">
        <div>
            <a href="admin.php" class="btn-back">← Back to Panel</a>
            <h1 style="margin-top:15px;">Surf History: <?= htmlspecialchars($usuario_alvo['nome']) ?></h1>
        </div>
    </div>

    <?php if(empty($sessoes)): ?>
        <p style="color: #64748b;">This user has not recorded any surf sessions yet.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Board</th>
                        <th>Rating</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sessoes as $s): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($s['data_sessao'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($s['praia']) ?></strong> - 
                                <?= htmlspecialchars($s['cidade']) ?>/<?= htmlspecialchars($s['estado']) ?>
                            </td>
                            <td><?= $s['duracao_minutos'] ?> min</td>
                            <td><?= $s['prancha_nome'] ? htmlspecialchars($s['prancha_nome']) : '<span style="color:#94a3b8;">None</span>' ?></td>
                            <td>⭐ <?= number_format($s['nota'], 1) ?></td>
                            <td>
                                <a href="admin_sessoes.php?usuario_id=<?= $usuario_alvo_id ?>&deletar_sessao=<?= $s['id'] ?>" 
                                   class="btn-del" onclick="return confirm('Are you sure you want to delete this surf session from user history?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</body>
</html>