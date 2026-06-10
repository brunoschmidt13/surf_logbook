<?php
/**
 * ====================================================================
 * FILE: admin.php
 * PURPOSE: Main Administration Panel - User Control
 * ====================================================================
 * 
 * This is the main admin panel for SurfLog administrators.
 * Provides:
 * 1. List of ALL users in the system
 * 2. View information for each user (name, email, level)
 * 3. Access board data for each user
 * 4. Access session history for each user
 * 5. Promote/Demote users (Admin <-> Common)
 * 6. Delete users completely
 * 
 * SECURITY: Only users with is_admin = 1 can access
 */

// Importa a conexão com o banco de dados
require_once 'config/conexao.php';

// Inicia a sessão para verificar se o usuário está logado
session_start();

// ============= VERIFICAÇÃO DE ACESSO (PROTEÇÃO 1) =============
// Verifica se o usuário está logado (tem ID na sessão)
if (!isset($_SESSION['usuario_id'])) {
    // Se não está logado, redireciona para login
    header("Location: index.php");
    exit;
}

// ============= VERIFICAÇÃO DE PERMISSÃO (PROTEÇÃO 2) =============
// Busca informações do usuário logado para verificar se é admin
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

// Se o usuário não existe ou não é admin (is_admin != 1)
if (!$user_atual || $user_atual['is_admin'] != 1) {
    // Redireciona para o dashboard normal
    header("Location: dashboard.php");
    exit;
}

// ============= BUSCAR TODOS OS USUÁRIOS =============
// Prepara uma query para buscar TODOS os usuários EXCETO o admin logado
// Ordena alfabeticamente por nome
$stmt_usuarios = $pdo->prepare("SELECT id, nome, email, is_admin FROM usuarios WHERE id != ? ORDER BY nome ASC");

// Executa a query passando o ID do admin logado (para excluir dele mesmo)
$stmt_usuarios->execute([$_SESSION['usuario_id']]);

// Obtém todos os resultados como um array de usuarios
$usuarios = $stmt_usuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SurfLog - Admin General Control</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f1f5f9; margin: 0; color: #1e293b; }
        .navbar { background-color: #ffffff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .logo { font-size: 20px; font-weight: bold; color: #0284c7; display: flex; align-items: center; gap: 8px; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 600; margin-left: 20px; font-size: 14px; }
        .nav-links a:hover { color: #0f172a; }
        
        .main-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 28px; color: #0f172a; margin-bottom: 5px; }
        p.subtitle { color: #64748b; margin-bottom: 30px; margin-top: 5px; }
        
        .admin-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-table th { background-color: #f8fafc; padding: 15px 20px; text-align: left; font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; }
        .admin-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-admin { background-color: #fee2e2; color: #ef4444; }
        .badge-user { background-color: #e0f2fe; color: #0369a1; }

        .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid transparent; transition: all 0.2s; }
        
        .btn-data { background-color: #f1f5f9; color: #334155; border-color: #cbd5e1; }
        .btn-data:hover { background-color: #e2e8f0; color: #0f172a; }
        
        .btn-toggle { background-color: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .btn-toggle:hover { background-color: #dcfce7; }
        
        .btn-delete { background-color: #fef2f2; color: #991b1b; border-color: #fca5a5; }
        .btn-delete:hover { background-color: #fee2e2; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo">⚙️ SurfLog Master Admin</div>
        <div class="nav-links">
            <a href="dashboard.php">← Back to App</a>
            <a href="logout.php" style="color: #ef4444;">Log out</a>
        </div>
    </div>

    <div class="main-container">
        <h1>User Control Panel</h1>
        <p class="subtitle">Manage accounts, boards, sessions and navigation history for any surfer.</p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Manage Data</th>
                    <th>Account Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($usuarios)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No other users registered.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if($user['is_admin'] == 1): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admin_pranchas.php?usuario_id=<?= $user['id'] ?>" class="btn-action btn-data">🏄‍♂️ Boards</a>
                                    <a href="admin_sessoes.php?usuario_id=<?= $user['id'] ?>" class="btn-action btn-data">🌊 Sessions</a>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admin_acoes.php?action=toggle_role&id=<?= $user['id'] ?>" class="btn-action btn-toggle">
                                        <?= $user['is_admin'] == 1 ? '📥 Demote' : '👑 Promote' ?>
                                    </a>
                                    <a href="admin_acoes.php?action=delete_user&id=<?= $user['id'] ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('CRITICAL WARNING: Deleting this user will permanently remove the account, ALL boards and ALL sessions in the system. Continue?')">
                                        🗑️ Delete User
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>