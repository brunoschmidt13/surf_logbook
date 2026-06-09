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
    $condicoes_onda  = $_POST['condicoes_onda'];
    $observacoes     = $_POST['observacoes'];
    $prancha_id      = !empty($_POST['prancha_id']) ? intval($_POST['prancha_id']) : null;

    if ($data_sessao && $duracao_minutos) {
        // CORRIGIDO: Agora usa estritamente 'cidade' para casar com seu banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO sessoes (usuario_id, prancha_id, data_sessao, duracao_minutos, nota, estado, cidade, praia, condicoes_onda, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $prancha_id, $data_sessao, $duracao_minutos, $nota, $estado, $cidade, $praia, $condicoes_onda, $observacoes]);
    }
}

header("Location: dashboard.php");
exit;