<?php
require_once '../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];

// Estatísticas rápidas
$stats = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM disciplinas WHERE usuario_id = ? AND ativo = 1) as total_disciplinas,
        (SELECT COUNT(*) FROM assuntos WHERE disciplina_id IN (SELECT id FROM disciplinas WHERE usuario_id = ?) AND concluido = 1) as assuntos_concluidos,
        (SELECT SUM(tempo_minutos) FROM sessoes_estudo WHERE usuario_id = ? AND concluida = 1) as total_minutos,
        (SELECT COUNT(*) FROM revisoes WHERE usuario_id = ? AND data_revisao <= CURDATE() AND concluida = 0) as revisoes_pendentes
");
$stats->execute([$usuario_id, $usuario_id, $usuario_id, $usuario_id]);
$dados = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - StudyFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Olá, <?php echo $_SESSION['usuario_nome']; ?>!</h1>
        <nav>
            <a href="disciplinas/">Disciplinas</a>
            <a href="assuntos/">Assuntos</a>
            <a href="sessoes/">Sessões de Estudo</a>
            <a href="../logout.php">Sair</a>
        </nav>

        <div class="stats">
            <div class="card">
                <h3>Disciplinas</h3>
                <p><?php echo $dados['total_disciplinas']; ?></p>
            </div>
            <div class="card">
                <h3>Assuntos Concluídos</h3>
                <p><?php echo $dados['assuntos_concluidos']; ?></p>
            </div>
            <div class="card">
                <h3>Total Estudado</h3>
                <p><?php echo floor($dados['total_minutos'] / 60); ?>h <?php echo $dados['total_minutos'] % 60; ?>m</p>
            </div>
            <div class="card">
                <h3>Revisões Pendentes</h3>
                <p><?php echo $dados['revisoes_pendentes']; ?></p>
            </div>
        </div>
    </div>
</body>
</html>