<?php
// admin_sessoes.php
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

// Buscar dados do dono das sessões
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_alvo_id]);
$usuario_alvo = $stmt_user->fetch();

// Deletar sessão se solicitado pelo admin
if (isset($_GET['deletar_sessao'])) {
    $sessao_id = filter_input(INPUT_GET, 'deletar_sessao', FILTER_VALIDATE_INT);
    $stmt_del = $pdo->prepare("DELETE FROM sessoes WHERE id = ? AND usuario_id = ?");
    $stmt_del->execute([$sessao_id, $usuario_alvo_id]);
    header("Location: admin_sessoes.php?usuario_id=" . $usuario_alvo_id);
    exit;
}

// Buscar sessões combinando os dados com o modelo da prancha utilizada
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - Sessões de <?= htmlspecialchars($usuario_alvo['nome']) ?></title>
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
            <a href="admin.php" class="btn-back">← Voltar ao Painel</a>
            <h1 style="margin-top:15px;">Histórico de Surf: <?= htmlspecialchars($usuario_alvo['nome']) ?></h1>
        </div>
    </div>

    <?php if(empty($sessoes)): ?>
        <p style="color: #64748b;">Este usuário ainda não registrou nenhuma sessão de surf.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Localização</th>
                        <th>Duração</th>
                        <th>Prancha</th>
                        <th>Nota</th>
                        <th>Ação</th>
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
                            <td><?= $s['prancha_nome'] ? htmlspecialchars($s['prancha_nome']) : '<span style="color:#94a3b8;">Nenhuma</span>' ?></td>
                            <td>⭐ <?= number_format($s['nota'], 1) ?></td>
                            <td>
                                <a href="admin_sessoes.php?usuario_id=<?= $usuario_alvo_id ?>&deletar_sessao=<?= $s['id'] ?>" 
                                   class="btn-del" onclick="return confirm('Tem certeza que deseja apagar esta sessão de surf do histórico do usuário?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</body>
</html>