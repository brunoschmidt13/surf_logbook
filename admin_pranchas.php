<?php
// admin_pranchas.php
require_once 'config/conexao.php';
session_start();

// Verificação de Admin
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();
if (!$user_atual || $user_atual['is_admin'] != 1) { header("Location: dashboard.php"); exit; }

// Capturar ID do usuário alvo
$usuario_alvo_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
if (!$usuario_alvo_id) { header("Location: admin.php"); exit; }

// Buscar dados do dono das pranchas
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_alvo_id]);
$usuario_alvo = $stmt_user->fetch();

// Deletar prancha se solicitado pelo admin
if (isset($_GET['deletar_prancha'])) {
    $prancha_id = filter_input(INPUT_GET, 'deletar_prancha', FILTER_VALIDATE_INT);
    
    $pdo->beginTransaction();
    try {
        // Desvincular sessões que usavam essa prancha (coloca null nelas)
        $stmt_null = $pdo->prepare("UPDATE sessoes SET prancha_id = NULL WHERE prancha_id = ?");
        $stmt_null->execute([$prancha_id]);
        
        // Deletar prancha
        $stmt_del = $pdo->prepare("DELETE FROM pranchas WHERE id = ? AND usuario_id = ?");
        $stmt_del->execute([$prancha_id, $usuario_alvo_id]);
        
        $pdo->commit();
        header("Location: admin_pranchas.php?usuario_id=" . $usuario_alvo_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

// Buscar pranchas do usuário alvo
$stmt_pranchas = $pdo->prepare("SELECT * FROM pranchas WHERE usuario_id = ? ORDER BY modelo ASC");
$stmt_pranchas->execute([$usuario_alvo_id]);
$pranchas = $stmt_pranchas->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - Pranchas de <?= htmlspecialchars($usuario_alvo['nome']) ?></title>
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
            <a href="admin.php" class="btn-back">← Voltar ao Painel</a>
            <h1 style="margin-top:15px;">Pranchas de: <?= htmlspecialchars($usuario_alvo['nome']) ?></h1>
        </div>
    </div>

    <?php if(empty($pranchas)): ?>
        <p style="color: #64748b;">Este usuário não possui pranchas cadastradas.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach($pranchas as $p): ?>
                <div class="card">
                    <h3>🏄‍♂️ <?= htmlspecialchars($p['modelo']) ?></h3>
                    <p><strong>Marca:</strong> <?= htmlspecialchars($p['marca']) ?></p>
                    <p><strong>Tamanho:</strong> <?= htmlspecialchars($p['tamanho']) ?></p>
                    <p><strong>Volume:</strong> <?= htmlspecialchars($p['volume']) ?>L</p>
                    <a href="admin_pranchas.php?usuario_id=<?= $usuario_alvo_id ?>&deletar_prancha=<?= $p['id'] ?>" 
                       class="btn-del" onclick="return confirm('Excluir esta prancha permanentemente?')">🗑️ Remover Prancha</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>