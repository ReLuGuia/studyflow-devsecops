<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = $_GET['id'] ?? 0;

// Soft delete: apenas marca como inativo
$stmt = $pdo->prepare("UPDATE disciplinas SET ativo = 0 WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);

$_SESSION['success'] = "Disciplina removida com sucesso.";
header('Location: index.php');
exit;