<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $objetivo = $_POST['objetivo'] ?? '';
    $nivel_experiencia = $_POST['nivel_experiencia'] ?? 'iniciante';
    $horas_dia_semana = $_POST['horas_dia_semana'] ?? 4;
    $horas_dia_fim_semana = $_POST['horas_dia_fim_semana'] ?? 2;

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, objetivo, nivel_experiencia, horas_dia_semana, horas_dia_fim_semana) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senha, $objetivo, $nivel_experiencia, $horas_dia_semana, $horas_dia_fim_semana]);

        $_SESSION['success'] = "Cadastro realizado com sucesso! Faça login.";
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $erro = "E-mail já cadastrado.";
        } else {
            $erro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro - StudyFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Registrar no StudyFlow</h2>
        <?php if (isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
        <form method="POST">
            <div>
                <label>Nome completo:</label>
                <input type="text" name="nome" required>
            </div>
            <div>
                <label>E-mail:</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Senha:</label>
                <input type="password" name="senha" required>
            </div>
            <div>
                <label>Objetivo (opcional):</label>
                <input type="text" name="objetivo">
            </div>
            <div>
                <label>Nível de experiência:</label>
                <select name="nivel_experiencia">
                    <option value="iniciante">Iniciante</option>
                    <option value="intermediario">Intermediário</option>
                    <option value="avancado">Avançado</option>
                </select>
            </div>
            <div>
                <label>Horas por dia (dias de semana):</label>
                <input type="number" name="horas_dia_semana" value="4" min="0" max="24">
            </div>
            <div>
                <label>Horas por dia (fim de semana):</label>
                <input type="number" name="horas_dia_fim_semana" value="2" min="0" max="24">
            </div>
            <button type="submit">Registrar</button>
        </form>
        <p>Já tem conta? <a href="login.php">Faça login</a></p>
    </div>
</body>
</html>