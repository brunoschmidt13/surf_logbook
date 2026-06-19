<?php
// salvar_prancha.php
require_once 'config/conexao.php';
session_start();

// Security: prevents unauthenticated users from sending data
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Lógica de Idioma baseada na sessão para mensagens de erro/exceção
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'db_error'       => 'Error saving to database: ',
        'required_fields'=> 'Please fill required fields (Model and Brand).'
    ],
    'pt' => [
        'db_error'       => 'Erro ao salvar no banco de dados: ',
        'required_fields'=> 'Por favor, preencha os campos obrigatórios (Modelo e Marca).'
    ],
    'es' => [
        'db_error'       => 'Error al guardar en la base de datos: ',
        'required_fields'=> 'Por favor, complete los campos obligatorios (Modelo y Marca).'
    ]
];

$txt = $translations[$lang] ?? $translations['en'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Captures new fields from form
    $modelo  = trim($_POST['modelo']);
    $marca   = trim($_POST['marca']);
    $tamanho = trim($_POST['tamanho']);
    $volume  = trim($_POST['volume']);
    
    // To maintain compatibility with some old screen, we can fill
    // 'medidas' column by joining size and volume automatically
    $medidas = $tamanho . " - " . $volume . "L";

    if (!empty($modelo) && !empty($marca)) {
        try {
            // FIXED: Now inserting all new columns from database
            $stmt = $pdo->prepare("
                INSERT INTO pranchas (usuario_id, modelo, marca, tamanho, volume, medidas) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$usuario_id, $modelo, $marca, $tamanho, $volume, $medidas]);
            
            // Success! Redirects back to dashboard
            header("Location: dashboard.php");
            exit;
            
        } catch (PDOException $e) {
            // If database error, show what happened (good for testing phase)
            echo $txt['db_error'] . $e->getMessage();
            exit;
        }
    } else {
        echo $txt['required_fields'];
        exit;
    }
} else {
    // If try to access file directly without form, send back
    header("Location: dashboard.php");
    exit;
}