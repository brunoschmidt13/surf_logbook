<?php
// salvar_prancha.php
require_once 'config/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Deixamos o texto entrar puro, pois o PDO se encarrega da segurança no banco
    $modelo = $_POST['modelo'];
    $medidas = $_POST['medidas']; 

    if ($modelo) {
        $stmt = $pdo->prepare("INSERT INTO pranchas (usuario_id, modelo, medidas) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $modelo, $medidas]);
    }
}

header("Location: dashboard.php");
exit;