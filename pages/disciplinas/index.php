<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];

// Buscar disciplinas do usuário
$stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE usuario_id = ? AND ativo = 1 ORDER BY ordem, nome");
$stmt->execute([$usuario_id]);
$disciplinas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Disciplinas - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Minhas Disciplinas</h2>
        <a href="create.php" class="btn">Nova Disciplina</a>
        <a href="../dashboard.php" class="btn">Voltar</a>

        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Cor</th>
                    <th>Horas Estimadas</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disciplinas as $d): ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['nome']); ?></td>
                    <td><?php echo htmlspecialchars($d['descricao']); ?></td>
                    <td style="background-color: <?php echo $d['cor']; ?>; width: 50px;">&nbsp;</td>
                    <td><?php echo $d['horas_estimadas']; ?>h</td>
                    <td>
                        <a href="edit.php?id=<?php echo $d['id']; ?>">Editar</a>
                        <a href="delete.php?id=<?php echo $d['id']; ?>" onclick="return confirm('Tem certeza?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($disciplinas)): ?>
                <tr><td colspan="5">Nenhuma disciplina cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>