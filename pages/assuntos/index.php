<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$disciplina_id = isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : 0;

// Verificar se a disciplina pertence ao usuário
if ($disciplina_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$disciplina_id, $usuario_id]);
    $disciplina = $stmt->fetch();
    
    if (!$disciplina) {
        $_SESSION['error'] = "Disciplina não encontrada.";
        header('Location: ../disciplinas/');
        exit;
    }
} else {
    // Se não tiver disciplina_id, redireciona para disciplinas
    header('Location: ../disciplinas/');
    exit;
}

// Buscar assuntos da disciplina
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM assuntos WHERE assunto_pai_id = a.id AND ativo = 1) as total_subassuntos,
           (SELECT COUNT(*) FROM sessoes_estudo WHERE assunto_id = a.id AND concluida = 1) as total_sessoes,
           (SELECT SUM(tempo_minutos) FROM sessoes_estudo WHERE assunto_id = a.id AND concluida = 1) as total_minutos
    FROM assuntos a
    WHERE a.disciplina_id = ? AND a.ativo = 1
    ORDER BY 
        CASE WHEN a.concluido = 1 THEN 1 ELSE 0 END,
        a.prioridade DESC,
        a.ordem,
        a.nome
");
$stmt->execute([$disciplina_id]);
$assuntos = $stmt->fetchAll();

// Buscar estatísticas da disciplina
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_assuntos,
        SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidos,
        AVG(percentual_concluido) as media_progresso
    FROM assuntos 
    WHERE disciplina_id = ? AND ativo = 1
