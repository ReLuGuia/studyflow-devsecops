<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar sessão
$stmt = $pdo->prepare("
    SELECT s.*, a.id as assunto_id, a.nome as assunto_nome,
           d.id as disciplina_id
    FROM sessoes_estudo s
    JOIN assuntos a ON s.assunto_id = a.id
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE s.id = ? AND s.usuario_id = ? AND s.concluida = 0
");
$stmt->execute([$id, $usuario_id]);
$sessao = $stmt->fetch();

if (!$sessao) {
    $_SESSION['error'] = "Sessão não encontrada ou já concluída.";
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tempo_minutos = (int)$_POST['tempo_minutos'];
    $foco_percentual = (int)$_POST['foco_percentual'];
    $anotacao = trim($_POST['anotacao']);
    
    $errors = [];
    
    if ($tempo_minutos <= 0) {
        $errors[] = "O tempo estudado deve ser maior que zero.";
    }
    
    if ($foco_percentual < 0 || $foco_percentual > 100) {
        $errors[] = "O percentual de foco deve estar entre 0 e 100.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Atualizar sessão
            $stmt = $pdo->prepare("
                UPDATE sessoes_estudo 
                SET concluida = 1,
                    data_fim = NOW(),
                    tempo_minutos = ?,
                    foco_percentual = ?,
                    anotacao = CONCAT(COALESCE(anotacao, ''), '\n\n', ?)
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$tempo_minutos, $foco_percentual, $anotacao, $id, $usuario_id]);
            
            // Atualizar conquistas
            $stmt = $pdo->prepare("CALL sp_atualizar_conquistas(?, 'horas', ?)");
            $stmt->execute([$usuario_id, $tempo_minutos]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Sessão concluída com sucesso! Tempo registrado: " . 
                                   floor($tempo_minutos / 60) . "h " . ($tempo_minutos % 60) . "m";
            
            header("Location: index.php?assunto_id=" . $sessao['assunto_id']);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Erro ao concluir sessão: " . $e->getMessage();
        }
    }
}

// Calcular tempo sugerido (baseado no planejado ou diferença de horário)
$tempo_sugerido = $sessao['tempo_planejado'];
if ($sessao['data_inicio']) {
    $inicio = strtotime($sessao['data_inicio']);
    $agora = time();
    $diferenca = floor(($agora - $inicio) / 60);
    if ($diferenca > 0 && $diferenca < 480) {
        $tempo_sugerido = $diferenca;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Concluir Sessão - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .sessao-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .sessao-info h3 {
            color: #667eea;
            margin-bottom: 10px;
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
        
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
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
            text-align: center;
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
        
        .sugestoes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .sugestao-tempo {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sugestao-tempo:hover {
            background: #27ae60;
            color: white;
        }
        
        .foco-slider {
            width: 100%;
            margin: 10px 0;
        }
        
        .timer {
            font-size: 48px;
            text-align: center;
            font-family: monospace;
            color: #667eea;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-check-circle"></i> Concluir Sessão de Estudo</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="sessao-info">
            <h3><?php echo htmlspecialchars($sessao['assunto_nome']); ?></h3>
            <p>
                <?php if ($sessao['data_inicio']): ?>
                    Iniciada em: <?php echo date('d/m/Y H:i', strtotime($sessao['data_inicio'])); ?>
                <?php else: ?>
                    Sessão sem horário definido
                <?php endif; ?>
            </p>
            <p>Tipo: <?php echo ucfirst($sessao['tipo_sessao']); ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="tempo_minutos">Tempo estudado (minutos) *</label>
                <input type="number" id="tempo_minutos" name="tempo_minutos" 
                       value="<?php echo $tempo_sugerido; ?>" 
                       min="1" max="480" step="1" required>
                
                <div class="sugestoes">
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=15">15 min</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=30">30 min</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=45">45 min</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=60">1 hora</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=90">1h30</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=120">2 horas</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=150">2h30</div>
                    <div class="sugestao-tempo" onclick="document.getElementById('tempo_minutos').value=180">3 horas</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="foco_percentual">Nível de Foco (0-100%)</label>
                <input type="range" id="foco_percentual" name="foco_percentual" 
                       class="foco-slider" min="0" max="100" step="5" value="80"
                       oninput="document.getElementById('foco_valor').textContent = this.value + '%'">
                <div style="text-align: center; margin-top: 5px;">
                    <span id="foco_valor">80%</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="anotacao">O que você estudou? (opcional)</label>
                <textarea id="anotacao" name="anotacao" 
                          placeholder="Resuma o que foi estudado, principais pontos, dificuldades encontradas..."></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Confirmar Conclusão
                </button>
            </div>
        </form>
        
        <div style="text-align: center;">
            <a href="index.php?assunto_id=<?php echo $sessao['assunto_id']; ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <script>
        // Timer opcional
        let minutos = <?php echo $tempo_sugerido; ?>;
        const inputTempo = document.getElementById('tempo_minutos');
        
        // Sugestões de tempo
        const sugestoes = document.querySelectorAll('.sugestao-tempo');
        sugestoes.forEach(s => {
            s.addEventListener('click', function() {
                inputTempo.value = this.textContent.replace('min', '').replace('hora', '60').replace('h', '').trim();
            });
        });
    </script>
</body>
</html>