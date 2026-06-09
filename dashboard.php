<?php
// dashboard.php
require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

$partes_nome = explode(' ', $usuario_nome);
$primeiro_nome = $partes_nome[0];

// 1. BUSCAR ESTATÍSTICAS DO DASHBOARD (Gerais Acumuladas)
$stmt = $pdo->prepare("SELECT COUNT(*) as total_sessoes, SUM(duracao_minutos) as total_minutos, AVG(nota) as media_nota FROM sessoes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estatisticas = $stmt->fetch();

$total_sessoes = $estatisticas['total_sessoes'] ?? 0;
$total_minutos = $estatisticas['total_minutos'] ?? 0;
$media_nota = $estatisticas['media_nota'] ? number_format($estatisticas['media_nota'], 1, '.', '') : '0.0';

// ==========================================
// NOVAS CONSULTAS PARA OS CARDS DE RESUMO (Recordes e Preferências)
// ==========================================
// A) Sessão mais longa (Maior tempo na água)
$stmt_longa = $pdo->prepare("SELECT MAX(duracao_minutos) as maior_tempo FROM sessoes WHERE usuario_id = ?");
$stmt_longa->execute([$usuario_id]);
$sessao_longa = $stmt_longa->fetch();
$maior_sessao = $sessao_longa['maior_tempo'] ?? 0;

// B) Prancha mais utilizada (CORRIGIDO: usando LEFT JOIN para evitar crash se houver prancha nula)
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
$prancha_mais_usada = $prancha_top ? $prancha_top['modelo'] : "Nenhuma ainda";

// C) Maior Onda Surfada (Cálculo matemático direto na nova coluna)
$stmt_onda_top = $pdo->prepare("SELECT MAX(altura_onda) as maior_onda FROM sessoes WHERE usuario_id = ?");
$stmt_onda_top->execute([$usuario_id]);
$onda_top = $stmt_onda_top->fetch();
$maior_onda = $onda_top['maior_onda'] ?? 0.0;


// 2. BUSCAR PRANCHAS DO USUÁRIO
$stmt_pranchas = $pdo->prepare("SELECT * FROM pranchas WHERE usuario_id = ?");
$stmt_pranchas->execute([$usuario_id]);
$pranchas = $stmt_pranchas->fetchAll();

// 3. BUSCAR SESSÕES DE SURF DO USUÁRIO
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SurfLog - Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; color: #1e293b; }
        
        .navbar { background-color: #ffffff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .logo { font-size: 20px; font-weight: bold; color: #0084b4; display: flex; align-items: center; gap: 8px; }
        .user-menu { display: flex; align-items: center; gap: 20px; font-size: 14px; }
        .logout-btn { color: #64748b; text-decoration: none; font-weight: 500; }
        .logout-btn:hover { color: #ef4444; }

        .main-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .welcome-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-section h1 { margin: 0; font-size: 28px; color: #0f172a; }
        
        .btn-primary { background-color: #0084b4; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background-color: #006b93; }

        /* AJUSTADO: Agora suporta dinamicamente de 3 a 4 colunas dependendo da tela */
        /* AJUSTADO: Alinhamento perfeito para 3 colunas por linha (totalizando 2 linhas de cards) */
        .dashboard-widgets { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 20px; 
        margin-bottom: 40px; 
    }
        .widget-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .widget-icon { font-size: 26px; padding: 8px; border-radius: 10px; display: flex; justify-content: center; align-items: center; width: 38px; height: 38px; }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-amber { background: #fef3c7; color: #d97706; }
        .icon-emerald { background: #dcfce7; color: #059669; }
        
        .widget-info { display: flex; flex-direction: column; }
        .widget-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px; }
        .widget-value { font-size: 22px; font-weight: bold; color: #0f172a; margin-top: 3px; }

        .section-title { font-size: 14px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 15px; letter-spacing: 0.5px; }
        .content-box { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 40px; }
        
        .board-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .board-item:last-child { border-bottom: none; }
        .board-info h3 { margin: 0; font-size: 16px; color: #0f172a; }
        .board-info p { margin: 5px 0 0 0; font-size: 14px; color: #64748b; }
        
        .session-item { border-bottom: 1px solid #f1f5f9; padding: 20px 0; }
        .session-item:last-child { border-bottom: none; }
        .session-meta { font-size: 13px; color: #64748b; margin-bottom: 8px; }
        .session-location { font-size: 16px; font-weight: bold; color: #0f172a; margin-bottom: 6px; }
        .session-location span { color: #64748b; font-weight: normal; font-size: 14px; }
        .session-details { font-size: 14px; color: #475569; line-height: 1.6; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); justify-content: center; align-items: center; overflow-y: auto; }
        .modal-content { background-color: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); position: relative; margin: 20px auto; }
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #94a3b8; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        
        .form-modal label { display: block; margin-bottom: 5px; color: #475569; font-size: 14px; font-weight: 600; }
        .form-modal input, .form-modal select, .form-modal textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; }

        .rating-select { display: flex; gap: 5px; font-size: 24px; cursor: pointer; margin-bottom: 15px; color: #cbd5e1; }
        .rating-select span:hover, .rating-select span.active { color: #0084b4; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo">🌊 SurfLog</div>
        <div class="user-menu">
            <?php
            $stmt_check = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['usuario_id']]);
            $check_admin = $stmt_check->fetch();
            if ($check_admin && $check_admin['is_admin'] == 1):
            ?>
                <a href="admin.php" style="color: #ef4444; font-weight: bold; text-decoration: none; margin-right: 15px;">⚙️ Painel Admin</a>
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
            <a href="javascript:void(0)" onclick="abrirModalSessao()" class="btn-primary">+ New session</a>
        </div>

        <div class="dashboard-widgets">
            
            <div class="widget-card">
                <div class="widget-icon icon-blue">🏄‍♂️</div>
                <div class="widget-info">
                    <div class="widget-label">Total Sessões</div>
                    <div class="widget-value"><?= $total_sessoes ?></div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-icon icon-blue">⏱️</div>
                <div class="widget-info">
                    <div class="widget-label">Tempo na Água</div>
                    <div class="widget-value"><?= $total_minutos ?> min</div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-icon icon-amber">⭐</div>
                <div class="widget-info">
                    <div class="widget-label">Nota Média</div>
                    <div class="widget-value"><?= $media_nota ?> ★</div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-icon icon-blue">⏳</div>
                <div class="widget-info">
                    <div class="widget-label">Sessão Mais Longa</div>
                    <div class="widget-value"><?= $maior_sessao ?> min</div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-icon icon-amber">🛹</div>
                <div class="widget-info">
                    <div class="widget-label">Prancha Mais Usada</div>
                    <div class="widget-value" style="font-size: 16px; margin-top: 6px;"><?= htmlspecialchars($prancha_mais_usada) ?></div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-icon icon-emerald">🌊</div>
                <div class="widget-info">
                    <div class="widget-label">Maior Onda</div>
                    <div class="widget-value"><?= number_format($maior_onda, 1, ',', '.') ?> m</div>
                </div>
            </div>

        </div>

        <div class="section-title">My Surfboards</div>
        <div class="content-box">
            <?php if(empty($pranchas)): ?>
                <p style="color: #94a3b8; margin: 0 0 15px 0;">Nenhuma prancha registrada ainda.</p>
            <?php else: ?>
                <?php foreach($pranchas as $board): ?>
                    <div class="board-item">
                        <div class="board-info">
                            <h3><?= htmlspecialchars($board['modelo']) ?></h3>
                            <p><?= htmlspecialchars($board['medidas'] ?? '') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="margin-top: 15px;">
                <a href="javascript:void(0)" onclick="abrirModalPrancha()" class="btn-primary" style="padding: 8px 15px; font-size: 14px;">+ Add board</a>
            </div>
        </div>

        <div class="section-title">All Sessions</div>
        <div class="content-box">
            <?php if(empty($sessoes)): ?>
                <p style="color: #94a3b8; margin: 0;">Nenhuma sessão de surf registrada ainda.</p>
            <?php else: ?>
                <?php foreach($sessoes as $sessao): ?>
                    <div class="session-item">
                        <div class="session-meta">
                            <?= date('d/m/Y', strtotime($sessao['data_sessao'])) ?> • <?= $sessao['duracao_minutos'] ?> min • ★ <?= number_format($sessao['nota'], 1) ?>
                        </div>
                        <div class="session-location">
                            <?= htmlspecialchars($sessao['praia']) ?> - <?= htmlspecialchars($sessao['estado']) ?> 
                            <span>· <?= htmlspecialchars($sessao['cidade']) ?></span>
                        </div>
                        <div class="session-details">
                            <strong>Prancha:</strong> <?= htmlspecialchars($sessao['prancha_modelo'] ?? 'Nenhuma') ?> <br>
                            <strong>Condições do Mar:</strong> 
                            <?php if (!empty($sessao['altura_onda'])): ?>
                                <?= number_format($sessao['altura_onda'], 1, ',', '.') ?>m 
                                <?php if (!empty($sessao['periodo_onda'])): ?>
                                    com <?= $sessao['periodo_onda'] ?>s de período
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">Não informadas</span>
                            <?php endif; ?> <br>
                            <strong>Observações:</strong> <?= htmlspecialchars($sessao['observacoes'] ?? '') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalPrancha" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="fecharModalPrancha()">&times;</span>
            <h2 style="margin-top: 0; color: #0f172a; margin-bottom: 20px;">Nova Prancha</h2>
            <form action="salvar_prancha.php" method="POST" class="form-modal">
                <label>Modelo / Marca</label>
                <input type="text" name="modelo" placeholder="Ex: Simoes Funboard" required>
                <label>Medidas / Volume</label>
                <input type="text" name="medidas" placeholder="Ex: 7.11 X 21 X 52L">
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Salvar Prancha</button>
            </form>
        </div>
    </div>

    <div id="modalSessao" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="fecharModalSessao()">&times;</span>
            <h2 style="margin-top: 0; color: #0f172a; margin-bottom: 20px;">Nova Sessão de Surf</h2>
            
            <form action="salvar_sessao.php" method="POST" class="form-modal">
                <div class="form-grid">
                    <div>
                        <label>Data</label>
                        <input type="date" name="data_sessao" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label>Tempo (em minutos)</label>
                        <input type="number" name="duracao_minutos" placeholder="Ex: 60" required>
                    </div>
                    <div>
                        <label>Estado</label>
                        <input type="text" name="estado" placeholder="Ex: Santa Catarina" required>
                    </div>
                    <div>
                        <label>Cidade</label>
                        <input type="text" name="cidade" placeholder="Ex: Imbituba" required>
                    </div>
                    <div class="full-width">
                        <label>Nome da Praia</label>
                        <input type="text" name="praia" placeholder="Ex: Praia do Rosa - Norte" required>
                    </div>
                    <div class="full-width">
                        <label>Prancha Utilizada</label>
                        <select name="prancha_id">
                            <option value="">Selecione uma prancha...</option>
                            <?php foreach($pranchas as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['modelo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Altura da Onda (metros)</label>
                        <input type="number" name="altura_onda" step="0.1" min="0" placeholder="Ex: 1.5">
                    </div>
                    <div>
                        <label>Período (segundos)</label>
                        <input type="number" name="periodo_onda" min="0" placeholder="Ex: 11">
                    </div>
                    
                    <div class="full-width">
                        <label>Nota da Sessão (0.5 a 5.0)</label>
                        <input type="number" name="nota" step="0.5" min="0.5" max="5" placeholder="Ex: 4.5" required>
                    </div>
                    <div class="full-width">
                        <label>Observações</label>
                        <textarea name="observacoes" rows="3" placeholder="Como foi a queda?"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">Salvar Sessão</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModalPrancha() { document.getElementById('modalPrancha').style.display = 'flex'; }
        function fecharModalPrancha() { document.getElementById('modalPrancha').style.display = 'none'; }
        
        function abrirModalSessao() { document.getElementById('modalSessao').style.display = 'flex'; }
        function fecharModalSessao() { document.getElementById('modalSessao').style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalPrancha')) fecharModalPrancha();
            if (event.target == document.getElementById('modalSessao')) fecharModalSessao();
        }
    </script>
</body>
</html>