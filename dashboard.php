<?php
/**
 * ====================================================================
 * FILE: dashboard.php
 * PURPOSE: Main User Dashboard - View Surf Logbook
 * ====================================================================
 */

require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

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
$prancha_mais_usada = $prancha_top ? $prancha_top['modelo'] : "None yet";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheSurfChronicles - Dashboard</title>
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

        .logout-btn { 
            color: #64748b; 
            text-decoration: none; 
            font-weight: 500; 
        }

        .logout-btn:hover { 
            color: #ef4444; 
        }

        .main-container { 
        max-width: 1000px; 
        margin: 40px auto; 
        padding: 0 20px; 
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
        color: #0f172a; 
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
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
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
        color: #64748b; 
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

    <?php if ($exibir_informativo): ?>
    <div id="modalInformativoLogin" class="modal-info-login">
        <div class="info-login-content">
            <div style="font-size: 40px; margin-bottom: 15px;">📢</div>
            <h2>What's New in SurfLog!</h2>
            <p>
                Hey master! We've updated the system. Now you can register the 
                <strong>Wave Height</strong> and <strong>Period</strong> in each session, 
                plus track new records right on your panel. Aloha and good waves! 🌊🤙
            </p>
            <button class="btn-vamos-la" onclick="closeNotice()">Got it</button>
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
                <a href="admin.php" style="color: #ef4444; font-weight: bold; text-decoration: none; margin-right: 15px;">⚙️ Admin Panel</a>
            <?php endif; ?>
            <span>@<?= htmlspecialchars($usuario_nome) ?></span>
            <a href="logout.php" class="logout-btn">Log out ↗</a>
        </div>
    </div>

    <div class="main-container">
        <div class="welcome-section">
            <div>
                <p style="color: #64748b; margin-bottom: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Logbook</p>
                <h1>Hi, <?= htmlspecialchars($primeiro_nome) ?></h1>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="openBoardModal()" class="btn-primary" style="background-color: #64748b;">+ New Board</button>
                <button onclick="openSessionModal()" class="btn-primary">+ New Session</button>
            </div>
        </div>

        <div class="dashboard-widgets">
            <div class="widget-card">
                <div class="widget-icon icon-blue">🏄‍♂️</div>
                <div class="widget-info">
                    <div class="widget-label">Total Sessions</div>
                    <div class="widget-value"><?= $total_sessoes ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon icon-blue">⏱️</div>
                <div class="widget-info">
                    <div class="widget-label">Water Time</div>
                    <div class="widget-value"><?= $total_minutos ?> min</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon icon-amber">⭐</div>
                <div class="widget-info">
                    <div class="widget-label">Average Rating</div>
                    <div class="widget-value"><?= $media_nota ?> ★</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon icon-blue">⏳</div>
                <div class="widget-info">
                    <div class="widget-label">Longest Session</div>
                    <div class="widget-value"><?= $maior_sessao ?> min</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon icon-amber">🛹</div>
                <div class="widget-info">
                    <div class="widget-label">Most Used Board</div>
                    <div class="widget-value" style="font-size: 16px; margin-top: 6px;"><?= htmlspecialchars($prancha_mais_usada) ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon icon-emerald">🌊</div>
                <div class="widget-info">
                    <div class="widget-label">Biggest Wave</div>
                    <div class="widget-value"><?= number_format($maior_onda, 1, ',', '.') ?> m</div>
                </div>
            </div>
        </div>

        <div class="section-title">My Surfboards</div>
        <div class="content-box">
            <?php if (empty($pranchas)): ?>
                <p style="color: #64748b; margin: 0;">No boards registered yet.</p>
            <?php else: ?>
                <?php foreach ($pranchas as $board): ?>
                    <div class="board-item">
                        <div class="board-info">
                            <h3><?= htmlspecialchars($board['marca'] ?? '') ?> - <?= htmlspecialchars($board['modelo']) ?></h3>
                            <p>Tamanho: <?= htmlspecialchars($board['tamanho'] ?? '') ?> | Volume: <?= htmlspecialchars($board['volume'] ?? '') ?>L</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section-title">All Sessions</div>
        <div class="content-box">
            <?php if (empty($sessoes)): ?>
                <p style="color: #64748b; margin: 0;">No sessions recorded yet.</p>
            <?php else: ?>
                <?php foreach ($sessoes as $sessao): ?>
                    <div class="session-item">
                        <div class="session-meta">
                            📅 <?= date('d/m/Y', strtotime($sessao['data_sessao'])) ?> | ⏱️ <?= $sessao['duracao_minutos'] ?> min | 🛹 <?= htmlspecialchars($sessao['prancha_modelo'] ?? 'Nenhuma') ?>
                        </div>
                        <div class="session-location">
                            <?= htmlspecialchars($sessao['praia']) ?> <span>- <?= htmlspecialchars($sessao['cidade']) ?>, <?= htmlspecialchars($sessao['estado']) ?></span>
                        </div>
                        <div class="session-details">
                            <strong>Condições:</strong> Onda de <?= number_format($sessao['altura_onda'] ?? 0, 1, ',', '.') ?>m com <?= $sessao['periodo_onda'] ?? 0 ?>s | 
                            <strong>Nota:</strong> <?= number_format($sessao['nota'], 1) ?> ★ <br>
                            <?= htmlspecialchars($sessao['observacoes'] ?? '') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalPrancha" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeBoardModal()">&times;</span>
            <h2 style="margin-top:0; color:#0f172a; margin-bottom:20px;">New Board</h2>
            <form action="salvar_prancha.php" method="POST" class="form-modal">
                <label>Model</label>
                <input type="text" name="modelo" placeholder="Ex: SF3, Monsta Box" required>
                
                <label>Brand</label>
                <input type="text" name="marca" placeholder="Ex: Tokoro, JS, Pyzel" required>
                
                <div class="form-grid">
                    <div>
                        <label>Size</label>
                        <input type="text" name="tamanho" placeholder="Ex: 5'11" required>
                    </div>
                    <div>
                        <label>Volume (Liters)</label>
                        <input type="text" name="volume" placeholder="Ex: 29.5" required>
                    </div>
                </div>
                <input type="hidden" name="medidas" value="">
                <button type="submit" class="btn-primary" style="width:100%; padding:12px; margin-top:10px;">Save Board</button>
            </form>
        </div>
    </div>

    <div id="modalSessao" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSessionModal()">&times;</span>
            <h2 style="margin-top:0; color:#0f172a; margin-bottom:20px;">Log New Session</h2>
            <form action="salvar_sessao.php" method="POST" class="form-modal">
                <div class="form-grid">
                    <div>
                        <label>Date</label>
                        <input type="date" name="data_sessao" required>
                    </div>
                    <div>
                        <label>Duration (minutes)</label>
                        <input type="number" name="duracao_minutos" placeholder="Ex: 90" required>
                    </div>
                    <div>
                        <label>State</label>
                        <input type="text" name="estado" placeholder="Ex: SC" required>
                    </div>
                    <div>
                        <label>City</label>
                        <input type="text" name="cidade" placeholder="Ex: Imbituba" required>
                    </div>
                    <div class="full-width">
                        <label>Beach / Spot</label>
                        <input type="text" name="praia" placeholder="Ex: Rosa Beach (South Corner)" required>
                    </div>
                    <div>
                        <label>Wave Height (m)</label>
                        <input type="number" step="0.1" name="altura_onda" placeholder="Ex: 1.5">
                    </div>
                    <div>
                        <label>Period (s)</label>
                        <input type="number" name="periodo_onda" placeholder="Ex: 11">
                    </div>
                    <div class="full-width">
                        <label>Board Used</label>
                        <select name="prancha_id">
                            <option value="">None / Other</option>
                            <?php foreach($pranchas as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['marca'] ?? '') ?> - <?= htmlspecialchars($b['modelo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full-width">
                        <label>Session Rating</label>
                        <input type="number" step="0.5" min="0.5" max="5" name="nota" placeholder="From 0.5 to 5.0" required>
                    </div>
                    <div class="full-width">
                        <label>Notes / Diary</label>
                        <textarea name="observacoes" rows="3" placeholder="How was the wind? And the tide? Big barrels?"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; padding:12px; margin-top:10px;">Log Session</button>
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