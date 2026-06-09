<?php
/**
 * ====================================================================
 * FILE: logout.php
 * PURPOSE: Encerrar a Sessão do Usuário
 * ====================================================================
 * 
 * Este arquivo é muito simples mas crucial para segurança.
 * Quando o usuário clica em "Log out", esta página:
 * 1. Inicia a sessão (para acessar dados dela)
 * 2. Limpa todos os dados da sessão
 * 3. Destrói a sessão completamente
 * 4. Redireciona para a página de login
 * 
 * Sem isso, o usuário continuaria logado após clicar em logout.
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