<?php
// Ativar exibição de erros (apenas para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = 'localhost';
$dbname = 'studyflow';
$user = 'root';
$password = ''; // No XAMPP, a senha padrão é vazia

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Teste de conexão (remova depois)
    // echo "Conectado com sucesso!"; 
    
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage() . " - Código: " . $e->getCode());
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

// Redireciona se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../pages/login.php');
        exit;
    }
}
?>