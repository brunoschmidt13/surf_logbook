<?php
/**
 * ====================================================================
 * FILE: admin.php
 * PURPOSE: Main Administration Panel - User Control
 * ====================================================================
 * * This is the main admin panel for SurfLog administrators.
 * Provides:
 * 1. List of ALL users in the system
 * 2. View information for each user (name, email, level)
 * 3. Access board data for each user
 * 4. Access session history for each user
 * 5. Promote/Demote users (Admin <-> Common)
 * 6. Delete users completely
 * * SECURITY: Only users with is_admin = 1 can access
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

// Lógica de Idioma baseada na sessão
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'nav_title'       => '⚙️ SurfLog Master Admin',
        'back_app'        => '← Back to App',
        'logout'          => 'Log out',
        'panel_title'     => 'User Control Panel',
        'panel_subtitle'  => 'Manage accounts, boards, sessions and navigation history for any surfer.',
        'th_name'         => 'Name',
        'th_email'        => 'Email',
        'th_level'        => 'Level',
        'th_manage_data'  => 'Manage Data',
        'th_actions'      => 'Account Actions',
        'no_users'        => 'No other users registered.',
        'badge_admin'     => 'Admin',
        'badge_user'      => 'User',
        'btn_boards'      => '🏄‍♂️ Boards',
        'btn_sessions'    => '🌊 Sessions',
        'btn_demote'      => '📥 Demote',
        'btn_promote'     => '👑 Promote',
        'btn_delete'      => '🗑️ Delete User',
        'confirm_delete'  => 'CRITICAL WARNING: Deleting this user will permanently remove the account, ALL boards and ALL sessions in the system. Continue?'
    ],
    'pt' => [
        'nav_title'       => '⚙️ SurfLog Admin Master',
        'back_app'        => '← Voltar ao App',
        'logout'          => 'Sair',
        'panel_title'     => 'Painel de Controle de Usuários',
        'panel_subtitle'  => 'Gerencie contas, pranchas, sessões e histórico de navegação de qualquer surfista.',
        'th_name'         => 'Nome',
        'th_email'        => 'E-mail',
        'th_level'        => 'Nível',
        'th_manage_data'  => 'Gerenciar Dados',
        'th_actions'      => 'Ações de Conta',
        'no_users'        => 'Nenhum outro usuário cadastrado.',
        'badge_admin'     => 'Admin',
        'badge_user'      => 'Usuário',
        'btn_boards'      => '🏄‍♂️ Pranchas',
        'btn_sessions'    => '🌊 Sessões',
        'btn_demote'      => '📥 Rebaixar',
        'btn_promote'     => '👑 Promover',
        'btn_delete'      => '🗑️ Excluir Usuário',
        'confirm_delete'  => 'AVISO CRÍTICO: Excluir este usuário removerá permanentemente a conta, TODAS as pranchas e TODAS as sessões no sistema. Continuar?'
    ],
    'es' => [
        'nav_title'       => '⚙️ SurfLog Admin Master',
        'back_app'        => '← Volver al App',
        'logout'          => 'Cerrar sesión',
        'panel_title'     => 'Panel de Control de Usuarios',
        'panel_subtitle'  => 'Gestione cuentas, tablas, sesiones e historial de navegación de cualquier surfista.',
        'th_name'         => 'Nombre',
        'th_email'        => 'Correo electrónico',
        'th_level'        => 'Nivel',
        'th_manage_data'  => 'Gestionar Datos',
        'th_actions'      => 'Acciones de Cuenta',
        'no_users'        => 'No hay otros usuarios registrados.',
        'badge_admin'     => 'Admin',
        'badge_user'      => 'Usuario',
        'btn_boards'      => '🏄‍♂️ Tablas',
        'btn_sessions'    => '🌊 Sesiones',
        'btn_demote'      => '📥 Degradar',
        'btn_promote'     => '👑 Promover',
        'btn_delete'      => '🗑️ Eliminar Usuario',
        'confirm_delete'  => 'ADVERTENCIA CRÍTICA: Eliminar este usuario eliminará permanentemente la cuenta, TODAS las tablas y TODAS las sesiones en el sistema. ¿Continuar?'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

// ============= BUSCAR TODOS OS USUÁRIOS =============
// Prepara uma query para buscar TODOS os usuários EXCETO o admin logado
// Ordena alfabeticamente por nome
$stmt_usuarios = $pdo->prepare("SELECT id, nome, email, is_admin FROM usuarios WHERE id != ? ORDER BY nome ASC");

// Executa a query passando o ID do admin logado (para excluir dele mesmo)
$stmt_usuarios->execute([$_SESSION['usuario_id']]);

// Obtem todos os resultados como um array de usuarios
$usuarios = $stmt_usuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
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
        <div class="logo"><?= $txt['nav_title'] ?></div>
        <div class="nav-links">
            <a href="dashboard.php"><?= $txt['back_app'] ?></a>
            <a href="logout.php" style="color: #ef4444;"><?= $txt['logout'] ?></a>
        </div>
    </div>

    <div class="main-container">
        <h1><?= $txt['panel_title'] ?></h1>
        <p class="subtitle"><?= $txt['panel_subtitle'] ?></p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= $txt['th_name'] ?></th>
                    <th><?= $txt['th_email'] ?></th>
                    <th><?= $txt['th_level'] ?></th>
                    <th><?= $txt['th_manage_data'] ?></th>
                    <th><?= $txt['th_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($usuarios)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;"><?= $txt['no_users'] ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if($user['is_admin'] == 1): ?>
                                    <span class="badge badge-admin"><?= $txt['badge_admin'] ?></span>
                                <?php else: ?>
                                    <span class="badge badge-user"><?= $txt['badge_user'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admin_pranchas.php?usuario_id=<?= $user['id'] ?>" class="btn-action btn-data"><?= $txt['btn_boards'] ?></a>
                                    <a href="admin_sessoes.php?usuario_id=<?= $user['id'] ?>" class="btn-action btn-data"><?= $txt['btn_sessions'] ?></a>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admin_acoes.php?action=toggle_role&id=<?= $user['id'] ?>" class="btn-action btn-toggle">
                                        <?= $user['is_admin'] == 1 ? $txt['btn_demote'] : $txt['btn_promote'] ?>
                                    </a>
                                    <a href="admin_acoes.php?action=delete_user&id=<?= $user['id'] ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('<?= addslashes($txt['confirm_delete']) ?>')">
                                        <?= $txt['btn_delete'] ?>
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