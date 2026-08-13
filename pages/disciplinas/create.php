<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $cor = $_POST['cor'] ?? '#3498db';
    $icone = $_POST['icone'] ?? 'book';
    $horas_estimadas = (int)$_POST['horas_estimadas'];
    $ordem = (int)$_POST['ordem'];

    try {
        $stmt = $pdo->prepare("INSERT INTO disciplinas (usuario_id, nome, descricao, cor, icone, horas_estimadas, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $nome, $descricao, $cor, $icone, $horas_estimadas, $ordem]);

        $_SESSION['success'] = "Disciplina criada com sucesso!";
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $erro = "Já existe uma disciplina com este nome.";
        } else {
            $erro = "Erro ao criar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Disciplina - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Nova Disciplina</h2>
        <?php if (isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
        <form method="POST">
            <div>
                <label>Nome:</label>
                <input type="text" name="nome" required>
            </div>
            <div>
                <label>Descrição:</label>
                <textarea name="descricao"></textarea>
            </div>
            <div>
                <label>Cor (hex):</label>
                <input type="color" name="cor" value="#3498db">
            </div>
            <div>
                <label>Ícone (ex: book, calculator, science):</label>
                <input type="text" name="icone" value="book">
            </div>
            <div>
                <label>Horas estimadas:</label>
                <input type="number" name="horas_estimadas" value="0" min="0">
            </div>
            <div>
                <label>Ordem:</label>
                <input type="number" name="ordem" value="0">
            </div>
            <button type="submit">Salvar</button>
            <a href="index.php">Cancelar</a>
        </form>
    </div>
</body>
</html>