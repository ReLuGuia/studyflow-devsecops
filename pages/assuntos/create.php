<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar assunto para redirecionamento
$stmt = $pdo->prepare("
    SELECT a.*, d.id as disciplina_id
    FROM assuntos a
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE a.id = ? AND d.usuario_id = ?
");
$stmt->execute([$id, $usuario_id]);
$assunto = $stmt->fetch();

if (!$assunto) {
    $_SESSION['error'] = "Assunto não encontrado.";
    header('Location: ../disciplinas/');
    exit;
}

// Verificar se tem subassuntos
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assuntos WHERE assunto_pai_id = ?");
$stmt->execute([$id]);
$subassuntos = $stmt->fetch();

if ($subassuntos['total'] > 0) {
    $_SESSION['error'] = "Não é possível excluir este assunto pois ele possui subassuntos.";
    header("Location: index.php?disciplina_id=" . $assunto['disciplina_id']);
    exit;
}

// Soft delete (marcar como inativo)
$stmt = $pdo->prepare("UPDATE assuntos SET ativo = 0 WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = "Assunto excluído com sucesso.";
header("Location: index.php?disciplina_id=" . $assunto['disciplina_id']);
exit;
?>