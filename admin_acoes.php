<?php
/**
 * ====================================================================
 * FILE: admin_acoes.php
 * PURPOSE: Executar Ações de Administração (Promover, Rebaixar, Deletar)
 * ====================================================================
 * 
 * Este arquivo processa as ações do admin via GET parameters:
 * 
 * Ações Suportadas:
 * 1. toggle_role: Alterna entre Admin e Usuário Comum
 * 2. delete_user: Deleta completamente o usuário e todos seus dados
 *    (pranchas, sessões, etc)
 * 
 * Formato das URLs:
 * - admin_acoes.php?action=toggle_role&id=123
 * - admin_acoes.php?action=delete_user&id=123
 * 
 * SEGURANÇA: Todas as ações requerem que o usuário seja admin
 */

// Importa a conexão com o banco de dados
require_once 'config/conexao.php';

// Inicia a sessão para verificar permissões
session_start();

// ============= VERIFICAÇÃO DE SEGURANÇA MÁXIMA =============
// Verifica se o usuário está logado (tem ID na sessão)
if (!isset($_SESSION['usuario_id'])) {
    // Se não, redireciona para login
    header("Location: index.php");
    exit;
}

// Busca informações do usuário logado para verificar se é admin
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user_atual = $stmt->fetch();

// Se o usuário não existe ou não é admin
if (!$user_atual || $user_atual['is_admin'] != 1) {
    // Redireciona para o dashboard
    header("Location: dashboard.php");
    exit;
}

// ============= CAPTURA DOS PARÂMETROS DA URL =============
// Obtém o parâmetro 'action' que especifica qual ação executar
$action = $_GET['action'] ?? '';

// Obtém e valida o ID do usuário alvo (deve ser um inteiro)
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Verifica se ambos os parâmetros foram fornecidos
if ($user_id && $action) {
    
    // ============= AÇÃO 1: ALTERNAR CARGO (ADMIN <-> USER) =============
    if ($action === 'toggle_role') {
        // Busca qual é o nível atual do usuário alvo
        $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch();
        
        // Se o usuário existe
        if ($target_user) {
            // Se é admin (1), muda para usuário comum (0)
            // Se é usuário comum (0), muda para admin (1)
            $novo_cargo = $target_user['is_admin'] == 1 ? 0 : 1;
            
            // Atualiza o banco com o novo cargo
            $update = $pdo->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
            $update->execute([$novo_cargo, $user_id]);
        }
    }

    // ============= AÇÃO 2: DELETAR USUÁRIO E TODOS OS DADOS =============
    if ($action === 'delete_user') {
        // Iniciamos uma transação para garantir integridade dos dados
        // Se tudo correr bem, confirma (commit)
        // Se der erro, desfaz tudo (rollback) para não deixar dados órfãos
        $pdo->beginTransaction();
        try {
            // PASSO 1: Deleta todas as sessões de surf do usuário
            $del_sessoes = $pdo->prepare("DELETE FROM sessoes WHERE usuario_id = ?");
            $del_sessoes->execute([$user_id]);

            // PASSO 2: Deleta todas as pranchas do usuário
            $del_pranchas = $pdo->prepare("DELETE FROM pranchas WHERE usuario_id = ?");
            $del_pranchas->execute([$user_id]);

            // PASSO 3: Por fim, deleta a conta do usuário
            $del_usuario = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $del_usuario->execute([$user_id]);

            // Se chegou aqui sem erros, confirma todas as deleções
            $pdo->commit();
        } catch (Exception $e) {
            // Se houver qualquer erro no processo
            // Desfaz TODAS as operações para não deixar dados inconsistentes
            $pdo->rollBack();
        }
    }
}

// ============= REDIRECIONAMENTO =============
// Após executar a ação, redireciona de volta para o painel admin
// para que o admin veja a lista atualizada
header("Location: admin.php");
exit;