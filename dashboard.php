<?php
/**
 * ====================================================================
 * FILE: dashboard.php
 * PURPOSE: Main User Dashboard - View Surf Logbook & Crew System
 * ====================================================================
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
        'none_yet' => 'None yet',
        'buddy'    => 'Buddy',
        'title'    => 'TheSurfChronicles - Dashboard'
    ],
    'pt' => [
        'none_yet' => 'Nenhum ainda',
        'buddy'    => 'Parceiro',
        'title'    => 'TheSurfChronicles - Painel'
    ],
    'es' => [
        'none_yet' => 'Ninguno aún',
        'buddy'    => 'Compañero',
        'title'    => 'TheSurfChronicles - Panel'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

// Control of post-login floating notice
$exibir_informativo = false;
if (isset($_SESSION['mostrar_aviso']) && $_SESSION['mostrar_aviso'] === true) {
    $exibir_informativo = true;
    unset($_SESSION['mostrar_aviso']); 
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

$partes_nome = explode(' ', $usuario_nome);
$primeiro_nome = $partes_nome[0];

// ============= 1. FETCH GENERAL DASHBOARD STATISTICS =============
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sessoes, 
        SUM(duracao_minutos) as total_minutos, 
        AVG(nota) as media_nota 
    FROM sessoes 
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$estatisticas = $stmt->fetch();

$total_sessoes = $estatisticas['total_sessoes'] ?? 0;
$total_minutos = $estatisticas['total_minutos'] ?? 0;
$media_nota = $estatisticas['media_nota'] ? number_format($estatisticas['media_nota'], 1, '.', '') : '0.0';

// ============= 2. FETCH RECORDS AND PREFERENCES =============
$stmt_longa = $pdo->prepare("SELECT MAX(duracao_minutos) as maior_tempo FROM sessoes WHERE usuario_id = ?");
$stmt_longa->execute([$usuario_id]);
$sessao_longa = $stmt_longa->fetch();
$maior_sessao = $sessao_longa['maior_tempo'] ?? 0;

$stmt_prancha_top = $pdo->prepare("
    SELECT p.modelo, COUNT(s.id) as total_usos 
    FROM sessoes s
    LEFT JOIN pranchas p ON s.prancha_id = p.id
    WHERE s.usuario_id = ? AND s.prancha_id IS NOT NULL
    GROUP BY p.id
    ORDER BY total_usos DESC
    LIMIT 1
");
$stmt_prancha_top->execute([$usuario_id]);
$prancha_top = $stmt_prancha_top->fetch();
$prancha_mais_usada = $prancha_top ? $prancha_top['modelo'] : $txt['none_yet'];

$stmt_onda_top = $pdo->prepare("SELECT MAX(altura_onda) as maior_onda FROM sessoes WHERE usuario_id = ?");
$stmt_onda_top->execute([$usuario_id]);
$onda_top = $stmt_onda_top->fetch();
$maior_onda = $onda_top['maior_onda'] ?? 0.0;

// ============= 3. FETCH USER'S BOARDS =============
$stmt_pranchas = $pdo->prepare("SELECT * FROM pranchas WHERE usuario_id = ? ORDER BY id DESC");
$stmt_pranchas->execute([$usuario_id]);
$pranchas = $stmt_pranchas->fetchAll();

// ============= 4. FETCH USER'S SURF SESSIONS =============
$stmt_sessoes = $pdo->prepare("
    SELECT s.*, p.modelo as prancha_modelo 
    FROM sessoes s 
    LEFT JOIN pranchas p ON s.prancha_id = p.id 
    WHERE s.usuario_id = ? 
    ORDER BY s.data_sessao DESC
");
$stmt_sessoes->execute([$usuario_id]);
$sessoes = $stmt_sessoes->fetchAll();

// ============= 5. FRIENDS SYSTEM DATA FETCH =============
// A. Pedidos recebidos pendentes (Para aceitar ou recusar)
$stmt_notif = $pdo->prepare("
    SELECT a.id, u.nome, u.id as amigo_id 
    FROM amizades a 
    JOIN usuarios u ON a.usuario_origem_id = u.id 
    WHERE a.usuario_destino_id = ? AND a.status = 'pendente'
");
$stmt_notif->execute([$usuario_id]);
$pedidos_pendentes = $stmt_notif->fetchAll();

// B. Notificações de pedidos enviados que foram ACEITOS pelo outro surfista
$stmt_aceitos = $pdo->prepare("
    SELECT a.id, u.nome 
    FROM amizades a 
    JOIN usuarios u ON a.usuario_destino_id = u.id 
    WHERE a.usuario_origem_id = ? AND a.status = 'aceito' AND a.notificacao_lida_origem = 0
");
$stmt_aceitos->execute([$usuario_id]);
$alertas_aceitos = $stmt_aceitos->fetchAll();

// C. Lista Completa de Amigos Confirmados
$stmt_lista = $pdo->prepare("
    SELECT a.id as amizade_id, u.id, u.nome 
    FROM amizades a
    JOIN usuarios u ON (a.usuario_origem_id = u.id OR a.usuario_destino_id = u.id)
    WHERE (a.usuario_origem_id = ? OR a.usuario_destino_id = ?) 
      AND a.status = 'aceito' 
      AND u.id != ?
    ORDER BY u.nome ASC
");
$stmt_lista->execute([$usuario_id, $usuario_id, $usuario_id]);
$lista_amigos = $stmt_lista->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $txt['title'] ?></title>
    <link rel="icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f1f5f9; 
            margin: 0; 
            color: #1e293b; 
            position: relative; 
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: 
                radial-gradient(circle, rgba(0,0,0,0) 60%, rgba(0,0,0,0.5) 70%),
                url('img/dash_background.jpg');
            background-size: cover;       
            background-position: center;  
            background-repeat: no-repeat;
            opacity: 0.60; 
        }

        .navbar { 
            background-color: #ffffff; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #e2e8f0; 
        }

        .logo { 
            text-align: center; 
            margin-bottom: 8px; 
        }
        .logo-dash { 
            font-size: 26px; 
            font-weight: bold; 
            color: #0084b4; 
            line-height: 1; 
        }

        .logo-dash-sub { 
            margin-top: 4px; 
            font-size: 12px; 
            margin-left: 12px; 
            font-weight: 400; 
            letter-spacing: 7px; 
            text-transform: uppercase; 
            color: rgba(44, 41, 41, 0.55); 
            line-height: 1; 
        }
        
        .water-round-container {
            margin: 0 auto;
            overflow: hidden;
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid silver;
            text-align: center;
            line-height: 20px;
            animation: water-waves linear infinite;
        }
        .water-wave1 {
            position: absolute;
            top: 40%;
            left: -25%;
            background: #33cfff;
            opacity: 0.8;
            width: 200%;
            height: 200%;
            border-radius: 40%;
            animation: inherit;
            animation-duration: 5s;
        }
        .water-wave2 {
            position: absolute;
            top: 45%;
            left: -35%;
            background: #0eaffe;
            opacity: 0.5;
            width: 200%;
            height: 200%;
            border-radius: 35%;
            animation: inherit;
            animation-duration: 7s;
        }
        .water-wave3 {
            position: absolute;
            top: 50%;
            left: -35%;
            background: #0f7ae4;
            opacity: 0.3;
            width: 200%;
            height: 200%;
            border-radius: 33%;
            animation: inherit;
            animation-duration: 11s;
        }

        @keyframes water-waves {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .user-menu { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            font-size: 14px; 
        }

        .user-meta-block {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            margin-top: 4px;
        }

        .user-id-tag {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .logout-btn { 
            color: #64748b; 
            text-decoration: none; 
            font-weight: 500; 
        }

        .logout-btn:hover { 
            color: #ef4444; 
        }

        /* ESTRUTURA GERAL COM WRAPPER DO WRAPPER DE SIDEBAR */
        .dashboard-layout-wrapper {
            max-width: 1350px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* AJUSTE RESPONSIVO CASO A TELA SEJA PEQUENA */
        @media (max-width: 992px) {
            .dashboard-layout-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
                margin: 20px auto;
            }
        }

        .sidebar-crew-container {
            display: flex;
            flex-direction: column;
            padding: 10px;
            margin-top: 55px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
        }

        .welcome-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .welcome-section h1 { 
            margin: 0; 
            font-size: 28px; 
            color: #282a2c; 
        }
        
        .btn-primary { 
            background-color: #0084b4; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
        }

        .btn-primary:hover { 
            background-color: #006b93; 
        }
        
        .dashboard-widgets { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 20px; 
            margin-bottom: 40px; 
        }
        
        .widget-card { 
            background: rgba(255, 255, 255, 0.88); 
            backdrop-filter: blur(6px); 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); 
        }

        .widget-icon { 
            font-size: 26px; 
            padding: 8px; 
            border-radius: 10px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            width: 38px; 
            height: 38px; 
        }

        .icon-blue { 
            background: #e0f2fe; 
            color: #0284c7; 
        }

        .icon-amber { 
            background: #fef3c7; 
            color: #d97706; 
        }

        .icon-emerald { 
            background: #dcfce7; 
            color: #059669; 
        }

        .widget-info { 
            display: flex; 
            flex-direction: column; 
        }

        .widget-label { 
            font-size: 11px; 
            text-transform: uppercase; 
            color: #94a3b8; 
            font-weight: 700; 
            letter-spacing: 0.5px; 
        }

        .widget-value { 
            font-size: 22px; 
            font-weight: bold; 
            color: #0f172a; 
            margin-top: 3px; 
        }
        
        .section-title { 
            font-size: 14px; 
            text-transform: uppercase; 
            color: #282a2c; 
            font-weight: 700; 
            margin-bottom: 15px; 
            letter-spacing: 0.5px; 
        }
        
        .content-box { 
            background: rgba(255, 255, 255, 0.88); 
            backdrop-filter: blur(6px); 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            padding: 25px; 
            margin-bottom: 40px; 
        }

        .board-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 0; 
            border-bottom: 1px solid #f1f5f9; 
        }

        .board-item:last-child { 
            border-bottom: none; 
        }

        .board-info h3 { 
            margin: 0; 
            font-size: 16px; 
            color: #0f172a; 
        }

        .board-info p { 
            margin: 5px 0 0 0; 
            font-size: 14px; 
            color: #64748b; 
        }
        
        .session-item { 
            border-bottom: 1px solid #f1f5f9; 
            padding: 20px 0; 
        }

        .session-item:last-child { 
            border-bottom: none; 
        }

        .session-meta { 
            font-size: 13px; 
            color: #64748b; 
            margin-bottom: 8px; 
        }

        .session-location { 
            font-size: 16px; 
            font-weight: bold; 
            color: #0f172a; 
            margin-bottom: 6px; 
        }

        .session-location span { 
            color: #64748b; 
            font-weight: normal; 
            font-size: 14px; 
        }

        .session-details { 
            font-size: 14px; 
            color: #475569; 
            line-height: 1.6; 
        }

        .modal-info-login { 
            position: fixed; 
            z-index: 9999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(15, 23, 42, 0.7); 
            backdrop-filter: blur(5px); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }

        .info-login-content {
            background-color: white; 
            padding: 35px; 
            border-radius: 16px; 
            width: 90%; 
            max-width: 420px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15); 
            text-align: center; 
            animation: popupSuave 0.3s ease-out; 
        }

        @keyframes popupSuave { 
            from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } 
        }

        .info-login-content h2 { 
            margin-top: 0; 
            color: #0f172a; 
            font-size: 22px; 
        }

        .info-login-content p { 
            color: #475569; 
            font-size: 15px; 
            line-height: 1.6; 
            margin: 15px 0 25px 0; 
        }

        .btn-vamos-la { 
            background-color: #0084b4; 
            color: white; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 15px; 
            cursor: pointer; 
            width: 100%; 
            transition: background 0.2s; 
        }

        .btn-vamos-la:hover { 
            background-color: #006b93; 
        }

        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.4); 
            justify-content: center; 
            align-items: center; 
        }

        .modal-content { 
            background-color: white; 
            padding: 30px; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 500px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
            position: relative; 
        }

        .close-btn { 
            position: absolute; 
            right: 20px; 
            top: 15px; 
            font-size: 24px; 
            cursor: pointer; 
            color: #94a3b8; 
        }

        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
        }

        .full-width { 
            grid-column: span 2; 
        }

        .form-modal label { 
            display: block; 
            margin-bottom: 5px; 
            color: #475569; 
            font-size: 14px; 
            font-weight: 600; 
        }

        .form-modal input, .form-modal select, .form-modal textarea { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-family: inherit; 
        }

        .rating-select { 
            display: flex; 
            gap: 5px; 
            font-size: 24px; 
            cursor: pointer; 
            margin-bottom: 15px;
            color: #cbd5e1; 
        }

        .rating-select span:hover, .rating-select span.active { 
            color: #0084b4; 
        }
        
    </style>
