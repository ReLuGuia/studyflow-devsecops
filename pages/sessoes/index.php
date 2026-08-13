<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$assunto_id = isset($_GET['assunto_id']) ? (int)$_GET['assunto_id'] : 0;

// Buscar informações do assunto se fornecido
$assunto = null;
if ($assunto_id > 0) {
    $stmt = $pdo->prepare("
        SELECT a.*, d.nome as disciplina_nome, d.cor as disciplina_cor
        FROM assuntos a
        JOIN disciplinas d ON a.disciplina_id = d.id
        WHERE a.id = ? AND d.usuario_id = ?
    ");
    $stmt->execute([$assunto_id, $usuario_id]);
    $assunto = $stmt->fetch();
}

// Filtros
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Query base
$sql = "
    SELECT s.*, 
           a.nome as assunto_nome,
           d.nome as disciplina_nome,
           d.cor as disciplina_cor,
           (SELECT COUNT(*) FROM anexos_sessao WHERE sessao_id = s.id) as total_anexos
    FROM sessoes_estudo s
    JOIN assuntos a ON s.assunto_id = a.id
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE s.usuario_id = ?
";

$params = [$usuario_id];

if ($assunto_id > 0) {
    $sql .= " AND s.assunto_id = ?";
    $params[] = $assunto_id;
}

if (!empty($filtro_data)) {
    $sql .= " AND DATE(s.data_inicio) = ?";
    $params[] = $filtro_data;
}

if (!empty($filtro_tipo)) {
    $sql .= " AND s.tipo_sessao = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_status === 'concluidas') {
    $sql .= " AND s.concluida = 1";
} elseif ($filtro_status === 'pendentes') {
    $sql .= " AND s.concluida = 0 AND s.data_inicio IS NOT NULL";
} elseif ($filtro_status === 'agendadas') {
    $sql .= " AND s.data_inicio > NOW()";
}

$sql .= " ORDER BY s.data_inicio DESC, s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessoes = $stmt->fetchAll();

// Estatísticas
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sessoes,
        SUM(CASE WHEN concluida = 1 THEN 1 ELSE 0 END) as concluidas,
        SUM(tempo_minutos) as total_minutos,
        AVG(foco_percentual) as media_foco
    FROM sessoes_estudo 
    WHERE usuario_id = ?
");
$stats->execute([$usuario_id]);
$estatisticas = $stats->fetch();

// Sessões de hoje
$hoje = date('Y-m-d');
$stmtHoje = $pdo->prepare("
    SELECT s.*, a.nome as assunto_nome, d.nome as disciplina_nome, d.cor
    FROM sessoes_estudo s
    JOIN assuntos a ON s.assunto_id = a.id
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE s.usuario_id = ? AND DATE(s.data_inicio) = ? AND s.concluida = 0
    ORDER BY s.data_inicio
");
$stmtHoje->execute([$usuario_id, $hoje]);
$sessoes_hoje = $stmtHoje->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessões de Estudo - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .numero {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card .rotulo {
            color: #666;
            margin-top: 5px;
        }
        
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filtro-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filtro-group select,
        .filtro-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn-criar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        
        .btn-criar:hover {
            transform: translateY(-2px);
        }
        
        .btn-voltar {
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .sessao-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 5px solid;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .sessao-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .sessao-card.concluida {
            opacity: 0.8;
            background: #f8f9fa;
        }
        
        .sessao-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .sessao-info h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        
        .sessao-info h3 a {
            color: #333;
            text-decoration: none;
        }
        
        .sessao-info h3 a:hover {
            color: var(--primary-color);
        }
        
        .disciplina-tag {
            display: inline-block;
            padding: 3px 10px;
            background: #f0f0f0;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .tipo-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tipo-estudo { background: #3498db; color: white; }
        .tipo-revisao { background: #9b59b6; color: white; }
        .tipo-questoes { background: #e67e22; color: white; }
        .tipo-simulado { background: #e74c3c; color: white; }
        
        .sessao-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }
        
        .sessao-meta i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .sessao-actions {
            display: flex;
            gap: 10px;
        }
        
        .sessao-actions a {
            color: #666;
            text-decoration: none;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .sessao-actions a:hover {
            color: var(--primary-color);
        }
        
        .sessao-actions .btn-iniciar {
            background: #27ae60;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
        }
        
        .sessao-actions .btn-iniciar:hover {
            background: #219a52;
            color: white;
        }
        
        .sessao-actions .btn-concluir {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
        }
        
        .anotacao-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
            border-left: 3px solid var(--primary-color);
        }
        
        .foco-indicator {
            display: inline-block;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(var(--primary-color) calc(1% * var(--foco)), #ecf0f1 0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .hoje-section {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .hoje-section h3 {
            color: #e65100;
            margin-bottom: 15px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .cronometro {
            display: inline-block;
            background: #2c3e50;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <div style="padding: 0 30px;">
                <?php if ($assunto): ?>
                    <a href="../assuntos/?disciplina_id=<?php echo $assunto['disciplina_id']; ?>" class="btn-voltar" style="background: rgba(255,255,255,0.2); margin-bottom: 20px; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Voltar para <?php echo htmlspecialchars($assunto['disciplina_nome']); ?>
                    </a>
                    <h1 style="margin: 20px 0 10px;">
                        <i class="fas fa-clock"></i>
                        Sessões de: <?php echo htmlspecialchars($assunto['nome']); ?>
                    </h1>
                <?php else: ?>
                    <a href="../dashboard.php" class="btn-voltar" style="background: rgba(255,255,255,0.2); margin-bottom: 20px; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                    </a>
                    <h1 style="margin: 20px 0 10px;">
                        <i class="fas fa-clock"></i>
                        Minhas Sessões de Estudo
                    </h1>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="numero"><?php echo $estatisticas['total_sessoes'] ?? 0; ?></div>
                <div class="rotulo">Total de Sessões</div>
            </div>
            <div class="stat-card">
                <div class="numero"><?php echo $estatisticas['concluidas'] ?? 0; ?></div>
                <div class="rotulo">Concluídas</div>
            </div>
            <div class="stat-card">
                <div class="numero"><?php echo floor(($estatisticas['total_minutos'] ?? 0) / 60); ?>h <?php echo ($estatisticas['total_minutos'] ?? 0) % 60; ?>m</div>
                <div class="rotulo">Tempo Total</div>
            </div>
            <div class="stat-card">
                <div class="numero"><?php echo round($estatisticas['media_foco'] ?? 0); ?>%</div>
                <div class="rotulo">Foco Médio</div>
            </div>
        </div>
        
        <!-- Sessões de Hoje -->
        <?php if (!empty($sessoes_hoje)): ?>
        <div class="hoje-section">
            <h3><i class="fas fa-sun"></i> Sessões de Hoje</h3>
            <?php foreach ($sessoes_hoje as $s): ?>
                <div class="sessao-card" style="border-left-color: <?php echo $s['cor']; ?>;">
                    <div class="sessao-header">
                        <div class="sessao-info">
                            <h3>
                                <a href="../assuntos/?disciplina_id=<?php echo $s['disciplina_id']; ?>">
                                    <?php echo htmlspecialchars($s['assunto_nome']); ?>
                                </a>
                            </h3>
                            <div>
                                <span class="disciplina-tag">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($s['disciplina_nome']); ?>
                                </span>
                                <span class="tipo-badge tipo-<?php echo $s['tipo_sessao']; ?>">
                                    <?php echo ucfirst($s['tipo_sessao']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="sessao-actions">
                            <a href="edit.php?id=<?php echo $s['id']; ?>" class="btn-iniciar">
                                <i class="fas fa-play"></i> Iniciar
                            </a>
                        </div>
                    </div>
                    <div class="sessao-meta">
                        <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($s['data_inicio'])); ?></span>
                        <span><i class="fas fa-hourglass-half"></i> <?php echo $s['tempo_planejado']; ?> min planejados</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Ações e Filtros -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="create.php<?php echo $assunto ? '?assunto_id=' . $assunto['id'] : ''; ?>" class="btn-criar">
                    <i class="fas fa-plus"></i> Nova Sessão
                </a>
                <?php if (!$assunto): ?>
                    <a href="create.php" class="btn-criar" style="background: #3498db; margin-left: 10px;">
                        <i class="fas fa-calendar-plus"></i> Agendar
                    </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="filtros">
                <?php if ($assunto): ?>
                    <input type="hidden" name="assunto_id" value="<?php echo $assunto['id']; ?>">
                <?php endif; ?>
                
                <div class="filtro-group">
                    <input type="date" name="data" value="<?php echo $filtro_data; ?>" placeholder="Data">
                </div>
                
                <div class="filtro-group">
                    <select name="tipo">
                        <option value="">Todos os tipos</option>
                        <option value="estudo" <?php echo $filtro_tipo == 'estudo' ? 'selected' : ''; ?>>Estudo</option>
                        <option value="revisao" <?php echo $filtro_tipo == 'revisao' ? 'selected' : ''; ?>>Revisão</option>
                        <option value="questoes" <?php echo $filtro_tipo == 'questoes' ? 'selected' : ''; ?>>Questões</option>
                        <option value="simulado" <?php echo $filtro_tipo == 'simulado' ? 'selected' : ''; ?>>Simulado</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <select name="status">
                        <option value="">Todos status</option>
                        <option value="concluidas" <?php echo $filtro_status == 'concluidas' ? 'selected' : ''; ?>>Concluídas</option>
                        <option value="pendentes" <?php echo $filtro_status == 'pendentes' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="agendadas" <?php echo $filtro_status == 'agendadas' ? 'selected' : ''; ?>>Agendadas</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-criar" style="padding: 10px 20px;">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
        </div>
        
        <!-- Lista de Sessões -->
        <div id="lista-sessoes">
            <?php if (empty($sessoes)): ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h3>Nenhuma sessão de estudo encontrada</h3>
                    <p>Comece agendando sua primeira sessão de estudos.</p>
                    <a href="create.php<?php echo $assunto ? '?assunto_id=' . $assunto['id'] : ''; ?>" class="btn-criar">
                        <i class="fas fa-plus"></i> Agendar Sessão
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($sessoes as $s): ?>
                    <div class="sessao-card <?php echo $s['concluida'] ? 'concluida' : ''; ?>" 
                         style="border-left-color: <?php echo $s['disciplina_cor']; ?>;">
                        
                        <div class="sessao-header">
                            <div class="sessao-info">
                                <h3>
                                    <a href="../assuntos/?disciplina_id=<?php echo $s['disciplina_id']; ?>">
                                        <?php echo htmlspecialchars($s['assunto_nome']); ?>
                                    </a>
                                </h3>
                                <div>
                                    <span class="disciplina-tag">
                                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($s['disciplina_nome']); ?>
                                    </span>
                                    <span class="tipo-badge tipo-<?php echo $s['tipo_sessao']; ?>">
                                        <?php echo ucfirst($s['tipo_sessao']); ?>
                                    </span>
                                    <?php if ($s['concluida']): ?>
                                        <span class="tipo-badge" style="background: #27ae60;">
                                            <i class="fas fa-check"></i> Concluída
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="sessao-actions">
                                <?php if (!$s['concluida'] && $s['data_inicio']): ?>
                                    <a href="iniciar.php?id=<?php echo $s['id']; ?>" class="btn-iniciar">
                                        <i class="fas fa-play"></i> Iniciar
                                    </a>
                                    <a href="concluir.php?id=<?php echo $s['id']; ?>" class="btn-concluir">
                                        <i class="fas fa-check"></i> Concluir
                                    </a>
                                <?php endif; ?>
                                
                                <a href="edit.php?id=<?php echo $s['id']; ?>" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <a href="delete.php?id=<?php echo $s['id']; ?>" 
                                   title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir esta sessão?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="sessao-meta">
                            <?php if ($s['data_inicio']): ?>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($s['data_inicio'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($s['data_inicio'])); ?></span>
                            <?php else: ?>
                                <span><i class="fas fa-calendar"></i> Data não agendada</span>
                            <?php endif; ?>
                            
                            <?php if ($s['data_fim']): ?>
                                <span><i class="fas fa-hourglass-end"></i> até <?php echo date('H:i', strtotime($s['data_fim'])); ?></span>
                            <?php endif; ?>
                            
                            <span><i class="fas fa-hourglass-half"></i> 
                                <?php echo $s['tempo_planejado']; ?> min planejados
                                <?php if ($s['tempo_minutos'] > 0): ?>
                                    (<?php echo $s['tempo_minutos']; ?> min realizados)
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($s['total_anexos'] > 0): ?>
                                <span><i class="fas fa-paperclip"></i> <?php echo $s['total_anexos']; ?> anexos</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($s['foco_percentual'] > 0): ?>
                            <div style="margin: 10px 0;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 14px; color: #666;">Foco:</span>
                                    <div style="flex: 1; height: 8px; background: #ecf0f1; border-radius: 4px;">
                                        <div style="width: <?php echo $s['foco_percentual']; ?>%; height: 100%; background: linear-gradient(90deg, <?php echo $s['disciplina_cor']; ?>, #9b59b6); border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-weight: bold; color: <?php echo $s['disciplina_cor']; ?>;"><?php echo $s['foco_percentual']; ?>%</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($s['anotacao'])): ?>
                            <div class="anotacao-preview">
                                <i class="fas fa-quote-left" style="color: <?php echo $s['disciplina_cor']; ?>;"></i>
                                <?php echo nl2br(htmlspecialchars(substr($s['anotacao'], 0, 200))); ?>
                                <?php if (strlen($s['anotacao']) > 200): ?>...<?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 10px; font-size: 12px; color: #999; display: flex; gap: 15px;">
                            <span><i class="fas fa-clock"></i> Criada: <?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?></span>
                            <?php if ($s['updated_at'] != $s['created_at']): ?>
                                <span><i class="fas fa-sync"></i> Atualizada: <?php echo date('d/m/Y H:i', strtotime($s['updated_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Atualizar página a cada minuto para mostrar cronômetro
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>