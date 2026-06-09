<?php
// admin_acoes.php
require_once 'config/conexao.php';
session_start();

// SEGURANÇA MÁXIMA: Verifica se quem chamou este arquivo é um admin de verdade
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

if (!$user_atual || $user_atual['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit;
}

// CAPTURA DOS PARÂMETROS
$action = $_GET['action'] ?? '';
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($user_id && $action) {
    
    // AÇÃO 1: ALTERNAR CARGO (ADMIN <-> USER)
    if ($action === 'toggle_role') {
        // Verifica qual o nível atual do usuário alvo
        $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch();
        
        if ($target_user) {
            $novo_cargo = $target_user['is_admin'] == 1 ? 0 : 1;
            $update = $pdo->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
            $update->execute([$novo_cargo, $user_id]);
        }
    }

    // AÇÃO 2: DELETAR USUÁRIO E TODOS OS SEUS DADOS ATRELADOS
    if ($action === 'delete_user') {
        // Iniciamos uma transação para garantir que tudo seja deletado junto sem erros
        $pdo->beginTransaction();
        try {
            // 1. Deleta todas as sessões de surf do usuário
            $del_sessoes = $pdo->prepare("DELETE FROM sessoes WHERE usuario_id = ?");
            $del_sessoes->execute([$user_id]);

            // 2. Deleta todas as pranchas do usuário
            $del_pranchas = $pdo->prepare("DELETE FROM pranchas WHERE usuario_id = ?");
            $del_pranchas->execute([$user_id]);

            // 3. Por fim, deleta a conta do usuário
            $del_usuario = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $del_usuario->execute([$user_id]);

            // Confirma as deleções no banco
            $pdo->commit();
        } catch (Exception $e) {
            // Se der qualquer erro no processo, desfaz tudo para não corromper os dados
            $pdo->rollBack();
        }
    }
}

// Finalizado o processo, redireciona de volta para a listagem admin
header("Location: admin.php");
exit;