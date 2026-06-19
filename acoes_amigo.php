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

// Lógica de Idioma baseada na sessão
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'cannot_add_self'    => 'You cannot add yourself.',
        'user_not_found'     => 'User ID not found.',
        'already_buddies'    => 'You are already buddies.',
        'already_pending'    => 'Request already pending.',
        'already_sent_you'   => 'This user already sent you a request! Check your notifications.',
        'request_sent'       => 'Friend request sent!',
        'now_buddies'        => 'You are now buddies! Shaka! 🤙',
        'request_declined'   => 'Request declined.'
    ],
    'pt' => [
        'cannot_add_self'    => 'Você não pode adicionar a si mesmo.',
        'user_not_found'     => 'ID de usuário não encontrado.',
        'already_buddies'    => 'Vocês já são amigos.',
        'already_pending'    => 'Solicitação já pendente.',
        'already_sent_you'   => 'Este usuário já lhe enviou uma solicitação! Verifique suas notificações.',
        'request_sent'       => 'Pedido de amizade enviado!',
        'now_buddies'        => 'Agora vocês são amigos! Shaka! 🤙',
        'request_declined'   => 'Solicitação recusada.'
    ],
    'es' => [
        'cannot_add_self'    => 'No puedes agregarte a ti mismo.',
        'user_not_found'     => 'ID de usuario no encontrado.',
        'already_buddies'    => 'Ya son amigos.',
        'already_pending'    => 'Solicitud ya pendiente.',
        'already_sent_you'   => '¡Este usuario ya te envió una solicitud! Revisa tus notificaciones.',
        'request_sent'       => '¡Solicitud de amistad enviada!',
        'now_buddies'        => '¡Ahora son amigos! ¡Shaka! 🤙',
        'request_declined'   => 'Solicitud rechazada.'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

$usuario_logado = $_SESSION['usuario_id'];
$acao = $_GET['acao'] ?? '';

// 1. ENVIAR PEDIDO DE AMIZADE
if ($acao === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amigo_id = intval($_POST['amigo_id']);

    if ($usuario_logado === $amigo_id) {
        header("Location: dashboard.php?erro=" . urlencode($txt['cannot_add_self']));
        exit;
    }

    // Verificar se o ID existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$amigo_id]);
    if (!$stmt->fetch()) {
        header("Location: dashboard.php?erro=" . urlencode($txt['user_not_found']));
        exit;
    }

    // Verificar se já existe relação entre eles (em qualquer direção)
    $stmt = $pdo->prepare("SELECT status, usuario_origem_id FROM amizades WHERE (usuario_origem_id = ? AND usuario_destino_id = ?) OR (usuario_origem_id = ? AND usuario_destino_id = ?)");
    $stmt->execute([$usuario_logado, $amigo_id, $amigo_id, $usuario_logado]);
    $relacao = $stmt->fetch();

    if ($relacao) {
        if ($relacao['status'] === 'aceito') {
            header("Location: dashboard.php?erro=" . urlencode($txt['already_buddies']));
        } elseif ($relacao['status'] === 'pendente') {
            if ($relacao['usuario_origem_id'] == $usuario_logado) {
                header("Location: dashboard.php?erro=" . urlencode($txt['already_pending']));
            } else {
                header("Location: dashboard.php?erro=" . urlencode($txt['already_sent_you']));
            }
        } else {
            // Se foi recusado antes, permite tentar enviar de novo resetando o status
            $stmt = $pdo->prepare("UPDATE amizades SET usuario_origem_id = ?, usuario_destino_id = ?, status = 'pendente', notificacao_lida_origem = 0 WHERE (usuario_origem_id = ? AND usuario_destino_id = ?) OR (usuario_origem_id = ? AND usuario_destino_id = ?)");
            $stmt->execute([$usuario_logado, $amigo_id, $usuario_logado, $amigo_id, $amigo_id, $usuario_logado]);
            header("Location: dashboard.php?sucesso=" . urlencode($txt['request_sent']));
        }
        exit;
    }

    // Insere novo pedido pendente
    $stmt = $pdo->prepare("INSERT INTO amizades (usuario_origem_id, usuario_destino_id, status) VALUES (?, ?, 'pendente')");
    $stmt->execute([$usuario_logado, $amigo_id]);
    header("Location: dashboard.php?sucesso=" . urlencode($txt['request_sent']));
    exit;
}

// 2. ACEITAR PEDIDO
if ($acao === 'aceitar') {
    $id_pedido = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE amizades SET status = 'aceito', notificacao_lida_origem = 0 WHERE id = ? AND usuario_destino_id = ?");
    $stmt->execute([$id_pedido, $usuario_logado]);
    header("Location: dashboard.php?sucesso=" . urlencode($txt['now_buddies']));
    exit;
}

// 3. RECUSAR PEDIDO
if ($acao === 'recusar') {
    $id_pedido = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE amizades SET status = 'recusado' WHERE id = ? AND usuario_destino_id = ?");
    $stmt->execute([$id_pedido, $usuario_logado]);
    header("Location: dashboard.php?sucesso=" . urlencode($txt['request_declined']));
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