");
$stmt->execute([$disciplina_id]);
$estatisticas = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assuntos - <?php echo htmlspecialchars($disciplina['nome']); ?> - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --cor-disciplina: <?php echo $disciplina['cor']; ?>;
        }
        
        .header-disciplina {
            background: linear-gradient(135deg, var(--cor-disciplina) 0%, #333 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .progresso-geral {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .barra-progresso {
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progresso {
            height: 100%;
            background: linear-gradient(90deg, var(--cor-disciplina), #9b59b6);
            width: <?php echo $estatisticas['media_progresso'] ?? 0; ?>%;
            transition: width 0.3s;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card .numero {
            font-size: 24px;
            font-weight: bold;
            color: var(--cor-disciplina);
        }
        
        .assunto-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 5px solid var(--cor-disciplina);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .assunto-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .assunto-card.concluido {
            opacity: 0.8;
            background: #f8f9fa;
            border-left-color: #27ae60;
        }
        
        .assunto-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .assunto-titulo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .assunto-titulo h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .assunto-titulo h3 a {
            color: #333;
            text-decoration: none;
        }
        
        .assunto-titulo h3 a:hover {
            color: var(--cor-disciplina);
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.prioridade-alta {
            background: #e74c3c;
            color: white;
        }
        
        .badge.prioridade-media {
            background: #f39c12;
            color: white;
        }
        
        .badge.prioridade-baixa {
            background: #3498db;
            color: white;
        }
        
        .badge.dificuldade-dificil {
            background: #c0392b;
            color: white;
        }
        
        .badge.dificuldade-medio {
            background: #e67e22;
            color: white;
        }
        
        .badge.dificuldade-facil {
            background: #27ae60;
            color: white;
        }
        
        .badge.concluido {
            background: #27ae60;
            color: white;
        }
        
        .progresso-assunto {
            margin: 15px 0;
        }
        
        .progresso-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .barra-progresso-assunto {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progresso-assunto-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--cor-disciplina), #9b59b6);
            width: 0%;
            transition: width 0.3s;
        }
        
        .assunto-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #666;
        }
        
        .assunto-meta i {
            margin-right: 5px;
            color: var(--cor-disciplina);
        }
        
        .assunto-actions {
            display: flex;
            gap: 10px;
        }
        
        .assunto-actions button,
        .assunto-actions a {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .assunto-actions button:hover,
        .assunto-actions a:hover {
            color: var(--cor-disciplina);
        }
        
        .assunto-actions .btn-concluir:hover {
            color: #27ae60;
        }
        
        .assunto-actions .btn-excluir:hover {
            color: #e74c3c;
        }
        
        .subassuntos {
            margin-left: 30px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        
        .subassunto-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--cor-disciplina);
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
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-criar {
            background: linear-gradient(135deg, var(--cor-disciplina), #9b59b6);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
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
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho da Disciplina -->
        <div class="header-disciplina">
            <div style="padding: 0 30px;">
                <a href="../disciplinas/" class="btn-voltar" style="background: rgba(255,255,255,0.2); margin-bottom: 20px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Voltar para Disciplinas
                </a>
                <h1 style="margin: 20px 0 10px;">
                    <i class="fas fa-<?php echo htmlspecialchars($disciplina['icone']); ?>"></i>
                    <?php echo htmlspecialchars($disciplina['nome']); ?>
                </h1>
                <p><?php echo htmlspecialchars($disciplina['descricao']); ?></p>
            </div>
        </div>
        
        <!-- Mensagens de Feedback -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Progresso Geral -->
        <div class="progresso-geral">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3><i class="fas fa-chart-line"></i> Progresso Geral</h3>
                <span class="badge" style="background: var(--cor-disciplina); color: white;">
                    <?php echo $estatisticas['concluidos'] ?? 0; ?>/<?php echo $estatisticas['total_assuntos'] ?? 0; ?> concluídos
                </span>
            </div>
            
            <div class="barra-progresso">
                <div class="progresso" style="width: <?php echo round($estatisticas['media_progresso'] ?? 0); ?>%"></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="numero"><?php echo $estatisticas['total_assuntos'] ?? 0; ?></div>
                    <div>Total de Assuntos</div>
                </div>
                <div class="stat-card">
                    <div class="numero"><?php echo $estatisticas['concluidos'] ?? 0; ?></div>
                    <div>Concluídos</div>
                </div>
                <div class="stat-card">
                    <div class="numero"><?php echo round($estatisticas['media_progresso'] ?? 0); ?>%</div>
                    <div>Progresso Médio</div>
                </div>
            </div>
        </div>
        
        <!-- Ações e Filtros -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="create.php?disciplina_id=<?php echo $disciplina_id; ?>" class="btn-criar">
                    <i class="fas fa-plus"></i> Novo Assunto
                </a>
            </div>
            
            <div class="filtros">
                <div class="filtro-group">
                    <select id="filtro-prioridade" onchange="filtrarAssuntos()">
                        <option value="">Todas Prioridades</option>
                        <option value="alta">Alta</option>
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <select id="filtro-dificuldade" onchange="filtrarAssuntos()">
                        <option value="">Todas Dificuldades</option>
                        <option value="facil">Fácil</option>
                        <option value="medio">Médio</option>
                        <option value="dificil">Difícil</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <select id="filtro-status" onchange="filtrarAssuntos()">
                        <option value="">Todos Status</option>
                        <option value="concluido">Concluídos</option>
                        <option value="andamento">Em Andamento</option>
                        <option value="nao-iniciado">Não Iniciados</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <input type="text" id="filtro-busca" placeholder="Buscar assunto..." onkeyup="filtrarAssuntos()">
                </div>
            </div>
        </div>
        
        <!-- Lista de Assuntos -->
        <div id="lista-assuntos">
            <?php if (empty($assuntos)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Nenhum assunto cadastrado</h3>
                    <p>Comece adicionando o primeiro assunto desta disciplina.</p>
                    <a href="create.php?disciplina_id=<?php echo $disciplina_id; ?>" class="btn-criar">
                        <i class="fas fa-plus"></i> Criar Primeiro Assunto
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($assuntos as $assunto): ?>
                    <div class="assunto-card <?php echo $assunto['concluido'] ? 'concluido' : ''; ?>" 
                         data-prioridade="<?php echo $assunto['prioridade']; ?>"
                         data-dificuldade="<?php echo $assunto['dificuldade']; ?>"
                         data-concluido="<?php echo $assunto['concluido']; ?>"
                         data-nome="<?php echo strtolower($assunto['nome']); ?>">
                        
                        <div class="assunto-header">
                            <div class="assunto-titulo">
                                <i class="fas fa-<?php echo $assunto['concluido'] ? 'check-circle' : 'circle'; ?>" 
                                   style="color: <?php echo $assunto['concluido'] ? '#27ae60' : $disciplina['cor']; ?>; font-size: 20px;"></i>
                                <div>
                                    <h3>
                                        <a href="../sessoes/?assunto_id=<?php echo $assunto['id']; ?>">
                                            <?php echo htmlspecialchars($assunto['nome']); ?>
                                        </a>
                                    </h3>
                                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                                        <span class="badge prioridade-<?php echo $assunto['prioridade']; ?>">
                                            <i class="fas fa-flag"></i> <?php echo ucfirst($assunto['prioridade']); ?>
                                        </span>
                                        <span class="badge dificuldade-<?php echo $assunto['dificuldade']; ?>">
                                            <i class="fas fa-signal"></i> <?php echo ucfirst($assunto['dificuldade']); ?>
                                        </span>
                                        <?php if ($assunto['concluido']): ?>
                                            <span class="badge concluido">
                                                <i class="fas fa-check"></i> Concluído
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="assunto-actions">
                                <?php if (!$assunto['concluido']): ?>
                                    <a href="concluir.php?id=<?php echo $assunto['id']; ?>" 
                                       class="btn-concluir" 
                                       title="Marcar como concluído"
                                       onclick="return confirm('Marcar este assunto como concluído?')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="edit.php?id=<?php echo $assunto['id']; ?>" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($assunto['total_subassuntos'] == 0): ?>
                                    <a href="create.php?disciplina_id=<?php echo $disciplina_id; ?>&assunto_pai_id=<?php echo $assunto['id']; ?>" 
                                       title="Adicionar subassunto">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="delete.php?id=<?php echo $assunto['id']; ?>" 
                                   class="btn-excluir" 
                                   title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir este assunto?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($assunto['descricao'])): ?>
                            <p style="color: #666; margin: 10px 0; font-size: 14px;">
                                <?php echo nl2br(htmlspecialchars($assunto['descricao'])); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="progresso-assunto">
                            <div class="progresso-info">
                                <span>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo floor($assunto['total_minutos'] / 60); ?>h <?php echo $assunto['total_minutos'] % 60; ?>m
                                </span>
                                <span><?php echo $assunto['percentual_concluido']; ?>% concluído</span>
                            </div>
                            <div class="barra-progresso-assunto">
                                <div class="progresso-assunto-bar" style="width: <?php echo $assunto['percentual_concluido']; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="assunto-meta">
                            <span><i class="fas fa-clock"></i> Criado em: <?php echo date('d/m/Y', strtotime($assunto['created_at'])); ?></span>
                            <?php if ($assunto['total_sessoes'] > 0): ?>
                                <span><i class="fas fa-play-circle"></i> <?php echo $assunto['total_sessoes']; ?> sessões</span>
                            <?php endif; ?>
                            <?php if ($assunto['total_subassuntos'] > 0): ?>
                                <span><i class="fas fa-sitemap"></i> <?php echo $assunto['total_subassuntos']; ?> subassuntos</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Subassuntos (se houver) -->
                        <?php if ($assunto['total_subassuntos'] > 0): ?>
                            <div class="subassuntos">
                                <?php
                                $stmtSub = $pdo->prepare("SELECT * FROM assuntos WHERE assunto_pai_id = ? AND ativo = 1 ORDER BY nome");
                                $stmtSub->execute([$assunto['id']]);
                                $subassuntos = $stmtSub->fetchAll();
                                ?>
                                <?php foreach ($subassuntos as $sub): ?>
                                    <div class="subassunto-card">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($sub['nome']); ?></strong>
                                                <span class="badge prioridade-<?php echo $sub['prioridade']; ?>" style="margin-left: 10px;">
                                                    <?php echo ucfirst($sub['prioridade']); ?>
                                                </span>
                                            </div>
                                            <div class="assunto-actions">
                                                <a href="edit.php?id=<?php echo $sub['id']; ?>"><i class="fas fa-edit"></i></a>
                                                <a href="delete.php?id=<?php echo $sub['id']; ?>" onclick="return confirm('Excluir subassunto?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function filtrarAssuntos() {
            const prioridade = document.getElementById('filtro-prioridade').value.toLowerCase();
            const dificuldade = document.getElementById('filtro-dificuldade').value.toLowerCase();
            const status = document.getElementById('filtro-status').value;
            const busca = document.getElementById('filtro-busca').value.toLowerCase();
            
            const assuntos = document.querySelectorAll('.assunto-card');
            
            assuntos.forEach(assunto => {
                let mostrar = true;
                
                // Filtro por prioridade
                if (prioridade && assunto.dataset.prioridade !== prioridade) {
                    mostrar = false;
                }
                
                // Filtro por dificuldade
                if (dificuldade && assunto.dataset.dificuldade !== dificuldade) {
                    mostrar = false;
                }
                
                // Filtro por status
                if (status === 'concluido' && assunto.dataset.concluido !== '1') {
                    mostrar = false;
                } else if (status === 'andamento' && (assunto.dataset.concluido === '1' || assunto.dataset.percentual === '0')) {
                    mostrar = false;
                } else if (status === 'nao-iniciado' && assunto.dataset.percentual !== '0') {
                    mostrar = false;
                }
                
                // Filtro por busca
                if (busca && !assunto.dataset.nome.includes(busca)) {
                    mostrar = false;
                }
                
                assunto.style.display = mostrar ? 'block' : 'none';
            });
        }
        
        // Atualizar filtros em tempo real
        document.getElementById('filtro-busca').addEventListener('keyup', filtrarAssuntos);
    </script>
</body>
</html>