<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = $_GET['id'] ?? 0;

// Buscar disciplina
$stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$disciplina = $stmt->fetch();

if (!$disciplina) {
    $_SESSION['error'] = "Disciplina não encontrada.";
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $cor = $_POST['cor'] ?? '#3498db';
    $icone = $_POST['icone'] ?? 'book';
    $horas_estimadas = (int)$_POST['horas_estimadas'];
    $ordem = (int)$_POST['ordem'];

    try {
        $stmt = $pdo->prepare("UPDATE disciplinas SET nome = ?, descricao = ?, cor = ?, icone = ?, horas_estimadas = ?, ordem = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome, $descricao, $cor, $icone, $horas_estimadas, $ordem, $id, $usuario_id]);

        $_SESSION['success'] = "Disciplina atualizada com sucesso!";
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $erro = "Já existe outra disciplina com este nome.";
        } else {
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Disciplina - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Editar Disciplina</h2>
        <?php if (isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
        <form method="POST">
            <div>
                <label>Nome:</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($disciplina['nome']); ?>" required>
            </div>
            <div>
                <label>Descrição:</label>
                <textarea name="descricao"><?php echo htmlspecialchars($disciplina['descricao']); ?></textarea>
            </div>
            <div>
                <label>Cor:</label>
                <input type="color" name="cor" value="<?php echo $disciplina['cor']; ?>">
            </div>
            <div>
                <label>Ícone:</label>
                <input type="text" name="icone" value="<?php echo htmlspecialchars($disciplina['icone']); ?>">
            </div>
            <div>
                <label>Horas estimadas:</label>
                <input type="number" name="horas_estimadas" value="<?php echo $disciplina['horas_estimadas']; ?>" min="0">
            </div>
            <div>
                <label>Ordem:</label>
                <input type="number" name="ordem" value="<?php echo $disciplina['ordem']; ?>">
            </div>
            <button type="submit">Atualizar</button>
            <a href="index.php">Cancelar</a>
        </form>
    </div>
</body>
</html>