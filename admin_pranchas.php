<?php
/**
 * ====================================================================
 * FILE: admin_pranchas.php
 * PURPOSE: Manage Boards of a Specific User
 * ====================================================================
 * * This page allows admin to view ALL boards of a
 * specific user and delete them if necessary.
 * * Flow:
 * 1. Admin clicks "Boards" of a user in admin.php
 * 2. Gets redirected to admin_pranchas.php?usuario_id=123
 * 3. Sees all boards of that user
 * 4. Can delete individual boards
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
        'page_title'      => 'Admin - Boards of',
        'back_panel'      => '← Back to Panel',
        'boards_of'       => 'Boards of:',
        'no_boards'       => 'This user has no boards registered.',
        'brand'           => 'Brand:',
        'size'            => 'Size:',
        'volume'          => 'Volume:',
        'remove_board'    => '🗑️ Remove Board',
        'confirm_delete'  => 'Delete this board permanently?'
    ],
    'pt' => [
        'page_title'      => 'Admin - Pranchas de',
        'back_panel'      => '← Voltar ao Painel',
        'boards_of'       => 'Pranchas de:',
        'no_boards'       => 'Este usuário não possui pranchas cadastradas.',
        'brand'           => 'Marca:',
        'size'            => 'Tamanho:',
        'volume'          => 'Volume:',
        'remove_board'    => '🗑️ Remover Prancha',
        'confirm_delete'  => 'Excluir esta prancha permanentemente?'
    ],
    'es' => [
        'page_title'      => 'Admin - Tablas de',
        'back_panel'      => '← Volver al Panel',
        'boards_of'       => 'Tablas de:',
        'no_boards'       => 'Este usuario no tiene tablas registradas.',
        'brand'           => 'Marca:',
        'size'            => 'Tamaño:',
        'volume'          => 'Volumen:',
        'remove_board'    => '🗑️ Eliminar Tabla',
        'confirm_delete'  => '¿Eliminar esta tabla permanentemente?'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

// ============= VALIDATE TARGET USER ID =============
// Gets and validates ID of user whose boards we want to see
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

// ============= DELETE BOARD IF REQUESTED =============
// Checks if there's a delete parameter in URL
if (isset($_GET['deletar_prancha'])) {
    // Gets and validates ID of board to delete
    $prancha_id = filter_input(INPUT_GET, 'deletar_prancha', FILTER_VALIDATE_INT);
    
    // Starts a transaction to ensure data consistency
    $pdo->beginTransaction();
    try {
        // STEP 1: Disconnects board from all sessions that used it
        // Sets NULL in prancha_id field of sessions
        $stmt_null = $pdo->prepare("UPDATE sessoes SET prancha_id = NULL WHERE prancha_id = ?");
        $stmt_null->execute([$prancha_id]);
        
        // STEP 2: Deletes the board
        // Checks double security: board ID AND owner ID
        $stmt_del = $pdo->prepare("DELETE FROM pranchas WHERE id = ? AND usuario_id = ?");
        $stmt_del->execute([$prancha_id, $usuario_alvo_id]);
        
        // Confirms transaction
        $pdo->commit();
        
        // Redirects to reload page without deleted board
        header("Location: admin_pranchas.php?usuario_id=" . $usuario_alvo_id);
        exit;
    } catch (Exception $e) {
        // If error, undo everything
        $pdo->rollBack();
    }
}

// ============= FETCH ALL USER'S BOARDS =============
// Gets all boards of target user ordered alphabetically
$stmt_pranchas = $pdo->prepare("SELECT * FROM pranchas WHERE usuario_id = ? ORDER BY modelo ASC");
$stmt_pranchas->execute([$usuario_alvo_id]);
$pranchas = $stmt_pranchas->fetchAll();
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
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .card h3 { margin: 0 0 10px 0; color: #0f172a; }
        .card p { margin: 5px 0; color: #475569; font-size: 14px; }
        .btn-del { display: inline-block; margin-top: 15px; color: #ef4444; text-decoration: none; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>

    <div class="header-area">
        <div>
            <a href="admin.php" class="btn-back"><?= $txt['back_panel'] ?></a>
            <h1 style="margin-top:15px;"><?= $txt['boards_of'] ?> <?= htmlspecialchars($usuario_alvo['nome']) ?></h1>
        </div>
    </div>

    <?php if(empty($pranchas)): ?>
        <p style="color: #64748b;"><?= $txt['no_boards'] ?></p>
    <?php else: ?>
        <div class="grid">
            <?php foreach($pranchas as $p): ?>
                <div class="card">
                    <h3>🏄‍♂️ <?= htmlspecialchars($p['modelo']) ?></h3>
                    <p><strong><?= $txt['brand'] ?></strong> <?= htmlspecialchars($p['marca']) ?></p>
                    <p><strong><?= $txt['size'] ?></strong> <?= htmlspecialchars($p['tamanho']) ?></p>
                    <p><strong><?= $txt['volume'] ?></strong> <?= htmlspecialchars($p['volume']) ?>L</p>
                    <a href="admin_pranchas.php?usuario_id=<?= $usuario_alvo_id ?>&deletar_prancha=<?= $p['id'] ?>" 
                       class="btn-del" onclick="return confirm('<?= addslashes($txt['confirm_delete']) ?>')"><?= $txt['remove_board'] ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>