<?php
/**
 * FILE: mudar_idioma.php
 * PURPOSE: Controller to switch current language session
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // Valida se o idioma enviado é suportado pelo sistema
    if (in_array($lang, ['en', 'pt'])) {
        $_SESSION['lang'] = $lang;
    }
}

// Redireciona de volta para a página anterior ou para o dashboard se falhar
$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $back);
exit;