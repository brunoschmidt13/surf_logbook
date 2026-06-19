<?php
/**
 * FILE: amigo.php
 * PURPOSE: Compare stats between Logged User and Friend
 */

require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$amigo_id = intval($_GET['id'] ?? 0);

// Certificar que eles realmente são amigos ativos antes de mostrar os dados
$stmt = $pdo->prepare("SELECT id FROM amizades WHERE status = 'aceito' AND ((usuario_origem_id = ? AND usuario_destino_id = ?) OR (usuario_origem_id = ? AND usuario_destino_id = ?))");
$stmt->execute([$usuario_id, $amigo_id, $amigo_id, $usuario_id]);
if (!$stmt->fetch()) {
    die("You must be friends to view this dashboard.");
}

// Lógica de Idioma baseada na sessão
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'back_dashboard' => '← Back to Dashboard',
        'comparison'     => 'Lineup Comparison',
        'subtitle'       => 'Comparing your logs with',
        'total_sessions' => 'Total Surf Sessions',
        'water_time'     => 'Total Water Time',
        'biggest_wave'   => 'Biggest Wave Conquered',
        'you'            => 'You',
        'buddy'          => 'Buddy'
    ],
    'pt' => [
        'back_dashboard' => '← Voltar ao Dashboard',
        'comparison'     => 'Comparação de Lineup',
        'subtitle'       => 'Comparando seus registros com',
        'total_sessions' => 'Total de Sessões de Surf',
        'water_time'     => 'Tempo Total de Água',
        'biggest_wave'   => 'Maior Onda Conquistada',
        'you'            => 'Você',
        'buddy'          => 'Parceiro'
    ],
    'es' => [
        'back_dashboard' => '← Volver al Dashboard',
        'comparison'     => 'Comparación de Lineup',
        'subtitle'       => 'Comparando tus registros con',
        'total_sessions' => 'Total de Sesiones de Surf',
        'water_time'     => 'Tiempo Total en el Agua',
        'biggest_wave'   => 'Ola Más Grande Conquistada',
        'you'            => 'Tú',
        'buddy'          => 'Compañero'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

// Pegar dados do Amigo
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$amigo_id]);
$dados_amigo = $stmt->fetch();
$amigo_nome = $dados_amigo['nome'] ?? $txt['buddy'];

// FUNÇÃO PARA BUSCAR ESTATÍSTICAS
function buscarStats($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessoes, 
            COALESCE(SUM(duracao_minutos), 0) as total_minutos, 
            COALESCE(MAX(altura_onda), 0.0) as maior_onda 
        FROM sessoes 
        WHERE usuario_id = ?
    ");
    $id = intval($id);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

$meu_stats = buscarStats($pdo, $usuario_id);
$amigo_stats = buscarStats($pdo, $amigo_id);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lineup Battle - <?= htmlspecialchars($amigo_nome) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(rgba(15,23,42,0.8), rgba(15,23,42,0.8)), url('img/dash_background.jpg') center/cover fixed;
            color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .back-link { color: #38bdf8; text-decoration: none; font-weight: 600; display: inline-block; margin-bottom: 20px; }
        .header-battle { text-align: center; margin-bottom: 40px; }
        .header-battle h1 { font-size: 32px; color: #fff; margin: 5px 0; }
        .compare-grid { display: grid; grid-template-columns: 1fr; gap: 25px; }
        .battle-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
        }
        .battle-title { text-align: center; font-size: 13px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 1px; margin-bottom: 15px; }
        .versus-row { display: flex; justify-content: space-between; align-items: center; }
        .side { flex: 1; text-align: center; }
        .side.me { color: #38bdf8; }
        .side.buddy { color: #f43f5e; }
        .label { font-size: 12px; color: #94a3b8; text-transform: uppercase; }
        .val { font-size: 28px; font-weight: bold; margin-top: 5px; }
        .vs-circle { background: rgba(255,255,255,0.15); font-weight: 800; font-size: 14px; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; margin: 0 15px; }
        .winner { position: relative; }
        .winner::after { content: " 👑"; position: absolute; font-size: 16px; top: -15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link"><?= $txt['back_dashboard'] ?></a>
        
        <div class="header-battle">
            <div style="font-size: 40px;">⚔️</div>
            <h1><?= $txt['comparison'] ?></h1>
            <p style="color: #94a3b8; margin: 0;"><?= $txt['subtitle'] ?> <strong><?= htmlspecialchars($amigo_nome) ?></strong></p>
        </div>

        <div class="compare-grid">
            <div class="battle-card">
                <div class="battle-title"><?= $txt['total_sessions'] ?></div>
                <div class="versus-row">
                    <div class="side me <?= ($meu_stats['total_sessoes'] >= $amigo_stats['total_sessoes'] && $meu_stats['total_sessoes'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= $txt['you'] ?></div>
                        <div class="val"><?= $meu_stats['total_sessoes'] ?></div>
                    </div>
                    <div class="vs-circle">VS</div>
                    <div class="side buddy <?= ($amigo_stats['total_sessoes'] >= $meu_stats['total_sessoes'] && $amigo_stats['total_sessoes'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= htmlspecialchars(explode(' ', $amigo_nome)[0]) ?></div>
                        <div class="val"><?= $amigo_stats['total_sessoes'] ?></div>
                    </div>
                </div>
            </div>

            <div class="battle-card">
                <div class="battle-title"><?= $txt['water_time'] ?></div>
                <div class="versus-row">
                    <div class="side me <?= ($meu_stats['total_minutos'] >= $amigo_stats['total_minutos'] && $meu_stats['total_minutos'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= $txt['you'] ?></div>
                        <div class="val"><?= $meu_stats['total_minutos'] ?> <span style="font-size: 14px;">min</span></div>
                    </div>
                    <div class="vs-circle">VS</div>
                    <div class="side buddy <?= ($amigo_stats['total_minutos'] >= $meu_stats['total_minutos'] && $amigo_stats['total_minutos'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= htmlspecialchars(explode(' ', $amigo_nome)[0]) ?></div>
                        <div class="val"><?= $amigo_stats['total_minutos'] ?> <span style="font-size: 14px;">min</span></div>
                    </div>
                </div>
            </div>

            <div class="battle-card">
                <div class="battle-title"><?= $txt['biggest_wave'] ?></div>
                <div class="versus-row">
                    <div class="side me <?= ($meu_stats['maior_onda'] >= $amigo_stats['maior_onda'] && $meu_stats['maior_onda'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= $txt['you'] ?></div>
                        <div class="val"><?= number_format($meu_stats['maior_onda'], 1, ',', '.') ?> <span style="font-size: 14px;">m</span></div>
                    </div>
                    <div class="vs-circle">VS</div>
                    <div class="side buddy <?= ($amigo_stats['maior_onda'] >= $meu_stats['maior_onda'] && $amigo_stats['maior_onda'] > 0) ? 'winner' : '' ?>">
                        <div class="label"><?= htmlspecialchars(explode(' ', $amigo_nome)[0]) ?></div>
                        <div class="val"><?= number_format($amigo_stats['maior_onda'], 1, ',', '.') ?> <span style="font-size: 14px;">m</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>