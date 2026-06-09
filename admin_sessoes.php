<?php
/**
 * ====================================================================
 * FILE: admin_sessoes.php
 * PURPOSE: Visualizar e Gerenciar Sessões de Surf de um Usuário
 * ====================================================================
 * 
 * Esta página permite ao admin:
 * 1. Ver TODAS as sessões de surf de um usuário específico
 * 2. Visualizar detalhes completos de cada sessão
 * 3. Deletar sessões individuais do histórico
 * 
 * Fluxo:
 * 1. Admin clica em "Sessões" de um usuário em admin.php
 * 2. É redirecionado para admin_sessoes.php?usuario_id=123
 * 3. Vê tabela com histórico de todas as sessões daquele usuário
 * 4. Pode deletar sessões pela ação na tabela
 * 
 * SEGURANÇA: Requer ser admin E fornecer ID válido do usuário
 */

// Importa a conexão com o banco de dados
require_once 'config/conexao.php';

// Inicia a sessão para verificar permissões
session_start();

// ============= VERIFICAÇÃO DE ACESSO - PROTEÇÃO 1 =============
// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Busca informações do usuário logado para verificar se é admin
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

// Se não é admin, redireciona
if (!$user_atual || $user_atual['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit;
}

// ============= VALIDAR ID DO USUÁRIO ALVO =============
// Obtém e valida o ID do usuário cujas sessões queremos ver
$usuario_alvo_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

// Se o ID não é válido, redireciona para admin.php
if (!$usuario_alvo_id) {
    header("Location: admin.php");
    exit;
}

// ============= BUSCAR DADOS DO USUÁRIO ALVO =============
// Obtém o nome do usuário para exibir no título
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_user->execute([$usuario_alvo_id]);
$usuario_alvo = $stmt_user->fetch();

// ============= DELETAR SESSÃO SE SOLICITADO =============
// Verifica se há parâmetro de deleção na URL
if (isset($_GET['deletar_sessao'])) {
    // Obtém e valida o ID da sessão a deletar
    $sessao_id = filter_input(INPUT_GET, 'deletar_sessao', FILTER_VALIDATE_INT);
    
    // Deleta a sessão (verifica dupla segurança: ID da sessão E ID do proprietário)
    $stmt_del = $pdo->prepare("DELETE FROM sessoes WHERE id = ? AND usuario_id = ?");
    $stmt_del->execute([$sessao_id, $usuario_alvo_id]);
    
    // Redireciona para recarregar a página sem a sessão deletada
    header("Location: admin_sessoes.php?usuario_id=" . $usuario_alvo_id);
    exit;
}

// ============= BUSCAR TODAS AS SESSÕES DO USUÁRIO =============
// Busca todas as sessões combinando dados com o modelo da prancha utilizada
// LEFT JOIN permite exibir sessões mesmo que a prancha tenha sido deletada
// Ordena por data da sessão (mais recente primeiro)
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