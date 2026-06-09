<?php
/**
 * ====================================================================
 * FILE: salvar_prancha.php
 * PURPOSE: Receber e Salvar Dados de Nova Prancha no Banco
 * ====================================================================
 * 
 * Esta página é chamada via POST do formulário de cadastro de pranchas
 * no dashboard. Recebe os dados, valida e insere no banco de dados.
 * 
 * Fluxo:
 * 1. Usuário preenche formulário de nova prancha
 * 2. Clica em "Salvar Prancha"
 * 3. Form envia POST para este arquivo (salvar_prancha.php)
 * 4. Dados são inseridos no banco
 * 5. Redireciona de volta para o dashboard
 */

// Importa a conexão com o banco de dados
require_once 'config/conexao.php';

// Inicia a sessão para verificar se o usuário está logado
session_start();

// ============= VERIFICAÇÃO DE SEGURANÇA =============
// Se o usuário não está logado (não tem ID na sessão)
if (!isset($_SESSION['usuario_id'])) {
    // Redireciona para a página de login
    header("Location: index.php");
    exit; // Para a execução aqui
}

// ============= PROCESSA SUBMISSÃO DO FORMULÁRIO =============
// Verifica se a requisição foi um POST (não GET, PUT, etc)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém o ID do usuário logado da sessão
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtém os dados do formulário enviados via POST
    // modelo = nome/marca da prancha (ex: "Simões Funboard")
    $modelo = $_POST['modelo'];
    
    // medidas = dimensões e volume (ex: "7.11 X 21 X 52L")
    $medidas = $_POST['medidas'];

    // ============= VALIDAÇÃO SIMPLES =============
    // Verifica se pelo menos o modelo foi preenchido
    if ($modelo) {
        // Prepara uma consulta INSERT com placeholders para segurança
        // Insere a prancha associada ao usuário logado
        $stmt = $pdo->prepare("INSERT INTO pranchas (usuario_id, modelo, medidas) VALUES (?, ?, ?)");
        
        // Executa a query com os valores reais
        // A ordem deve ser: usuario_id, modelo, medidas
        $stmt->execute([$usuario_id, $modelo, $medidas]);
    }
}

// ============= REDIRECIONAMENTO =============
// Após salvar (ou tentar salvar), redireciona para o dashboard
// O usuário verá a prancha adicionada imediatamente
header("Location: dashboard.php");

// Interrompe qualquer código posterior
exit;