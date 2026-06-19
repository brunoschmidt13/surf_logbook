<?php
/**
 * ====================================================================
 * FILE: admin_sessoes.php
 * PURPOSE: View and Manage Surf Sessions of a User
 * ====================================================================
 * * This page allows admin to:
 * 1. See ALL surf sessions of a specific user
 * 2. View complete details of each session
 * 3. Delete individual sessions from history
 * * Flow:
 * 1. Admin clicks "Sessions" of a user in admin.php
 * 2. Gets redirected to admin_sessoes.php?usuario_id=123
 * 3. Sees table with history of all sessions of that user
 * 4. Can delete sessions via action in table
 * * SECURITY: Requires being admin AND providing valid user ID
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

// Lógica de Idioma baseada na sessão
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'page_title'      => 'Admin - Sessions of',
        'back_panel'      => '← Back to Panel',
        'surf_history'    => 'Surf History:',
        'no_sessions'     => 'This user has not recorded any surf sessions yet.',
        'th_date'         => 'Date',
        'th_location'     => 'Location',
        'th_duration'     => 'Duration',
        'th_board'        => 'Board',
        'th_rating'       => 'Rating',
        'th_action'       => 'Action',
        'none'            => 'None',
        'delete'          => 'Delete',
        'confirm_delete'  => 'Are you sure you want to delete this surf session from user history?'
    ],
    'pt' => [
        'page_title'      => 'Admin - Sessões de',
        'back_panel'      => '← Voltar ao Painel',
        'surf_history'    => 'Histórico de Surf:',
        'no_sessions'     => 'Este usuário ainda não registrou nenhuma sessão de surf.',
        'th_date'         => 'Data',
        'th_location'     => 'Localização',
        'th_duration'     => 'Duração',
        'th_board'        => 'Prancha',
        'th_rating'       => 'Nota',
        'th_action'       => 'Ação',
        'none'            => 'Nenhuma',
        'delete'          => 'Excluir',
        'confirm_delete'  => 'Tem certeza de que deseja excluir esta sessão de surf do histórico do usuário?'
    ],
    'es' => [
        'page_title'      => 'Admin - Sesiones de',
        'back_panel'      => '← Volver al Panel',
        'surf_history'    => 'Historial de Surf:',
        'no_sessions'     => 'Este usuario aún no ha registrado ninguna sesión de surf.',
        'th_date'         => 'Fecha',
        'th_location'     => 'Ubicación',
        'th_duration'     => 'Duración',
        'th_board'        => 'Tabla',
        'th_rating'       => 'Nota',
        'th_action'       => 'Acción',
        'none'            => 'Ninguna',
        'delete'          => 'Eliminar',
        'confirm_delete'  => '¿Estás seguro de que deseas eliminar esta sesión de surf del historial del usuario?'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

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
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $txt['page_title'] ?> <?= htmlspecialchars($usuario_alvo['nome']) ?></title>
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
            <a href="admin.php" class="btn-back"><?= $txt['back_panel'] ?></a>
            <h1 style="margin-top:15px;"><?= $txt['surf_history'] ?> <?= htmlspecialchars($usuario_alvo['nome']) ?></h1>
        </div>
    </div>

    <?php if(empty($sessoes)): ?>
        <p style="color: #64748b;"><?= $txt['no_sessions'] ?></p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $txt['th_date'] ?></th>
                        <th><?= $txt['th_location'] ?></th>
                        <th><?= $txt['th_duration'] ?></th>
                        <th><?= $txt['th_board'] ?></th>
                        <th><?= $txt['th_rating'] ?></th>
                        <th><?= $txt['th_action'] ?></th>
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
                            <td><?= $s['prancha_nome'] ? htmlspecialchars($s['prancha_nome']) : '<span style="color:#94a3b8;">' . $txt['none'] . '</span>' ?></td>
                            <td>⭐ <?= number_format($s['nota'], 1) ?></td>
                            <td>
                                <a href="admin_sessoes.php?usuario_id=<?= $usuario_alvo_id ?>&deletar_sessao=<?= $s['id'] ?>" 
                                   class="btn-del" onclick="return confirm('<?= addslashes($txt['confirm_delete']) ?>')"><?= $txt['delete'] ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</body>
</html>