<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$disciplina_id = $_GET['disciplina_id'] ?? 0;

// Verificar se a disciplina pertence ao usuário
if ($disciplina_id) {
    $stmt = $pdo->prepare("SELECT id FROM disciplinas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$disciplina_id, $usuario_id]);
    if (!$stmt->fetch()) {
        die("Disciplina não encontrada.");
    }
}

// Buscar assuntos da disciplina
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM assuntos WHERE assunto_pai_id = a.id) as subassuntos
    FROM assuntos a 
    WHERE a.disciplina_id = ? AND a.ativo = 1 
    ORDER BY a.ordem, a.nome
");
$stmt->execute([$disciplina_id]);
$assuntos = $stmt->fetchAll();

// Buscar nome da disciplina
$stmt = $pdo->prepare("SELECT nome FROM disciplinas WHERE id = ?");
$stmt->execute([$disciplina_id]);
$disciplina = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assuntos de <?php echo $disciplina['nome']; ?></title>
</head>
<body>
    <h2>Assuntos: <?php echo $disciplina['nome']; ?></h2>
    <a href="create.php?disciplina_id=<?php echo htmlentities($disciplina_id); ?>">Novo Assunto</a>
    <a href="../disciplinas/">Voltar para Disciplinas</a>
    <!-- Listagem... -->
</body>
</html>