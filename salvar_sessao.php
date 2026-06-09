<?php
// salvar_sessao.php
require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Pegando os dados diretamente
    $data_sessao     = $_POST['data_sessao'];
    $duracao_minutos = intval($_POST['duracao_minutos']);
    $nota            = floatval($_POST['nota']);
    $estado          = $_POST['estado'];
    $cidade          = $_POST['cidade'];
    $praia           = $_POST['praia'];
    $observacoes     = $_POST['observacoes'];
    $prancha_id      = !empty($_POST['prancha_id']) ? intval($_POST['prancha_id']) : null;

    // NOVO: Capturando os novos campos numéricos de Altura e Período
    // Usamos floatval para permitir números decimais (ex: 1.5) e intval para inteiros (ex: 11)
    $altura_onda     = !empty($_POST['altura_onda']) ? floatval($_POST['altura_onda']) : null;
    $periodo_onda    = !empty($_POST['periodo_onda']) ? intval($_POST['periodo_onda']) : null;

    if ($data_sessao && $duracao_minutos) {
        // CORRIGIDO: Removido 'condicoes_onda' e adicionado 'altura_onda' e 'periodo_onda'
        $stmt = $pdo->prepare("
            INSERT INTO sessoes (usuario_id, prancha_id, data_sessao, duracao_minutos, nota, estado, cidade, praia, altura_onda, periodo_onda, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // No execute, passamos as novas variáveis na mesma ordem exata da query acima
        $stmt->execute([
            $usuario_id, 
            $prancha_id, 
            $data_sessao, 
            $duracao_minutos, 
            $nota, 
            $estado, 
            $cidade, 
            $praia, 
            $altura_onda,  // Nova coluna numérica
            $periodo_onda, // Nova coluna numérica
            $observacoes
        ]);
    }
}

header("Location: dashboard.php");
exit;