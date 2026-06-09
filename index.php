<?php
/**
 * ====================================================================
 * FILE: index.php
 * PURPOSE: Página de Login e Cadastro - Porta de entrada do SurfLog
 * ====================================================================
 */

require_once 'config/conexao.php';
session_start();

// Se o usuário já está logado, redireciona diretamente para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ============= BLOCO DE CADASTRO =============
    if ($acao === 'cadastrar') {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'];

        if ($nome && $email && $senha) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);
                $sucesso = "Conta criada com sucesso! Faça o login.";
            } catch (PDOException $e) {
                $erro = "Este e-mail já está cadastrado.";
            }
        } else {
            $erro = "Preencha todos os campos corretamente.";
        }
    } 
    
    // ============= BLOCO DE LOGIN =============
    if ($acao === 'login') {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'];

        if ($email && $senha) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];

                // Ativa o informativo flutuante exclusivo para o primeiro carregamento pós-login
                $_SESSION['mostrar_aviso'] = true;

                header("Location: dashboard.php");
                exit;
            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } else {
            $erro = "Por favor, insira um e-mail válido.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheSurfChronicles - Welcome</title>
    <style>
        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
        }
        .bg-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1;
            background-image: url('img/login_img.avif'); background-size: cover; background-position: center;
            filter: blur(6px) scale(1.02); 
        }
        .bg-container::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle, transparent 30%, rgba(15, 23, 42, 0.85) 95%);
            pointer-events: none;
        }
        .main-wrapper {
            position: relative; z-index: 10; width: 100%; height: 100%;
            display: grid; grid-template-columns: 1.2fr 0.8fr; box-sizing: border-box;
        }
        .info-column { display: flex; flex-direction: column; justify-content: center; padding: 0 10%; color: #ffffff; }
        .info-tag { font-size: 13px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; color: rgba(255, 255, 255, 0.7); margin-bottom: 10px; }
        .info-title { font-size: 54px; font-weight: 800; line-height: 1.1; margin: 0 0 20px 0; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .info-subtitle { font-size: 18px; line-height: 1.6; color: rgba(255, 255, 255, 0.85); margin-bottom: 40px; max-width: 500px; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; max-width: 650px; }
        .feature-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.15); padding: 20px; border-radius: 12px; }
        .feature-icon { font-size: 20px; margin-bottom: 12px; }
        .feature-card h3 { margin: 0 0 6px 0; font-size: 15px; font-weight: 600; }
        .feature-card p { margin: 0; font-size: 12px; color: rgba(255, 255, 255, 0.7); line-height: 1.4; }
        .form-column { display: flex; justify-content: center; align-items: center; padding-right: 15%; }
        .login-card {
            background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 40px 35px; border-radius: 16px; width: 100%; max-width: 380px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.35); box-sizing: border-box;
        }
        .logo-area { text-align: center; margin-bottom: 30px; }
        .logo-main { font-size: 26px; font-weight: bold; color: #0084b4; line-height: 1; }
        .logo-sub { margin-top: 4px; margin-left: 12px; font-size: 12px; letter-spacing: 7px; text-transform: uppercase; color: rgba(255, 255, 255, 0.55); line-height: 1; }
        .tabs { display: flex; border-bottom: 2px solid rgba(0, 0, 0, 0.08); margin-bottom: 25px; }
        .tab-btn { flex: 1; text-align: center; padding: 10px 0; background: none; border: none; font-size: 15px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s ease; }
        .tab-btn.active { color: #0084b4; border-bottom: 2px solid #0084b4; margin-bottom: -2px; }
        .form-content { display: none; }
        .form-content.active { display: block; }
        label { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
        input { width: 100%; padding: 11px; margin-bottom: 18px; border: 1px solid rgba(0, 0, 0, 0.15); border-radius: 8px; box-sizing: border-box; font-size: 14px; background: rgba(255, 255, 255, 0.75); color: #0f172a; }
        input:focus { outline: none; border-color: #0084b4; background: #ffffff; }
        .btn-submit { width: 100%; background-color: #0084b4; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: #006b93; }
        .msg { padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .msg-error { background-color: #fee2e2; color: #ef4444; }
        .msg-success { background-color: #dcfce7; color: #22c55e; }

        @media (max-width: 1024px) {
            .main-wrapper { grid-template-columns: 1fr; overflow-y: auto; }
            .info-column { padding: 40px 20px; align-items: center; text-align: center; }
            .form-column { padding: 20px; }
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="bg-container"></div>

    <div class="main-wrapper">
        <div class="info-column">
            <div class="info-tag">A Minimal Surf Logbook</div>
            <h1 class="info-title">Every session.<br>Every wave.</h1>
            <p class="info-subtitle">Log your surf sessions with the details that matter — board, break, swell, and how it felt.</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌊</div>
                    <h3>Wave detail</h3>
                    <p>Height, period, quality — capture conditions session to session.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3>Breaks</h3>
                    <p>Track the beach, city and state for every surf.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Progress</h3>
                    <p>See your sessions stacked up over time.</p>
                </div>
            </div>
        </div>

        <div class="form-column">
            <div class="login-card">
                <div class="logo-area">
                    <div class="logo-main">🌊 The Surf</div>
                    <div class="logo-sub">CHRONICLES</div>
                </div>

                <?php if (!empty($erro)): ?>
                    <div class="msg msg-error"><?= $erro ?></div>
                <?php endif; ?>
                <?php if (!empty($sucesso)): ?>
                    <div class="msg msg-success"><?= $sucesso ?></div>
                <?php endif; ?>

                <div class="tabs">
                    <button class="tab-btn active" onclick="mudarAba('login')">Login</button>
                    <button class="tab-btn" onclick="mudarAba('cadastro')">Sign Up</button>
                </div>

                <div id="form-login" class="form-content active">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="acao" value="login">
                        
                        <label for="email_login">Email</label>
                        <input type="email" id="email_login" name="email" placeholder="seu@email.com" required>

                        <label for="senha_login">Password</label>
                        <input type="password" id="senha_login" name="senha" placeholder="••••••••" required>

                        <button type="submit" class="btn-submit">Log In</button>
                    </form>
                </div>

                <div id="form-cadastro" class="form-content">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="acao" value="cadastrar">

                        <label for="nome_cad">Nome Completo</label>
                        <input type="text" id="nome_cad" name="nome" placeholder="Ex: Bruno Schmidt" required>
                        
                        <label for="email_cad">Email</label>
                        <input type="email" id="email_cad" name="email" placeholder="seu@email.com" required>

                        <label for="senha_cad">Password</label>
                        <input type="password" id="senha_cad" name="senha" placeholder="Crie uma senha forte" required>

                        <button type="submit" class="btn-submit">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mudarAba(tipo) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(form => form.classList.remove('active'));

            if (tipo === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('form-login').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('form-cadastro').classList.add('active');
            }
        }
    </script>
</body>
</html>