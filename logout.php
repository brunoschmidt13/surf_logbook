<?php
/**
 * ====================================================================
 * FILE: logout.php
 * PURPOSE: End User Session
 * ====================================================================
 * 
 * This file is very simple but crucial for security.
 * When user clicks "Log out", this page:
 * 1. Starts session (to access its data)
 * 2. Clears all session data
 * 3. Completely destroys session
 * 4. Redirects to login page
 * 
 * Without this, user would stay logged in after clicking logout.
 */

// Inicia a sessão para poder acessar e modificar seus dados
session_start();

// Remove todas as variáveis de sessão (como $_SESSION['usuario_id'])
session_unset();

// Destrói a sessão completamente (apaga cookie de sessão do navegador)
session_destroy();

// Redireciona o usuário de volta para a página de login
header("Location: index.php");

// Interrompe qualquer código posterior
exit;
?>