<?php
/**
 * ====================================================================
 * FILE: index.php
 * PURPOSE: Ultimate All-In-One Gateway (Login, Register, Recover & Reset)
 * ====================================================================
 */

require_once 'config/conexao.php';
session_start();

// --- CONTROLE DE IDIOMA (MUDANÇA APENAS PARA SELEÇÃO DE LINGUAGEM) ---
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'pt' ? 'pt' : 'en';
}
$lang = $_SESSION['lang'] ?? 'en';

$textos = [
    'en' => [
        'info_tag' => 'A Minimal Surf Logbook',
        'info_title' => 'Every session.<br>Every wave.',
        'info_subtitle' => 'Log your surf sessions with the details that matter — board, break, swell, and how it felt.',
        'feat_wave_title' => 'Wave detail',
        'feat_wave_desc' => 'Height, period, quality — capture conditions session to session.',
        'feat_breaks_title' => 'Breaks',
        'feat_breaks_desc' => 'Track the beach, city and state for every surf.',
        'feat_prog_title' => 'Progress',
        'feat_prog_desc' => 'See your sessions stacked up over time.',
        'login_tab' => 'Login',
        'signup_tab' => 'Sign Up',
        'email_lbl' => 'Email',
        'pass_lbl' => 'Password',
        'forgot_lnk' => 'Forgot password?',
        'login_btn' => 'Log In',
        'name_lbl' => 'Full Name',
        'signup_btn' => 'Create Account',
        'recover_title' => 'Recover Password',
        'recover_desc' => "Enter your email below. We'll generate a secure token so you can choose a new password.",
        'recover_lbl' => 'Your Email Address',
        'recover_btn' => 'Generate Reset Link',
        'back_login' => '← Back to Login',
        'reset_title' => '🔑 Choose New Password',
        'new_pass_lbl' => 'New Password',
        'conf_pass_lbl' => 'Confirm New Password',
        'update_btn' => 'Update Password'
    ],
    'pt' => [
        'info_tag' => 'Um Diário de Surf Minimalista',
        'info_title' => 'Cada sessão.<br>Cada onda.',
        'info_subtitle' => 'Registre suas sessões de surf com os detalhes que importam — prancha, pico, ondulação e como se sentiu.',
        'feat_wave_title' => 'Detalhes da onda',
        'feat_wave_desc' => 'Altura, período, qualidade — capture as condições de sessão para sessão.',
        'feat_breaks_title' => 'Picos',
        'feat_breaks_desc' => 'Monitore a praia, cidade e estado de cada queda.',
        'feat_prog_title' => 'Progresso',
        'feat_prog_desc' => 'Veja seu histórico de sessões acumular ao longo do tempo.',
        'login_tab' => 'Entrar',
        'signup_tab' => 'Cadastrar',
        'email_lbl' => 'E-mail',
        'pass_lbl' => 'Senha',
        'forgot_lnk' => 'Esqueceu a senha?',
        'login_btn' => 'Entrar',
        'name_lbl' => 'Nome Completo',
        'signup_btn' => 'Criar Conta',
        'recover_title' => 'Recuperar Senha',
        'recover_desc' => 'Insira seu e-mail abaixo. Geraremos um token seguro para que você possa escolher uma nova senha.',
        'recover_lbl' => 'Seu Endereço de E-mail',
        'recover_btn' => 'Gerar Link de Recuperação',
        'back_login' => '← Voltar para o Login',
        'reset_title' => '🔑 Escolha a Nova Senha',
        'new_pass_lbl' => 'Nova Senha',
        'conf_pass_lbl' => 'Confirmar Nova Senha',
        'update_btn' => 'Atualizar Senha'
    ]
];
// --------------------------------------------------------------------

// Se o usuário já estiver logado, joga para a dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

// Descobre qual visual exibir baseado na URL (Modos: login, register, forgot, reset)
$modo = filter_input(INPUT_GET, 'modo', FILTER_DEFAULT) ?? 'login';
$token_url = filter_input(INPUT_GET, 'token', FILTER_DEFAULT);

// Se houver um token válido na URL, força o modo a ser 'reset' (Nova Senha)
if ($token_url) {
    $modo = 'reset';
    // Valida imediatamente se o token existe e não expirou
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_expira_em > NOW()");
    $stmt->execute([$token_url]);
    $usuario_token = $stmt->fetch();
    
    if (!$usuario_token) {
        $erro = "This recovery token is invalid or has expired. Please request a new link.";
        $modo = 'forgot'; // Devolve para a tela de pedir link
    }
}

