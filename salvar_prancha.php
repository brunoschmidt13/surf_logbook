<?php
// salvar_prancha.php
require_once 'config/conexao.php';
session_start();

// Segurança: impede que quem não está logado envie dados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Captura os novos campos vindos do formulário
    $modelo  = trim($_POST['modelo']);
    $marca   = trim($_POST['marca']);
    $tamanho = trim($_POST['tamanho']);
    $volume  = trim($_POST['volume']);
    
    // Para manter compatibilidade com alguma tela antiga, podemos preencher 
    // a coluna 'medidas' juntando o tamanho e volume automaticamente
    $medidas = $tamanho . " - " . $volume . "L";

    if (!empty($modelo) && !empty($marca)) {
        try {
            // CORRIGIDO: Agora inserindo em todas as novas colunas do banco
            $stmt = $pdo->prepare("
                INSERT INTO pranchas (usuario_id, modelo, marca, tamanho, volume, medidas) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$usuario_id, $modelo, $marca, $tamanho, $volume, $medidas]);
            
            // Sucesso! Redireciona de volta para o dashboard
            header("Location: dashboard.php");
            exit;
            
        } catch (PDOException $e) {
            // Se der erro no banco, mostra o que aconteceu (bom para fase de testes)
            echo "Erro ao salvar no banco de dados: " . $e->getMessage();
            exit;
        }
    } else {
        echo "Por favor, preencha os campos obrigatórios (Modelo e Marca).";
        exit;
    }
} else {
    // Se tentarem acessar o arquivo direto sem ser via formulário, manda de volta
    header("Location: dashboard.php");
    exit;
}