<?php
require_once '../../includes/config.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar sessão
$stmt = $pdo->prepare("
    SELECT s.*, a.nome as assunto_nome, d.nome as disciplina_nome,
           d.cor as disciplina_cor, a.id as assunto_id
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

// Atualizar data de início se não tiver
if (!$sessao['data_inicio']) {
    $stmt = $pdo->prepare("UPDATE sessoes_estudo SET data_inicio = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $sessao['data_inicio'] = date('Y-m-d H:i:s');
}

// Calcular tempo planejado em segundos para o cronômetro
$tempo_planejado_segundos = $sessao['tempo_planejado'] * 60;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronômetro de Estudo - <?php echo htmlspecialchars($sessao['assunto_nome']); ?> - StudyFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .cronometro-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .disciplina-badge {
            display: inline-block;
            padding: 5px 15px;
            background: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .assunto-nome {
            font-size: 28px;
            color: #333;
            margin: 10px 0;
        }
        
        .tipo-sessao {
            color: #666;
            font-size: 16px;
        }
        
        .tipo-sessao i {
            margin-right: 5px;
            color: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
        }
        
        .timer-display {
            text-align: center;
            margin: 30px 0;
        }
        
        .timer-circle {
            width: 300px;
            height: 300px;
            margin: 0 auto;
            position: relative;
        }
        
        .timer-svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .timer-svg circle {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
        }
        
        .timer-background {
            stroke: #f0f0f0;
        }
        
        .timer-progress {
            stroke: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
            stroke-dasharray: 879.2;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 1s linear;
        }
        
        .timer-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .timer-time {
            font-size: 48px;
            font-weight: bold;
            color: #333;
            font-family: monospace;
        }
        
        .timer-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .timer-controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn-control {
            width: 60px;
            height: 60px;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .btn-control:hover {
            transform: scale(1.1);
        }
        
        .btn-control:active {
            transform: scale(0.95);
        }
        
        .btn-play {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-pause {
            background: linear-gradient(135deg, #f39c12, #f1c40f);
            color: white;
            display: none;
        }
        
        .btn-stop {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .info-panel {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-value {
            font-size: 20px;
            font-weight: bold;
            color: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
        }
        
        .anotacoes-area {
            margin: 20px 0;
        }
        
        .anotacoes-area textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.3s;
        }
        
        .anotacoes-area textarea:focus {
            border-color: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
            outline: none;
        }
        
        .btn-concluir {
            background: linear-gradient(135deg, <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>, #9b59b6);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 20px;
        }
        
        .btn-concluir:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-voltar {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        
        .progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>, #9b59b6);
            width: 0%;
            transition: width 0.3s;
        }
        
        .pomodoro-counter {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 15px 0;
        }
        
        .pomodoro-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #f0f0f0;
            transition: background 0.3s;
        }
        
        .pomodoro-dot.active {
            background: <?php echo $sessao['disciplina_cor'] ?? '#667eea'; ?>;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            text-align: center;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-content .foco-slider {
            width: 100%;
            margin: 20px 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
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
        
        @media (max-width: 768px) {
            .cronometro-container {
                padding: 20px;
            }
            
            .timer-circle {
                width: 250px;
                height: 250px;
            }
            
            .timer-time {
                font-size: 36px;
            }
            
            .info-panel {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="cronometro-container">
        <!-- Cabeçalho -->
        <div class="header-info">
            <span class="disciplina-badge">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($sessao['disciplina_nome']); ?>
            </span>
            <h1 class="assunto-nome"><?php echo htmlspecialchars($sessao['assunto_nome']); ?></h1>
            <p class="tipo-sessao">
                <i class="fas fa-tag"></i> <?php echo ucfirst($sessao['tipo_sessao']); ?>
            </p>
        </div>
        
        <!-- Timer Circular -->
        <div class="timer-display">
            <div class="timer-circle">
                <svg class="timer-svg" viewBox="0 0 300 300">
                    <circle class="timer-background" cx="150" cy="150" r="140"></circle>
                    <circle class="timer-progress" cx="150" cy="150" r="140" 
                            style="stroke-dashoffset: 879.2;" 
                            id="timerProgress"></circle>
                </svg>
                <div class="timer-text">
                    <div class="timer-time" id="timer">00:00</div>
                    <div class="timer-label">tempo decorrido</div>
                </div>
            </div>
        </div>
        
        <!-- Barra de Progresso -->
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        
        <!-- Controles -->
        <div class="timer-controls">
            <button class="btn-control btn-play" id="btnPlay" onclick="iniciarCronometro()">
                <i class="fas fa-play"></i>
            </button>
            <button class="btn-control btn-pause" id="btnPause" onclick="pausarCronometro()">
                <i class="fas fa-pause"></i>
            </button>
            <button class="btn-control btn-stop" onclick="pararCronometro()">
                <i class="fas fa-stop"></i>
            </button>
            <button class="btn-control btn-reset" onclick="resetarCronometro()">
                <i class="fas fa-redo-alt"></i>
            </button>
        </div>
        
        <!-- Informações -->
        <div class="info-panel">
            <div class="info-item">
                <div class="info-label">Início</div>
                <div class="info-value" id="horaInicio">
                    <?php echo date('H:i', strtotime($sessao['data_inicio'])); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Planejado</div>
                <div class="info-value"><?php echo $sessao['tempo_planejado']; ?> min</div>
            </div>
            <div class="info-item">
                <div class="info-label">Foco Atual</div>
                <div class="info-value" id="focoAtual">0%</div>
            </div>
        </div>
        
        <!-- Contador Pomodoro (opcional) -->
        <div class="pomodoro-counter" id="pomodoroCounter">
            <span class="pomodoro-dot active"></span>
            <span class="pomodoro-dot"></span>
            <span class="pomodoro-dot"></span>
            <span class="pomodoro-dot"></span>
        </div>
        
        <!-- Área de Anotações Rápidas -->
        <div class="anotacoes-area">
            <textarea id="anotacao" placeholder="Faça anotações rápidas durante o estudo..."></textarea>
        </div>
        
        <!-- Botão Concluir -->
        <button class="btn-concluir" onclick="abrirModalConclusao()">
            <i class="fas fa-check-circle"></i> Concluir Sessão de Estudo
        </button>
        
        <!-- Link Voltar -->
        <div style="text-align: center;">
            <a href="index.php?assunto_id=<?php echo $sessao['assunto_id']; ?>" class="btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar sem concluir
            </a>
        </div>
    </div>
    
    <!-- Modal de Conclusão -->
    <div class="modal" id="modalConclusao">
        <div class="modal-content">
            <h3><i class="fas fa-trophy"></i> Concluir Sessão</h3>
            
            <div class="info-item" style="margin-bottom: 20px;">
                <div class="info-label">Tempo estudado</div>
                <div class="info-value" id="tempoEstudadoModal">0 minutos</div>
            </div>
            
            <label for="focoModal">Nível de foco (0-100%):</label>
            <input type="range" id="focoModal" class="foco-slider" min="0" max="100" step="5" value="80"
                   oninput="document.getElementById('focoValorModal').textContent = this.value + '%'">
            <div style="text-align: center; margin: 10px 0;">
                <span id="focoValorModal">80%</span>
            </div>
            
            <label for="anotacaoModal">O que você aprendeu?</label>
            <textarea id="anotacaoModal" rows="3" style="width: 100%; padding: 10px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 5px;"></textarea>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-control" style="width: 50%; border-radius: 5px; background: #27ae60;" onclick="concluirSessao()">
                    <i class="fas fa-check"></i> Confirmar
                </button>
                <button class="btn-control" style="width: 50%; border-radius: 5px; background: #e74c3c;" onclick="fecharModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Alertas -->
    <div class="alert alert-success" id="alertSuccess"></div>
    <div class="alert alert-error" id="alertError"></div>
    
    <script>
        // Variáveis do cronômetro
        let inicioCronometro = <?php echo strtotime($sessao['data_inicio']) * 1000; ?>;
        let tempoDecorrido = 0;
        let tempoPausado = 0;
        let timerInterval;
        let estaRodando = false;
        let estaPausado = false;
        const tempoPlanejado = <?php echo $tempo_planejado_segundos; ?>;
        const circunferencia = 879.2; // 2 * π * 140
        let pomodoroCount = 0;
        
        // Elementos do DOM
        const timerDisplay = document.getElementById('timer');
        const timerProgress = document.getElementById('timerProgress');
        const progressFill = document.getElementById('progressFill');
        const btnPlay = document.getElementById('btnPlay');
        const btnPause = document.getElementById('btnPause');
        const focoAtual = document.getElementById('focoAtual');
        
        // Inicializar cronômetro
        function iniciarCronometro() {
            if (!estaRodando) {
                if (estaPausado) {
                    // Retomar de onde parou
                    inicioCronometro = Date.now() - tempoDecorrido * 1000;
                } else {
                    // Iniciar novo
                    inicioCronometro = Date.now();
                }
                
                timerInterval = setInterval(atualizarCronometro, 1000);
                estaRodando = true;
                estaPausado = false;
                
                btnPlay.style.display = 'none';
                btnPause.style.display = 'flex';
            }
        }
        
        function pausarCronometro() {
            if (estaRodando) {
                clearInterval(timerInterval);
                estaRodando = false;
                estaPausado = true;
                
                btnPlay.style.display = 'flex';
                btnPause.style.display = 'none';
            }
        }
        
        function resetarCronometro() {
            if (confirm('Reiniciar o cronômetro? O tempo atual será perdido.')) {
                clearInterval(timerInterval);
                tempoDecorrido = 0;
                estaRodando = false;
                estaPausado = false;
                
                atualizarDisplay(0);
                atualizarProgresso(0);
                
                btnPlay.style.display = 'flex';
                btnPause.style.display = 'none';
            }
        }
        
        function pararCronometro() {
            if (confirm('Parar o cronômetro? Você poderá retomar depois.')) {
                pausarCronometro();
            }
        }
        
        function atualizarCronometro() {
            const agora = Date.now();
            tempoDecorrido = Math.floor((agora - inicioCronometro) / 1000);
            
            atualizarDisplay(tempoDecorrido);
            atualizarProgresso(tempoDecorrido);
            calcularFoco();
            
            // Verificar se atingiu o tempo planejado
            if (tempoDecorrido >= tempoPlanejado) {
                atingiuTempoPlanejado();
            }
            
            // Pomodoro a cada 25 minutos (1500 segundos)
            if (tempoDecorrido > 0 && tempoDecorrido % 1500 === 0) {
                pomodoroCount = Math.min(pomodoroCount + 1, 4);
                atualizarPomodoro();
                if (pomodoroCount < 4) {
                    mostrarAlerta('success', '🍅 Pomodoro completo! Hora de uma pausa curta de 5 minutos.');
                } else {
                    mostrarAlerta('success', '🎉 Ciclo Pomodoro completo! Faça uma pausa longa de 15 minutos.');
                    pomodoroCount = 0;
                    atualizarPomodoro();
                }
            }
        }
        
        function atualizarDisplay(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;
            
            const tempoStr = horas > 0 
                ? `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`
                : `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
            
            timerDisplay.textContent = tempoStr;
        }
        
        function atualizarProgresso(segundos) {
            const progresso = Math.min((segundos / tempoPlanejado) * 100, 100);
            progressFill.style.width = progresso + '%';
            
            const offset = circunferencia - (progresso / 100) * circunferencia;
            timerProgress.style.strokeDashoffset = offset;
        }
        
        function calcularFoco() {
            // Simula cálculo de foco baseado em interações (cliques, scroll, etc)
            // Em uma versão real, você poderia usar a API de idle time
            const foco = Math.min(80 + Math.floor(Math.random() * 20), 100);
            focoAtual.textContent = foco + '%';
        }
        
        function atualizarPomodoro() {
            const dots = document.querySelectorAll('.pomodoro-dot');
            dots.forEach((dot, index) => {
                if (index < pomodoroCount) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        function atingiuTempoPlanejado() {
            mostrarAlerta('success', '🎯 Parabéns! Você atingiu o tempo planejado para esta sessão!');
            // Tocar som de notificação
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRlwAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YVAAAAA8Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PD48Pjw+PA==');
                audio.play();
            } catch (e) {
                console.log('Não foi possível tocar som');
            }
        }
        
        function mostrarAlerta(tipo, mensagem) {
            const alert = tipo === 'success' ? document.getElementById('alertSuccess') : document.getElementById('alertError');
            alert.textContent = mensagem;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        function abrirModalConclusao() {
            pausarCronometro();
            
            const horas = Math.floor(tempoDecorrido / 3600);
            const minutos = Math.floor((tempoDecorrido % 3600) / 60);
            const tempoStr = horas > 0 
                ? `${horas} hora(s) e ${minutos} minuto(s)`
                : `${minutos} minuto(s)`;
            
            document.getElementById('tempoEstudadoModal').textContent = tempoStr;
            document.getElementById('modalConclusao').style.display = 'flex';
        }
        
        function fecharModal() {
            document.getElementById('modalConclusao').style.display = 'none';
        }
        
        function concluirSessao() {
            const foco = document.getElementById('focoModal').value;
            const anotacao = document.getElementById('anotacaoModal').value;
            const anotacaoRapida = document.getElementById('anotacao').value;
            
            // Combinar anotações
            const anotacaoCompleta = anotacaoRapida + (anotacaoRapida && anotacao ? '\n\n' : '') + anotacao;
            
            // Criar formulário para enviar os dados
            const form = document.createElement('form');
            form.method = 'POST';
           form.action = 'concluir.php?id=<?php echo htmlentities($id); ?>';
            
            const inputTempo = document.createElement('input');
            inputTempo.type = 'hidden';
            inputTempo.name = 'tempo_minutos';
            inputTempo.value = Math.ceil(tempoDecorrido / 60);
            
            const inputFoco = document.createElement('input');
            inputFoco.type = 'hidden';
            inputFoco.name = 'foco_percentual';
            inputFoco.value = foco;
            
            const inputAnotacao = document.createElement('input');
            inputAnotacao.type = 'hidden';
            inputAnotacao.name = 'anotacao';
            inputAnotacao.value = anotacaoCompleta;
            
            form.appendChild(inputTempo);
            form.appendChild(inputFoco);
            form.appendChild(inputAnotacao);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Event listeners para pausar quando a janela perde foco
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && estaRodando) {
                pausarCronometro();
                mostrarAlerta('error', '⏸️ Estudo pausado automaticamente');
            }
        });
        
        // Prevenir fechamento acidental
        window.addEventListener('beforeunload', function(e) {
            if (estaRodando && tempoDecorrido > 0) {
                e.preventDefault();
                e.returnValue = 'Você tem uma sessão de estudo em andamento. Tem certeza que deseja sair?';
            }
        });
        
        // Carregar anotações do localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const savedAnotacao = localStorage.getItem('sessao_anotacao_<?php echo htmlentities($id); ?>');
            if (savedAnotacao) {
                document.getElementById('anotacao').value = savedAnotacao;
            }
        });
        
        // Salvar anotações automaticamente
        setInterval(function() {
            const anotacao = document.getElementById('anotacao').value;
            localStorage.setItem('sessao_anotacao_<?php echo htmlentities($id); ?>', anotacao);
        }, 5000);
        
        // Atualizar display inicial
        atualizarDisplay(0);
    </script>
</body>
</html>