// ====================================================================
// PROCESSAMENTO DOS FORMULÁRIOS (POST)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // 1. AÇÃO: CADASTRAR
    if ($acao === 'cadastrar') {
        $modo = 'register'; // Mantém a aba activa se der erro
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'];

        if ($nome && $email && $senha) {
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt_check->execute([$email]);
            
            if ($stmt_check->rowCount() > 0) {
                $erro = "This email is already registered. Try logging in!";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                    $stmt->execute([$nome, $email, $senha_hash]);
                    $sucesso = "Account created successfully! Please log in.";
                    $modo = 'login'; // Muda para o login
                } catch (PDOException $e) {
                    $erro = "An error occurred. Please try again.";
                }
            }
        } else {
            $erro = "Please fill all fields correctly.";
        }
    } 
    
    // 2. AÇÃO: LOGIN
    if ($acao === 'login') {
        $modo = 'login';
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'];

        if ($email && $senha) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['mostrar_aviso'] = true;
                header("Location: dashboard.php");
                exit;
            } else {
                $erro = "Email or password incorrect.";
            }
        } else {
            $erro = "Please enter a valid email.";
        }
    }

    // 3. AÇÃO: SOLICITAR RECUPERAÇÃO (ESQUECI A SENHA)
    if ($acao === 'solicitar_recuperacao') {
        $modo = 'forgot';
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if ($email) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt_update = $pdo->prepare("UPDATE usuarios SET token_recuperacao = ?, token_expira_em = ? WHERE id = ?");
                $stmt_update->execute([$token, $expira, $usuario['id']]);
                
                // O link agora aponta para o PRÓPRIO arquivo index.php passando o token por GET
                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $url_atual = $protocolo . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
                $link = $url_atual . "?token=" . $token;
                
                $sucesso = "Simulation: Recovery link generated!<br><br>
                            <a href='$link' style='color: #22c55e; font-weight: bold; word-break: break-all;'>Click here to reset password</a>";
            } else {
                $sucesso = "If this e-mail is registered, a recovery link has been generated.";
            }
        } else {
            $erro = "Please enter a valid email.";
        }
    }

    // 4. AÇÃO: DEFINIR NOVA SENHA
    if ($acao === 'atualizar_senha') {
        $modo = 'reset';
        $token_post = filter_input(INPUT_POST, 'token_confirmacao', FILTER_DEFAULT);
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        // Revalida o token enviado pelo formulário oculto
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_expira_em > NOW()");
        $stmt->execute([$token_post]);
        $usuario_confirmado = $stmt->fetch();

        if ($usuario_confirmado) {
            if (strlen($nova_senha) >= 6) {
                if ($nova_senha === $confirma_senha) {
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira_em = NULL WHERE id = ?");
                    $stmt_update->execute([$nova_senha_hash, $usuario_confirmado['id']]);
                    
                    $sucesso = "Password updated successfully!<br> You can log in now.";
                    $modo = 'login';
                } else {
                    $erro = "Passwords do not match.";
                }
            } else {
                $erro = "The password must be at least 6 characters long.";
            }
        } else {
            $erro = "Invalid token session or expired link.";
            $modo = 'forgot';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheSurfChronicles - Welcome</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <style>
        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0f172a;
        }
        .bg-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1;
            background-image: url('img/login_img.avif'); background-size: cover; background-position: center;
            filter: blur(6px) scale(1.02); 
        }
        .bg-container::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle, transparent 30%, rgba(15, 23, 42, 0.85) 95%); pointer-events: none;
        }
        .main-wrapper {
            position: relative; z-index: 10; width: 105%; height: 100%;
            display: grid; grid-template-columns: 1.2fr 0.8fr; box-sizing: border-box;
        }
        .info-column { display: flex; flex-direction: column; justify-content: center; padding: 0 10%; color: #ffffff; }
        .info-tag { font-size: 13px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; color: rgba(255, 255, 255, 0.7); margin-bottom: 10px; }
        .info-title { font-size: 54px; font-weight: 800; line-height: 1.1; margin: 0 0 20px 0; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .info-subtitle { font-size: 18px; line-height: 1.6; color: rgba(255, 255, 255, 0.85); margin-bottom: 40px; max-width: 500px; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; max-width: 650px; }
        .feature-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.15); padding: 20px; border-radius: 12px; }
        .feature-icon { font-size: 20px; margin-bottom: 12px; }
        .feature-card h3 { margin: 0 0 6px 0; font-size: 15px; font-weight: 600; }
        .feature-card p { margin: 0; font-size: 12px; color: rgba(255, 255, 255, 0.7); line-height: 1.4; }
        .form-column { display: flex; justify-content: center; align-items: center; padding-right: 15%; }
        
        .login-card {
            background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(20px);
            padding: 40px 35px; border-radius: 16px; width: 100%; max-width: 380px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.35); box-sizing: border-box;
        }
        .logo-area { text-align: center; margin-bottom: 30px; position: relative; }
        .logo-main { font-size: 28px; font-weight: 800; color: #0284c7; }
        .logo-sub { margin-top: 4px; margin-left: 12px; font-size: 12px; letter-spacing: 7px; text-transform: uppercase; color: rgba(255, 255, 255, 0.55); line-height: 1; }
        
        /* Chaves de idioma mantendoc layout */
        .lang-switcher { position: absolute; top: -25px; right: 0; font-size: 12px; }
        .lang-switcher a { color: #0f172a; text-decoration: none; font-weight: bold; margin-left: 5px; }
        .lang-switcher a:hover { text-decoration: underline; }

        .tabs { display: flex; border-bottom: 2px solid rgba(0, 0, 0, 0.08); margin-bottom: 25px; }
        .tab-btn { flex: 1; text-align: center; padding: 10px 0; background: none; border: none; font-size: 15px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s ease; }
        .tab-btn.active { color: #0084b4; border-bottom: 2px solid #0084b4; margin-bottom: -2px; }
        
        .form-content { display: none; }
        .form-content.active { display: block; }
        
        label { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
        input { width: 100%; padding: 11px; margin-bottom: 18px; border: 1px solid rgba(0, 0, 0, 0.15); border-radius: 8px; box-sizing: border-box; font-size: 14px; background: rgba(255, 255, 255, 0.75); color: #0f172a; }
        input:focus { outline: none; border-color: #0084b4; background: #ffffff; }
        
        .forgot-password-wrapper { text-align: right; margin-top: -12px; margin-bottom: 18px; }
        .forgot-password-link { font-size: 12px; color: #0f172a; text-decoration: none; font-weight: 600; }
        .forgot-password-link:hover { color: #006b93; text-decoration: underline; }
        
        .btn-submit { width: 100%; background-color: #0084b4; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: #006b93; }
        .btn-green { background-color: #22c55e; }
        .btn-green:hover { background-color: #16a34a; }
        
        .back-link { display: block; text-align: center; font-size: 13px; color: #0f172a; text-decoration: none; font-weight: 600; margin-top: 15px; }
        .back-link:hover { text-decoration: underline; color: #006b93; }
        
        .card-desc { font-size: 13px; color: #1e293b; margin-bottom: 20px; text-align: center; line-height: 1.4; font-weight: 500; }
        .msg { padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; line-height: 1.4; word-break: break-all; }
        .msg-error { background-color: #fee2e2; color: #ef4444; }
        .msg-success { background-color: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }

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
            <div class="info-tag"><?= $textos[$lang]['info_tag'] ?></div>
            <h1 class="info-title"><?= $textos[$lang]['info_title'] ?></h1>
            <p class="info-subtitle"><?= $textos[$lang]['info_subtitle'] ?></p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌊</div>
                    <h3><?= $textos[$lang]['feat_wave_title'] ?></h3>
                    <p><?= $textos[$lang]['feat_wave_desc'] ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3><?= $textos[$lang]['feat_breaks_title'] ?></h3>
                    <p><?= $textos[$lang]['feat_breaks_desc'] ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3><?= $textos[$lang]['feat_prog_title'] ?></h3>
                    <p><?= $textos[$lang]['feat_prog_desc'] ?></p>
                </div>
            </div>
        </div>

        <div class="form-column">
            <div class="login-card">
                <div class="logo-area">
                    <div class="lang-switcher">
                        <a href="?lang=pt">🇧🇷 PT</a> | <a href="?lang=en">🇺🇸 EN</a>
                    </div>
                    <div class="logo-main">🌊 The Surf</div>
                    <div class="logo-sub">CHRONICLES</div>
                </div>

                <?php if (!empty($erro)): ?>
                    <div class="msg msg-error"><?= $erro ?></div>
                <?php endif; ?>
                <?php if (!empty($sucesso)): ?>
                    <div class="msg msg-success"><?= $sucesso ?></div>
                <?php endif; ?>

                <?php if ($modo === 'login' || $modo === 'register'): ?>
                    <div class="tabs">
                        <button class="tab-btn <?= $modo === 'login' ? 'active' : '' ?>" onclick="switchTab('login')"><?= $textos[$lang]['login_tab'] ?></button>
                        <button class="tab-btn <?= $modo === 'register' ? 'active' : '' ?>" onclick="switchTab('register')"><?= $textos[$lang]['signup_tab'] ?></button>
                    </div>

                    <div id="form-login" class="form-content <?= $modo === 'login' ? 'active' : '' ?>">
                        <form action="index.php?modo=login" method="POST">
                            <input type="hidden" name="acao" value="login">
                            
                            <label for="email_login"><?= $textos[$lang]['email_lbl'] ?></label>
                            <input type="email" id="email_login" name="email" placeholder="your@email.com" required>

                            <label for="senha_login"><?= $textos[$lang]['pass_lbl'] ?></label>
                            <input type="password" id="senha_login" name="senha" placeholder="••••••••" required>

                            <div class="forgot-password-wrapper">
                                <a href="index.php?modo=forgot" class="forgot-password-link"><?= $textos[$lang]['forgot_lnk'] ?></a>
                            </div>

                            <button type="submit" class="btn-submit"><?= $textos[$lang]['login_btn'] ?></button>
                        </form>
                    </div>

                    <div id="form-register" class="form-content <?= $modo === 'register' ? 'active' : '' ?>">
                        <form action="index.php?modo=register" method="POST">
                            <input type="hidden" name="acao" value="cadastrar">

                            <label for="nome_cad"><?= $textos[$lang]['name_lbl'] ?></label>
                            <input type="text" id="nome_cad" name="nome" placeholder="Ex: Bruno Schmidt" required>
                            
                            <label for="email_cad"><?= $textos[$lang]['email_lbl'] ?></label>
                            <input type="email" id="email_cad" name="email" placeholder="your@email.com" required>

                            <label for="senha_cad"><?= $textos[$lang]['pass_lbl'] ?></label>
                            <input type="password" id="senha_cad" name="senha" placeholder="Create a strong password" required>

                            <button type="submit" class="btn-submit"><?= $textos[$lang]['signup_btn'] ?></button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($modo === 'forgot'): ?>
                    <div id="form-forgot">
                        <form action="index.php?modo=forgot" method="POST">
                            <input type="hidden" name="acao" value="solicitar_recuperacao">
                            
                            <div class="card-desc" style="text-align: center; font-weight: bold; font-size: 15px; margin-bottom: 5px;"><?= $textos[$lang]['recover_title'] ?></div>
                            <div class="card-desc"><?= $textos[$lang]['recover_desc'] ?></div>
                            
                            <label for="email_forgot"><?= $textos[$lang]['recover_lbl'] ?></label>
                            <input type="email" id="email_forgot" name="email" placeholder="your@email.com" required>

                            <button type="submit" class="btn-submit"><?= $textos[$lang]['recover_btn'] ?></button>
                            <a href="index.php?modo=login" class="back-link"><?= $textos[$lang]['back_login'] ?></a>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($modo === 'reset' && !empty($token_url)): ?>
                    <div id="form-reset">
                        <form action="index.php?token=<?= urlencode($token_url) ?>" method="POST">
                            <input type="hidden" name="acao" value="atualizar_senha">
                            <input type="hidden" name="token_confirmacao" value="<?= htmlspecialchars($token_url) ?>">
                            
                            <div class="card-desc" style="text-align: center; font-weight: bold; font-size: 15px; margin-bottom: 10px;"><?= $textos[$lang]['reset_title'] ?></div>
                            
                            <label for="nova_senha"><?= $textos[$lang]['new_pass_lbl'] ?></label>
                            <input type="password" id="nova_senha" name="nova_senha" placeholder="At least 6 characters" required>

                            <label for="confirma_senha"><?= $textos[$lang]['conf_pass_lbl'] ?></label>
                            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="••••••••" required>

                            <button type="submit" class="btn-submit btn-green"><?= $textos[$lang]['update_btn'] ?></button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(form => form.classList.remove('active'));

            if (type === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('form-login').classList.add('active');
                window.history.replaceState(null, null, "index.php?modo=login");
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('form-register').classList.add('active');
                window.history.replaceState(null, null, "index.php?modo=register");
            }
        }
    </script>
</body>
</html>