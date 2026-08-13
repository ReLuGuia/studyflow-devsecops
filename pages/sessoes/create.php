<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar sessão
$stmt = $pdo->prepare("
    SELECT s.*, a.nome as assunto_nome, a.disciplina_id,
           d.nome as disciplina_nome, d.cor as disciplina_cor
    FROM sessoes_estudo s
    JOIN assuntos a ON s.assunto_id = a.id
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE s.id = ? AND s.usuario_id = ?
");
$stmt->execute([$id, $usuario_id]);
$sessao = $stmt->fetch();

if (!$sessao) {
    $_SESSION['error'] = "Sessão não encontrada.";
    header('Location: index.php');
    exit;
}

// Buscar assuntos para o select
$stmtAssuntos = $pdo->prepare("
    SELECT a.id, a.nome as assunto_nome, d.nome as disciplina_nome
    FROM assuntos a
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE d.usuario_id = ? AND a.ativo = 1
    ORDER BY d.nome, a.nome
");
$stmtAssuntos->execute([$usuario_id]);
$assuntos = $stmtAssuntos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assunto_id = (int)$_POST['assunto_id'];
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $hora_inicio = !empty($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
    $tempo_planejado = (int)$_POST['tempo_planejado'];
    $tipo_sessao = $_POST['tipo_sessao'];
    $anotacao = trim($_POST['anotacao']);
    $foco_percentual = (int)$_POST['foco_percentual'];
    
    // Combinar data e hora
    if ($data_inicio && $hora_inicio) {
        $data_inicio = $data_inicio . ' ' . $hora_inicio . ':00';
    }
    
    $errors = [];
    
    if ($assunto_id <= 0) {
        $errors[] = "Selecione um assunto.";
    }
    
    if ($tempo_planejado <= 0) {
        $errors[] = "O tempo planejado deve ser maior que zero.";
    }
    
    if ($foco_percentual < 0 || $foco_percentual > 100) {
        $errors[] = "O percentual de foco deve estar entre 0 e 100.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE sessoes_estudo SET
                    assunto_id = ?,
                    data_inicio = ?,
                    tempo_planejado = ?,
                    tipo_sessao = ?,
                    anotacao = ?,
                    foco_percentual = ?
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([
                $assunto_id, $data_inicio, $tempo_planejado,
                $tipo_sessao, $anotacao, $foco_percentual,
                $id, $usuario_id
            ]);
            
            $_SESSION['success'] = "Sessão atualizada com sucesso!";
            
            if ($sessao['disciplina_id']) {
                header("Location: index.php?assunto_id=" . $sessao['assunto_id']);
            } else {
                header("Location: index.php");
            }
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Erro ao atualizar sessão: " . $e->getMessage();
        }
    }
}

// Separar data e hora para o formulário
$data_inicio = $sessao['data_inicio'] ? date('Y-m-d', strtotime($sessao['data_inicio'])) : '';
$hora_inicio = $sessao['data_inicio'] ? date('H:i', strtotime($sessao['data_inicio'])) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Sessão - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: <?php echo $sessao['disciplina_cor']; ?>20;
            border-left: 4px solid <?php echo $sessao['disciplina_cor']; ?>;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .status-info {
            background: <?php echo $sessao['concluida'] ? '#d4edda' : '#fff3cd'; ?>;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid <?php echo $sessao['concluida'] ? '#28a745' : '#ffc107'; ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .foco-slider {
            width: 100%;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            <i class="fas fa-edit" style="color: <?php echo $sessao['disciplina_cor']; ?>;"></i>
            Editar Sessão de Estudo
        </h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="status-info">
            <div>
                <i class="fas fa-<?php echo $sessao['concluida'] ? 'check-circle' : 'clock'; ?>"></i>
                Status: <strong><?php echo $sessao['concluida'] ? 'Concluída' : 'Pendente'; ?></strong>
                <?php if ($sessao['concluida'] && $sessao['tempo_minutos'] > 0): ?>
                    - Tempo realizado: <?php echo floor($sessao['tempo_minutos'] / 60); ?>h <?php echo $sessao['tempo_minutos'] % 60; ?>m
                <?php endif; ?>
            </div>
            <?php if (!$sessao['concluida']): ?>
                <a href="concluir.php?id=<?php echo $sessao['id']; ?>" class="btn-primary" style="width: auto; padding: 8px 15px;">
                    <i class="fas fa-check"></i> Concluir Sessão
                </a>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="assunto_id">Assunto *</label>
                <select id="assunto_id" name="assunto_id" required>
                    <option value="">Selecione um assunto...</option>
                    <?php foreach ($assuntos as $a): ?>
                        <option value="<?php echo $a['id']; ?>" 
                                <?php echo ($a['id'] == $sessao['assunto_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($a['disciplina_nome'] . ' - ' . $a['assunto_nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="row">
                <div class="form-group">
                    <label for="data_inicio">Data de Início</label>
                    <input type="date" id="data_inicio" name="data_inicio" 
                           value="<?php echo $data_inicio ?: date('Y-m-d'); ?>"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de Início</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" 
                           value="<?php echo $hora_inicio ?: date('H:i'); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="form-group">
                    <label for="tempo_planejado">Tempo Planejado (minutos) *</label>
                    <input type="number" id="tempo_planejado" name="tempo_planejado" 
                           value="<?php echo $sessao['tempo_planejado'] ?: 60; ?>" 
                           min="5" max="480" step="5" required>
                </div>
                
                <div class="form-group">
                    <label for="tipo_sessao">Tipo de Sessão</label>
                    <select id="tipo_sessao" name="tipo_sessao">
                        <option value="estudo" <?php echo $sessao['tipo_sessao'] == 'estudo' ? 'selected' : ''; ?>>Estudo</option>
                        <option value="revisao" <?php echo $sessao['tipo_sessao'] == 'revisao' ? 'selected' : ''; ?>>Revisão</option>
                        <option value="questoes" <?php echo $sessao['tipo_sessao'] == 'questoes' ? 'selected' : ''; ?>>Questões</option>
                        <option value="simulado" <?php echo $sessao['tipo_sessao'] == 'simulado' ? 'selected' : ''; ?>>Simulado</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="foco_percentual">Percentual de Foco (0-100%)</label>
                <input type="range" id="foco_percentual" name="foco_percentual" 
                       class="foco-slider" min="0" max="100" step="5"
                       value="<?php echo $sessao['foco_percentual'] ?: 80; ?>"
                       oninput="document.getElementById('foco_valor').textContent = this.value + '%'">
                <div style="text-align: center; margin-top: 5px;">
                    <span id="foco_valor"><?php echo $sessao['foco_percentual'] ?: 80; ?>%</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="anotacao">Anotações</label>
                <textarea id="anotacao" name="anotacao" 
                          placeholder="Adicione notas sobre a sessão..."><?php echo htmlspecialchars($sessao['anotacao']); ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
        
        <div style="text-align: center;">
            <a href="index.php<?php echo $sessao['assunto_id'] ? '?assunto_id=' . $sessao['assunto_id'] : ''; ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para Lista
            </a>
        </div>
    </div>
    
    <script>
        // Atualizar valor do foco
        const focoSlider = document.getElementById('foco_percentual');
        const focoValor = document.getElementById('foco_valor');
        
        focoSlider.addEventListener('input', function() {
            focoValor.textContent = this.value + '%';
        });
    </script>
</body>
</html>