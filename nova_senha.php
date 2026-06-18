<?php
/**
 * ====================================================================
 * FILE: nova_senha.php
 * PURPOSE: Validate token and update user password
 * ====================================================================
 */

require_once 'config/conexao.php';
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

// Captura o token vindo da URL
$token = filter_input(INPUT_GET, 'token', FILTER_DEFAULT);

if (!$token) {
    die("Access denied. Invalid or missing token.");
}

// Verifica se o token existe no banco E se a data limite é maior que o momento atual (NOW)
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_expira_em > NOW()");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

// Se não achar nada, o token ou está incorreto ou já passou de 1 hora
if (!$usuario) {
    die("This recovery token is invalid or has already expired. Please request a new link.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (strlen($nova_senha) >= 6) {
        if ($nova_senha === $confirma_senha) {
            // Criptografa a nova senha gerada
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualiza a senha no banco e DELETA o token colocando NULL para nunca mais ser reutilizado
            $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira_em = NULL WHERE id = ?");
            $stmt_update->execute([$nova_senha_hash, $usuario['id']]);
            
            // Redireciona para a página de login informando o sucesso
            header("Location: index.php");
            exit;
        } else {
            $erro = "Passwords do not match. Please try again.";
        }
    } else {
        $erro = "The password must be at least 6 characters long.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheSurfChronicles - Change Password</title>
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
            background: radial-gradient(circle, transparent 30%, rgba(15, 23, 42, 0.85) 95%);
        }
        .main-wrapper { position: relative; z-index: 10; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; }
        .login-card {
            background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(20px);
            padding: 40px 35px; border-radius: 16px; width: 100%; max-width: 400px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.35); box-sizing: border-box;
        }
        .logo-area { text-align: center; margin-bottom: 25px; }
        .logo-main { font-size: 24px; font-weight: 800; color: #0284c7; }
        label { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
        input { 
            width: 100%; padding: 11px; margin-bottom: 18px; border: 1px solid rgba(0, 0, 0, 0.15); 
            border-radius: 8px; box-sizing: border-box; font-size: 14px; background: rgba(255, 255, 255, 0.75); color: #0f172a; 
        }
        input:focus { outline: none; border-color: #0084b4; background: #ffffff; }
        .btn-submit { 
            width: 100%; background-color: #22c55e; color: white; border: none; padding: 12px; 
            border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;
        }
        .btn-submit:hover { background-color: #16a34a; }
        .msg { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .msg-error { background-color: #fee2e2; color: #ef4444; }
    </style>
</head>
<body>
    <div class="bg-container"></div>
    <div class="main-wrapper">
        <div class="login-card">
            <div class="logo-area">
                <div class="logo-main">🔑 New Password</div>
            </div>

            <?php if (!empty($erro)): ?>
                <div class="msg msg-error"><?= $erro ?></div>
            <?php endif; ?>

            <form action="nova_senha.php?token=<?= urlencode($token) ?>" method="POST">
                <label for="nova_senha">Choose a new password</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="At least 6 characters" required>

                <label for="confirma_senha">Confirm your new password</label>
                <input type="password" id="confirma_senha" name="confirma_senha" placeholder="••••••••" required>

                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>