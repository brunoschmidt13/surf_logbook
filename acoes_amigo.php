<?php
/**
 * FILE: acoes_amigo.php
 * PURPOSE: Centralize friend requests, accept, decline and notifications
 */

require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_logado = $_SESSION['usuario_id'];
$acao = $_GET['acao'] ?? '';

// 1. ENVIAR PEDIDO DE AMIZADE
if ($acao === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amigo_id = intval($_POST['amigo_id']);

    if ($usuario_logado === $amigo_id) {
        header("Location: dashboard.php?erro=You cannot add yourself.");
        exit;
    }

    // Verificar se o ID existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$amigo_id]);
    if (!$stmt->fetch()) {
        header("Location: dashboard.php?erro=User ID not found.");
        exit;
    }

    // Verificar se já existe relação entre eles (em qualquer direção)
    $stmt = $pdo->prepare("SELECT status, usuario_origem_id FROM amizades WHERE (usuario_origem_id = ? AND usuario_destino_id = ?) OR (usuario_origem_id = ? AND usuario_destino_id = ?)");
    $stmt->execute([$usuario_logado, $amigo_id, $amigo_id, $usuario_logado]);
    $relacao = $stmt->fetch();

    if ($relacao) {
        if ($relacao['status'] === 'aceito') {
            header("Location: dashboard.php?erro=You are already buddies.");
        } elseif ($relacao['status'] === 'pendente') {
            if ($relacao['usuario_origem_id'] == $usuario_logado) {
                header("Location: dashboard.php?erro=Request already pending.");
            } else {
                header("Location: dashboard.php?erro=This user already sent you a request! Check your notifications.");
            }
        } else {
            // Se foi recusado antes, permite tentar enviar de novo resetando o status
            $stmt = $pdo->prepare("UPDATE amizades SET usuario_origem_id = ?, usuario_destino_id = ?, status = 'pendente', notificacao_lida_origem = 0 WHERE (usuario_origem_id = ? AND usuario_destino_id = ?) OR (usuario_origem_id = ? AND usuario_destino_id = ?)");
            $stmt->execute([$usuario_logado, $amigo_id, $usuario_logado, $amigo_id, $amigo_id, $usuario_logado]);
            header("Location: dashboard.php?sucesso=Friend request sent!");
        }
        exit;
    }

    // Insere novo pedido pendente
    $stmt = $pdo->prepare("INSERT INTO amizades (usuario_origem_id, usuario_destino_id, status) VALUES (?, ?, 'pendente')");
    $stmt->execute([$usuario_logado, $amigo_id]);
    header("Location: dashboard.php?sucesso=Friend request sent!");
    exit;
}

// 2. ACEITAR PEDIDO
if ($acao === 'aceitar') {
    $id_pedido = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE amizades SET status = 'aceito', notificacao_lida_origem = 0 WHERE id = ? AND usuario_destino_id = ?");
    $stmt->execute([$id_pedido, $usuario_logado]);
    header("Location: dashboard.php?sucesso=You are now buddies! Shaka! 🤙");
    exit;
}

// 3. RECUSAR PEDIDO
if ($acao === 'recusar') {
    $id_pedido = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE amizades SET status = 'recusado' WHERE id = ? AND usuario_destino_id = ?");
    $stmt->execute([$id_pedido, $usuario_logado]);
    header("Location: dashboard.php?sucesso=Request declined.");
    exit;
}

// 4. LIMPAR AVISO DE "AMIGO ACEITOU SEU PEDIDO"
if ($acao === 'limpar_aviso') {
    $id_pedido = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE amizades SET notificacao_lida_origem = 1 WHERE id = ? AND usuario_origem_id = ?");
    $stmt->execute([$id_pedido, $usuario_logado]);
    header("Location: dashboard.php");
    exit;
}