</head>
<body>

<?php
    $more_translations = [
        'en' => [
            'friend_accepted' => 'accepted your friend request! You are now surf buddies.',
            'dismiss' => 'Dismiss',
            'sent_request' => 'sent you a friend request!',
            'accept' => 'Accept',
            'decline' => 'Decline',
            'whats_new' => "What's New in SurfLog!",
            'notice_text' => "Hey master! We've rolled out some fresh updates 🌊🔥<br>
			🤙 New Friend System — Add your friends using their user <strong>ID</strong> and build your surf crew.<br>
			📊 Crew Dashboard — Follow your friends' sessions and keep up with your crew's progress.<br>
			🏄‍♂️ Comparison Dashboard — Compare stats, track your evolution, and see who's catching the best waves.<br>
			Stay connected, push your limits, and enjoy the ride together. Aloha and good waves! 🌊🤙",
            'got_it' => 'Got it',
            'admin_panel' => '⚙️ Admin Panel',
            'logout' => 'Log out ↗',
            'logbook' => 'Logbook',
            'hi' => 'Hi',
            'new_board' => '+ New Board',
            'new_session' => '+ New Session',
            'tot_sessions' => 'Total Sessions',
            'water_time' => 'Water Time',
            'avg_rating' => 'Average Rating',
            'longest_session' => 'Longest Session',
            'most_used_board' => 'Most Used Board',
            'biggest_wave' => 'Biggest Wave',
            'crew_buddies' => 'Surf Crew & Buddies',
            'add_to_crew' => 'Add to Crew',
            'enter_id' => "Enter a friend's #ID to compare statistics.",
            'send_request' => 'Request',
            'my_friends' => 'My Friends',
            'empty_crew' => 'Crew is empty. Use the input above!',
            'compare_logs' => '📊 Compare Logs',
            'my_surfboards' => 'My Surfboards',
            'no_boards' => 'No boards registered yet.',
            'size' => 'Size',
            'volume' => 'Volume',
            'all_sessions' => 'All Sessions',
            'no_sessions' => 'No sessions recorded yet.',
            'none_board' => 'None / Other',
            'none_f' => 'None',
            'conditions' => 'Conditions',
            'rating' => 'Rating',
            'notes_diary' => 'Notes / Diary',
            'save_board' => 'Save Board',
            'log_new_session' => 'Log New Session',
            'log_session' => 'Log Session',
            'model' => 'Model',
            'brand' => 'Brand',
            'liters' => 'Liters',
            'date' => 'Date',
            'duration' => 'Duration (minutes)',
            'state' => 'State',
            'city' => 'City',
            'beach_spot' => 'Beach / Spot',
            'wave_height' => 'Wave Height (m)',
            'period' => 'Period (s)',
            'board_used' => 'Board Used',
            'session_rating' => 'Session Rating',
            'placeholder_model' => 'Ex: SF3, Monsta Box',
            'placeholder_brand' => 'Ex: Tokoro, JS, Pyzel',
            'placeholder_size' => "Ex: 5'11",
            'placeholder_volume' => 'Ex: 29.5',
            'placeholder_duration' => 'Ex: 90',
            'placeholder_state' => 'Ex: SC',
            'placeholder_city' => 'Ex: Imbituba',
            'placeholder_beach' => 'Ex: Rosa Beach (South Corner)',
            'placeholder_height' => 'Ex: 1.5',
            'placeholder_period' => 'Ex: 11',
            'placeholder_rating' => 'From 0.5 to 5.0',
            'placeholder_notes' => 'How was the wind? And the tide? Big barrels?'
        ],
        'pt' => [
            'friend_accepted' => 'aceitou seu pedido de amizade! Vocês agora são parceiros de surf.',
            'dismiss' => 'Fechar',
            'sent_request' => 'te enviou um pedido de amizade!',
            'accept' => 'Aceitar',
            'decline' => 'Recusar',
            'whats_new' => 'Novidades no SurfLog!',
            'notice_text' => 'E aí, mestre! Acabamos de lançar novas atualizações 🌊🔥<br>
            🤙 Novo Sistema de Amizades — Adicione seus amigos usando o ID de usuário e monte sua própria surf crew.<br>
            📊 Dashboard da Crew — Acompanhe as sessões dos seus amigos e veja o progresso da sua galera.<br>
            🏄‍♂️ Dashboard Comparativa — Compare estatísticas, acompanhe sua evolução e descubra quem está pegando as melhores ondas.<br>
            Fique conectado, evolua seus limites e aproveite a jornada junto com sua crew. Aloha e boas ondas! 🌊🤙',
            'got_it' => 'Entendi',
            'admin_panel' => '⚙️ Painel do Admin',
            'logout' => 'Sair ↗',
            'logbook' => 'Diário de Surf',
            'hi' => 'Olá',
            'new_board' => '+ Nova Prancha',
            'new_session' => '+ Nova Sessão',
            'tot_sessions' => 'Total de Sessões',
            'water_time' => 'Tempo de Água',
            'avg_rating' => 'Nota Média',
            'longest_session' => 'Sessão Mais Longa',
            'most_used_board' => 'Prancha Mais Usada',
            'biggest_wave' => 'Maior Onda',
            'crew_buddies' => 'Equipe e Parceiros',
            'add_to_crew' => 'Adicionar à Equipe',
            'enter_id' => 'Insira o #ID de um amigo para comparar estatísticas.',
            'send_request' => 'Pedir',
            'my_friends' => 'Meus Amigos',
            'empty_crew' => 'Sua equipe está vazia. Adicione parceiros acima!',
            'compare_logs' => '📊 Comparar',
            'my_surfboards' => 'Minhas Pranchas',
            'no_boards' => 'Nenhuma prancha registrada ainda.',
            'size' => 'Tamanho',
            'volume' => 'Volume',
            'all_sessions' => 'Todas as Sessões',
            'no_sessions' => 'Nenhuma sessão gravada ainda.',
            'none_board' => 'Nenhuma / Outra',
            'none_f' => 'Nenhuma',
            'conditions' => 'Condições',
            'rating' => 'Nota',
            'notes_diary' => 'Notas / Diário',
            'save_board' => 'Salvar Prancha',
            'log_new_session' => 'Registrar Nova Sessão',
            'log_session' => 'Salvar Sessão',
            'model' => 'Modelo',
            'brand' => 'Marca',
            'liters' => 'Litros',
            'date' => 'Data',
            'duration' => 'Duração (minutos)',
            'state' => 'Estado',
            'city' => 'Cidade',
            'beach_spot' => 'Praia / Pico',
            'wave_height' => 'Altura da Onda (m)',
            'period' => 'Período (s)',
            'board_used' => 'Prancha Utilizada',
            'session_rating' => 'Nota da Sessão',
            'placeholder_model' => 'Ex: SF3, Monsta Box',
            'placeholder_brand' => 'Ex: Tokoro, JS, Pyzel',
            'placeholder_size' => "Ex: 5'11",
            'placeholder_volume' => 'Ex: 29.5',
            'placeholder_duration' => 'Ex: 90',
            'placeholder_state' => 'Ex: SC',
            'placeholder_city' => 'Ex: Imbituba',
            'placeholder_beach' => 'Ex: Praia do Rosa (Canto Sul)',
            'placeholder_height' => 'Ex: 1.5',
            'placeholder_period' => 'Ex: 11',
            'placeholder_rating' => 'De 0.5 a 5.0',
            'placeholder_notes' => 'Como estava o vento? E a maré? Teve tubo?'
        ],
        'es' => [
            'friend_accepted' => 'aceptó tu solicitud de amistad! Ahora son compañeros de surf.',
            'dismiss' => 'Descartar',
            'sent_request' => '¡te envió una solicitud de amistad!',
            'accept' => 'Aceptar',
            'decline' => 'Rechazar',
            'whats_new' => '¡Novedades en SurfLog!',
            'notice_text' => '¡Hola maestro! Hemos actualizado el sistema. Ahora puedes registrar la <strong>Altura de la Ola</strong> y el <strong>Período</strong> en cada sesión, además de seguir nuevos récords directamente en tu panel. ¡Aloha y buenas olas! 🌊🤙',
            'got_it' => 'Entendido',
            'admin_panel' => '⚙️ Panel de Admin',
            'logout' => 'Salir ↗',
            'logbook' => 'Diario de Surf',
            'hi' => 'Hola',
            'new_board' => '+ Nueva Tabla',
            'new_session' => '+ Nueva Sesión',
            'tot_sessions' => 'Sesiones Totales',
            'water_time' => 'Tiempo de Agua',
            'avg_rating' => 'Nota Media',
            'longest_session' => 'Sesión Más Larga',
            'most_used_board' => 'Tabla Más Usada',
            'biggest_wave' => 'Mayor Ola',
            'crew_buddies' => 'Equipo y Compañeros',
            'add_to_crew' => 'Añadir al Equipo',
            'enter_id' => 'Ingresa el #ID de un amigo.',
            'send_request' => 'Solicitud',
            'my_friends' => 'Mis Amigos',
            'empty_crew' => 'Tu equipo está vacío.',
            'compare_logs' => '📊 Comparar',
            'my_surfboards' => 'Mis Tablas',
            'no_boards' => 'Ninguna tabla registrada aún.',
            'size' => 'Tamaño',
            'volume' => 'Volumen',
            'all_sessions' => 'Todas as Sesiones',
            'no_sessions' => 'Ninguna sesión grabada aún.',
            'none_board' => 'Ninguna / Otra',
            'none_f' => 'Ninguna',
            'conditions' => 'Condiciones',
            'rating' => 'Nota',
            'notes_diary' => 'Notas / Diario',
            'save_board' => 'Guardar Tabla',
            'log_new_session' => 'Registrar Nueva Sesión',
            'log_session' => 'Guardar Sesión',
            'model' => 'Modelo',
            'brand' => 'Marca',
            'liters' => 'Litros',
            'date' => 'Fecha',
            'duration' => 'Duración (minutos)',
            'state' => 'Estado',
            'city' => 'Ciudad',
            'beach_spot' => 'Playa / Pico',
            'wave_height' => 'Altura de Ola (m)',
            'period' => 'Período (s)',
            'board_used' => 'Tabla Utilizada',
            'session_rating' => 'Nota de la Sesión',
            'placeholder_model' => 'Ej: SF3, Monsta Box',
            'placeholder_brand' => 'Ej: Tokoro, JS, Pyzel',
            'placeholder_size' => "Ej: 5'11",
            'placeholder_volume' => 'Ej: 29.5',
            'placeholder_duration' => 'Ej: 90',
            'placeholder_state' => 'Ej: SC',
            'placeholder_city' => 'Ej: Imbituba',
            'placeholder_beach' => 'Ej: Playa del Rosa (Canto Sul)',
            'placeholder_height' => 'Ej: 1.5',
            'placeholder_period' => 'Ej: 11',
            'placeholder_rating' => 'De 0.5 a 5.0',
            'placeholder_notes' => '¿Cómo estuvo el viento? ¿Y la marea? ¿Hubo tubos?'
        ]
    ];

    $txt = array_merge($txt, ($more_translations[$lang] ?? $more_translations['en']));
    ?>

    <?php if (isset($_GET['erro'])): ?>
        <div style="background-color: #fef2f2; color: #b91c1c; padding: 12px 20px; text-align: center; font-weight: 600; font-size: 14px; border-bottom: 1px solid #fee2e2; position: relative; z-index: 9999;">
            ⚠️ <?= htmlspecialchars($_GET['erro']) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['sucesso'])): ?>
        <div style="background-color: #f0fdf4; color: #15803d; padding: 12px 20px; text-align: center; font-weight: 600; font-size: 14px; border-bottom: 1px solid #dcfce7; position: relative; z-index: 9999;">
            🤙 <?= htmlspecialchars($_GET['sucesso']) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($alertas_aceitos as $alerta): ?>
        <div style="background-color: #e0f2fe; color: #0369a1; padding: 12px 20px; text-align: center; font-weight: 600; font-size: 14px; border-bottom: 1px solid #bae6fd; position: relative; z-index: 9998;">
            🎉 <strong><?= htmlspecialchars($alerta['nome']) ?></strong> <?= $txt['friend_accepted'] ?>
            <a href="acoes_amigo.php?acao=limpar_aviso&id=<?= $alerta['id'] ?>" style="margin-left: 15px; color: #0284c7; text-decoration: underline; font-size: 12px;"><?= $txt['dismiss'] ?></a>
        </div>
    <?php endforeach; ?>

    <?php foreach ($pedidos_pendentes as $pedido): ?>
        <div style="background-color: #fffbeb; color: #b45309; padding: 12px 20px; text-align: center; font-weight: 600; font-size: 14px; border-bottom: 1px solid #fef3c7; display: flex; justify-content: center; align-items: center; gap: 15px; position: relative; z-index: 9998;">
            <span>🤝 <strong><?= htmlspecialchars($pedido['nome']) ?> (#<?= $pedido['amigo_id'] ?>)</strong> <?= $txt['sent_request'] ?></span>
            <div>
                <a href="acoes_amigo.php?acao=aceitar&id=<?= $pedido['id'] ?>" style="background: #d97706; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; margin-right: 5px;"><?= $txt['accept'] ?></a>
                <a href="acoes_amigo.php?acao=recusar&id=<?= $pedido['id'] ?>" style="background: #cbd5e1; color: #475569; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;"><?= $txt['decline'] ?></a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($exibir_informativo): ?>
    <div id="modalInformativoLogin" class="modal-info-login">
        <div class="info-login-content">
            <div style="font-size: 40px; margin-bottom: 15px;">📢</div>
            <h2><?= $txt['whats_new'] ?></h2>
            <p>
                <?= $txt['notice_text'] ?>
            </p>
            <button class="btn-vamos-la" onclick="closeNotice()"><?= $txt['got_it'] ?></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="navbar">
        <div class="logo">
            <div class="logo-dash">🌊 The Surf</div>
            <div class="logo-dash-sub">CHRONICLES</div>
        </div>

        <div class="water-round-container">
            <div class="water-wave1"></div>
            <div class="water-wave2"></div>
            <div class="water-wave3"></div>
        </div>

        <div class="user-menu">
            <?php
            $stmt_check = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['usuario_id']]);
            $check_admin = $stmt_check->fetch();
            if ($check_admin && $check_admin['is_admin'] == 1):
            ?>
                <a href="admin.php" style="color: #ef4444; font-weight: bold; text-decoration: none; margin-right: 15px;"><?= $txt['admin_panel'] ?></a>
            <?php endif; ?>
            
            <div class="user-meta-block">
                <span style="font-weight: 600; color: #282a2c;">@<?= htmlspecialchars($usuario_nome) ?></span>
                <span class="user-id-tag">#<?= $usuario_id ?></span>
            </div>
            
            <a href="logout.php" class="logout-btn"><?= $txt['logout'] ?></a>
        </div>
    </div>

    <!-- WRAPPER GLOBAL DO DASHBOARD INICIANDO O LAYOUT COM SIDEBAR -->
    <div class="dashboard-layout-wrapper">
        
        <!-- SIDEBAR LATERAL DA ESQUERDA (ÁREA EM VERMELHO DE image_90f525.jpg) -->
        <div class="sidebar-crew-container">
            <div class="section-title"><?= $txt['crew_buddies'] ?></div>
            <div class="content-box" style="padding: 20px;">
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 4px 0; font-size: 15px; color: #0f172a;"><?= $txt['add_to_crew'] ?></h3>
                    <p style="margin: 0 0 12px 0; font-size: 12px; color: #64748b;"><?= $txt['enter_id'] ?></p>
                    <form action="acoes_amigo.php?acao=enviar" method="POST" style="display: flex; gap: 8px; width: 100%; margin:0;">
                        <input type="number" name="amigo_id" placeholder="Ex: 14" required style="margin:0; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; flex: 1; font-family: inherit; font-size: 13px;">
                        <button type="submit" class="btn-primary" style="padding: 8px 12px; font-size: 13px;"><?= $txt['send_request'] ?></button>
                    </form>
                </div>

                <div>
                    <h4 style="margin: 0 0 12px 0; font-size: 12px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;"><?= $txt['my_friends'] ?></h4>
                    <?php if (empty($lista_amigos)): ?>
                        <p style="color: #64748b; margin: 0; font-size: 13px;"><?= $txt['empty_crew'] ?></p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($lista_amigos as $amigo): ?>
                                <div style="display: flex; flex-direction: column; gap: 8px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="font-weight: 600; color: #0f172a; font-size: 14px;">
                                        👤 <?= htmlspecialchars($amigo['nome']) ?> 
                                        <span style="font-size: 11px; color: #94a3b8; font-weight: normal; block;">#<?= $amigo['id'] ?></span>
                                    </div>
                                    <a href="amigo.php?id=<?= $amigo['id'] ?>" class="btn-primary" style="padding: 6px 10px; font-size: 12px; background-color: #0284c7; text-align: center; display: block; width: 100%; box-sizing: border-box;">
                                        <?= $txt['compare_logs'] ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CONTEÚDO PRINCIPAL DA DIREITA -->
        <div class="main-content">
            <div class="welcome-section">
                <div>
                    <p style="color: #282a2c; margin-bottom: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase;"><?= $txt['logbook'] ?></p>
                    <h1><?= $txt['hi'] ?>, <?= htmlspecialchars($primeiro_nome) ?></h1>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="openBoardModal()" class="btn-primary" style="background-color: #64748b;"><?= $txt['new_board'] ?></button>
                    <button onclick="openSessionModal()" class="btn-primary"><?= $txt['new_session'] ?></button>
                </div>
            </div>

            <!-- STATS WIDGETS -->
            <div class="dashboard-widgets">
                <div class="widget-card">
                    <div class="widget-icon icon-blue">🏄‍♂️</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['tot_sessions'] ?></div>
                        <div class="widget-value"><?= $total_sessoes ?></div>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon icon-blue">⏱️</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['water_time'] ?></div>
                        <div class="widget-value"><?= $total_minutos ?> min</div>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon icon-amber">⭐</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['avg_rating'] ?></div>
                        <div class="widget-value"><?= $media_nota ?> ★</div>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon icon-blue">⏳</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['longest_session'] ?></div>
                        <div class="widget-value"><?= $maior_sessao ?> min</div>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon icon-amber">🛹</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['most_used_board'] ?></div>
                        <div class="widget-value" style="font-size: 15px; margin-top: 6px;"><?= htmlspecialchars($prancha_mais_usada) ?></div>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon icon-emerald">🌊</div>
                    <div class="widget-info">
                        <div class="widget-label"><?= $txt['biggest_wave'] ?></div>
                        <div class="widget-value"><?= number_format($maior_onda, 1, ',', '.') ?> m</div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO DE PRANCHAS -->
            <div class="section-title"><?= $txt['my_surfboards'] ?></div>
            <div class="content-box">
                <?php if (empty($pranchas)): ?>
                    <p style="color: #64748b; margin: 0;"><?= $txt['no_boards'] ?></p>
                <?php else: ?>
                    <?php foreach ($pranchas as $board): ?>
                        <div class="board-item">
                            <div class="board-info">
                                <h3><?= htmlspecialchars($board['marca'] ?? '') ?> - <?= htmlspecialchars($board['modelo']) ?></h3>
                                <p><?= $txt['size'] ?>: <?= htmlspecialchars($board['tamanho'] ?? '') ?> | <?= $txt['volume'] ?>: <?= htmlspecialchars($board['volume'] ?? '') ?>L</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- SEÇÃO DE SESSÕES -->
            <div class="section-title"><?= $txt['all_sessions'] ?></div>
            <div class="content-box">
                <?php if (empty($sessoes)): ?>
                    <p style="color: #64748b; margin: 0;"><?= $txt['no_sessions'] ?></p>
                <?php else: ?>
                    <?php foreach ($sessoes as $sessao): ?>
                        <div class="session-item">
                            <div class="session-meta">
                                📅 <?= date('d/m/Y', strtotime($sessao['data_sessao'])) ?> | ⏱️ <?= $sessao['duracao_minutos'] ?> min | 🛹 <?= htmlspecialchars($sessao['prancha_modelo'] ?? $txt['none_f']) ?>
                            </div>
                            <div class="session-location">
                                <?= htmlspecialchars($sessao['praia']) ?> <span>- <?= htmlspecialchars($sessao['cidade']) ?>, <?= htmlspecialchars($sessao['estado']) ?></span>
                            </div>
                            <div class="session-details">
                                <strong><?= $txt['conditions'] ?>:</strong> <?= $txt['wave_height'] ?> de <?= number_format($sessao['altura_onda'] ?? 0, 1, ',', '.') ?>m com <?= $sessao['periodo_onda'] ?? 0 ?>s | 
                                <strong><?= $txt['rating'] ?>:</strong> <?= number_format($sessao['nota'], 1) ?> ★ <br>
                                <?= htmlspecialchars($sessao['observacoes'] ?? '') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div> <!-- FIM DO DASHBOARD LAYOUT WRAPPER -->

    <!-- MODAL PRANCHA -->
    <div id="modalPrancha" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeBoardModal()">&times;</span>
            <h2 style="margin-top:0; color:#0f172a; margin-bottom:20px;"><?= $txt['new_board'] ?></h2>
            <form action="salvar_prancha.php" method="POST" class="form-modal">
                <label><?= $txt['model'] ?></label>
                <input type="text" name="modelo" placeholder="<?= $txt['placeholder_model'] ?>" required>
                
                <label><?= $txt['brand'] ?></label>
                <input type="text" name="marca" placeholder="<?= $txt['placeholder_brand'] ?>" required>
                
                <div class="form-grid">
                    <div>
                        <label><?= $txt['size'] ?></label>
                        <input type="text" name="tamanho" placeholder="<?= $txt['placeholder_size'] ?>" required>
                    </div>
                    <div>
                        <label><?= $txt['volume'] ?> (<?= $txt['liters'] ?>)</label>
                        <input type="text" name="volume" placeholder="<?= $txt['placeholder_volume'] ?>" required>
                    </div>
                </div>
                <input type="hidden" name="medidas" value="">
                <button type="submit" class="btn-primary" style="width:100%; padding:12px; margin-top:10px;"><?= $txt['save_board'] ?></button>
            </form>
        </div>
    </div>

    <!-- MODAL SESSÃO -->
    <div id="modalSessao" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSessionModal()">&times;</span>
            <h2 style="margin-top:0; color:#0f172a; margin-bottom:20px;"><?= $txt['log_new_session'] ?></h2>
            <form action="salvar_sessao.php" method="POST" class="form-modal">
                <div class="form-grid">
                    <div>
                        <label><?= $txt['date'] ?></label>
                        <input type="date" name="data_sessao" required>
                    </div>
                    <div>
                        <label><?= $txt['duration'] ?></label>
                        <input type="number" name="duracao_minutos" placeholder="<?= $txt['placeholder_duration'] ?>" required>
                    </div>
                    <div>
                        <label><?= $txt['state'] ?></label>
                        <input type="text" name="estado" placeholder="<?= $txt['placeholder_state'] ?>" required>
                    </div>
                    <div>
                        <label><?= $txt['city'] ?></label>
                        <input type="text" name="cidade" placeholder="<?= $txt['placeholder_city'] ?>" required>
                    </div>
                    <div class="full-width">
                        <label><?= $txt['beach_spot'] ?></label>
                        <input type="text" name="praia" placeholder="<?= $txt['placeholder_beach'] ?>" required>
                    </div>
                    <div>
                        <label><?= $txt['wave_height'] ?></label>
                        <input type="number" step="0.1" name="altura_onda" placeholder="<?= $txt['placeholder_height'] ?>">
                    </div>
                    <div>
                        <label><?= $txt['period'] ?></label>
                        <input type="number" name="periodo_onda" placeholder="<?= $txt['placeholder_period'] ?>">
                    </div>
                    <div class="full-width">
                        <label><?= $txt['board_used'] ?></label>
                        <select name="prancha_id">
                            <option value=""><?= $txt['none_board'] ?></option>
                            <?php foreach($pranchas as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['marca'] ?? '') ?> - <?= htmlspecialchars($b['modelo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full-width">
                        <label><?= $txt['session_rating'] ?></label>
                        <input type="number" step="0.5" min="0.5" max="5" name="nota" placeholder="<?= $txt['placeholder_rating'] ?>" required>
                    </div>
                    <div class="full-width">
                        <label><?= $txt['notes_diary'] ?></label>
                        <textarea name="observacoes" rows="3" placeholder="<?= $txt['placeholder_notes'] ?>"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; padding:12px; margin-top:10px;"><?= $txt['log_session'] ?></button>
            </form>
        </div>
    </div>

    <script>
        function closeNotice() {
            const modal = document.getElementById('modalInformativoLogin');
            if (modal) modal.style.display = 'none';
        }
        function openBoardModal() { document.getElementById('modalPrancha').style.display = 'flex'; }
        function closeBoardModal() { document.getElementById('modalPrancha').style.display = 'none'; }
        function openSessionModal() { document.getElementById('modalSessao').style.display = 'flex'; }
        function closeSessionModal() { document.getElementById('modalSessao').style.display = 'none'; }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>