<?php
/**
 * ====================================================================
 * FILE: salvar_sessao.php
 * PURPOSE: Salvar Nova Sessão de Surf (Session Log)
 * ====================================================================
 * 
 * Esta página recebe os dados de uma sessão de surf preenchida no
 * formulário do dashboard e insere no banco de dados.
 * 
 * Uma "sessão de surf" inclui:
 * - Data da sessão
 * - Duração (em minutos)
 * - Localização (estado, cidade, praia)
 * - Prancha utilizada
 * - Condições do mar (altura e período da onda)
 * - Nota pessoal (como foi)
 * - Observações gerais
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
    
    // ============= CAMPOS BÁSICOS =============
    // Data da sessão de surf (formato YYYY-MM-DD)
    $data_sessao     = $_POST['data_sessao'];
    
    // Duração em minutos convertida para inteiro (remove decimais se houver)
    $duracao_minutos = intval($_POST['duracao_minutos']);
    
    // Nota da sessão convertida para float (permite decimais como 4.5)
    $nota            = floatval($_POST['nota']);
    
    // ============= LOCALIZAÇÃO =============
    // Estado (ex: "Santa Catarina")
    $estado          = $_POST['estado'];
    
    // Cidade (ex: "Imbituba")
    $cidade          = $_POST['cidade'];
    
    // Praia específica (ex: "Praia do Rosa - Norte")
    $praia           = $_POST['praia'];
    
    // ============= OBSERVAÇÕES =============
    // Notas livres do usuário sobre a sessão
    $observacoes     = $_POST['observacoes'];
    
    // ============= PRANCHA E CONDIÇÕES DO MAR =============
    // ID da prancha utilizada (pode ser null se deixado em branco)
    $prancha_id      = !empty($_POST['prancha_id']) ? intval($_POST['prancha_id']) : null;

    // NOVO: Altura da onda em metros (pode ter decimais como 1.5)
    // Se não preenchido, fica NULL
    $altura_onda     = !empty($_POST['altura_onda']) ? floatval($_POST['altura_onda']) : null;
    
    // NOVO: Período da onda em segundos (deve ser um número inteiro como 11)
    // Se não preenchido, fica NULL
    $periodo_onda    = !empty($_POST['periodo_onda']) ? intval($_POST['periodo_onda']) : null;

    // ============= VALIDAÇÃO BÁSICA =============
    // Verifica se os campos obrigatórios foram preenchidos
    if ($data_sessao && $duracao_minutos) {
        // Prepara a query INSERT com placeholders (?) para segurança
        $stmt = $pdo->prepare("
            INSERT INTO sessoes (usuario_id, prancha_id, data_sessao, duracao_minutos, nota, estado, cidade, praia, altura_onda, periodo_onda, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Executa a query substituindo os placeholders pelos valores reais
        // A ordem DEVE coincidir com os ? na query acima
        $stmt->execute([
            $usuario_id,      // ID do usuário logado
            $prancha_id,      // ID da prancha (pode ser NULL)
            $data_sessao,     // Data da sessão
            $duracao_minutos, // Duração em minutos
            $nota,            // Nota da sessão
            $estado,          // Estado
            $cidade,          // Cidade
            $praia,           // Praia
            $altura_onda,     // Altura da onda em metros (pode ser NULL)
            $periodo_onda,    // Período em segundos (pode ser NULL)
            $observacoes      // Observações livres
        ]);
    }
}

// ============= REDIRECIONAMENTO =============
// Após salvar (ou tentar salvar), redireciona para o dashboard
// O usuário verá a sessão adicionada imediatamente
header("Location: dashboard.php");

// Interrompe qualquer código posterior
exit;