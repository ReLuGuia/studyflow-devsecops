<?php
session_start();


if (isset($_SESSION['usuario_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyFlow - Plataforma Inteligente de Estudos</title>
    <meta name="description" content="Organize seus estudos, acompanhe seu progresso e alcance seus objetivos com o StudyFlow">
    
    
    <link rel="stylesheet" href="assets/css/style.css">
    
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            font-size: 32px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
            font-size: 16px;
        }

        .nav-menu a:hover {
            opacity: 0.8;
        }

        .btn-login {
            background: white;
            color: #667eea !important;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #f0f0f0;
            opacity: 1 !important;
        }

        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 150px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 15px 35px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        
        .features {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
        }

        .section-title p {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .feature-icon i {
            font-size: 35px;
            color: white;
        }

        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        
        .how-it-works {
            padding: 100px 0;
            background: white;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .step {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin: 0 auto 25px;
        }

        .step h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .step p {
            color: #666;
            line-height: 1.6;
        }

        
        .stats {
            padding: 80px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 40px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .stat-item p {
            font-size: 18px;
            opacity: 0.9;
        }

        .testimonials {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .testimonial-text {
            font-style: italic;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }

        .author-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        .author-info p {
            font-size: 14px;
            color: #666;
        }

        .cta {
            padding: 100px 0;
            background: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #333;
        }

        .cta p {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta .btn {
            font-size: 18px;
            padding: 15px 40px;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: #667eea;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-col ul li a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .social-links a:hover {
            background: #667eea;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bdc3c7;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .section-title h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-brain"></i>
                StudyFlow
            </a>
            <ul class="nav-menu">
                <li><a href="#features">Recursos</a></li>
                <li><a href="#how-it-works">Como Funciona</a></li>
                <li><a href="#testimonials">Depoimentos</a></li>
                <li><a href="#contact">Contato</a></li>
                <li><a href="pages/login.php" class="btn-login">Entrar</a></li>
                <li><a href="pages/register.php">Cadastrar</a></li>
            </ul>
        </nav>
    </header>
    
    <section class="hero">
        <div class="hero-content">
            <h1>Transforme seus estudos em resultados</h1>
            <p>Organize seu tempo, acompanhe seu progresso e alcance seus objetivos com a plataforma inteligente de estudos</p>
            <div class="hero-buttons">
                <a href="pages/register.php" class="btn btn-primary">Começar Grátis</a>
                <a href="#features" class="btn btn-secondary">Conhecer Recursos</a>
            </div>
        </div>
    </section>
  
    <section id="features" class="features">
        <div class="container">
            <div class="section-title">
                <h2>Tudo que você precisa para estudar melhor</h2>
                <p>Ferramentas completas para organizar, planejar e acompanhar seus estudos</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Planejamento Semanal</h3>
                    <p>Organize sua rotina de estudos com planejamento personalizado por dia da semana</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Disciplinas e Assuntos</h3>
                    <p>Estruture seu conteúdo em disciplinas e assuntos com níveis de prioridade</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Sessões de Estudo</h3>
                    <p>Registre seu tempo de estudo e acompanhe seu progresso em cada assunto</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Revisões Espaçadas</h3>
                    <p>Sistema inteligente de revisões para fixar o conteúdo a longo prazo</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>Banco de Questões</h3>
                    <p>Resolva questões e acompanhe seu desempenho por assunto e dificuldade</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Gamificação</h3>
                    <p>Ganhe conquistas e pontos conforme avança nos estudos e mantém a consistência</p>
                </div>
            </div>
        </div>
    </section>
   
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>Como começar a usar</h2>
                <p>Em poucos passos você já pode organizar seus estudos</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Crie sua conta</h3>
                    <p>Registre-se gratuitamente e configure seu perfil com seus objetivos</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Adicione disciplinas</h3>
                    <p>Cadastre as matérias que você precisa estudar e organize por prioridade</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Planeje seus estudos</h3>
                    <p>Defina seu cronograma semanal e comece a registrar suas sessões</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Acompanhe resultados</h3>
                    <p>Monitore seu progresso, faça revisões e alcance suas metas</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>10.000+</h3>
                    <p>Usuários Ativos</p>
                </div>
                <div class="stat-item">
                    <h3>500.000+</h3>
                    <p>Horas Estudadas</p>
                </div>
                <div class="stat-item">
                    <h3>1M+</h3>
                    <p>Questões Resolvidas</p>
                </div>
                <div class="stat-item">
                    <h3>95%</h3>
                    <p>Satisfação</p>
                </div>
            </div>
        </div>
    </section>
 
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>O que nossos usuários dizem</h2>
                <p>Histórias reais de quem transformou seus estudos com o StudyFlow</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"Consegui organizar meus estudos para concurso público de forma muito mais eficiente. O sistema de revisões espaçadas fez toda diferença na minha aprovação!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">M</div>
                        <div class="author-info">
                            <h4>Maria Silva</h4>
                            <p>Aprovada em 1º lugar - TRF</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Como estudante de medicina, precisava de uma ferramenta que me ajudasse a gerenciar o volume enorme de conteúdo. O StudyFlow salvou meu semestre!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">J</div>
                        <div class="author-info">
                            <h4>João Pedro</h4>
                            <p>Estudante de Medicina - USP</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"A gamificação me motiva a estudar todos os dias. Parece até vício bom! Já são 150 dias consecutivos de estudo."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">A</div>
                        <div class="author-info">
                            <h4>Ana Carolina</h4>
                            <p>Estudante de Direito</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Pronto para transformar seus estudos?</h2>
            <p>Junte-se a milhares de estudantes que já estão alcançando seus objetivos com o StudyFlow</p>
            <a href="pages/register.php" class="btn btn-primary">Começar Agora - É Grátis!</a>
        </div>
    </section>

    
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>StudyFlow</h4>
                    <p>Sua plataforma inteligente de estudos para alcançar resultados extraordinários.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Links Rápidos</h4>
                    <ul>
                        <li><a href="#features">Recursos</a></li>
                        <li><a href="#how-it-works">Como Funciona</a></li>
                        <li><a href="#testimonials">Depoimentos</a></li>
                        <li><a href="#">Preços</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                        <li><a href="#">Cookies</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contato</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> contato@studyflow.com</li>
                        <li><i class="fas fa-phone"></i> (11) 99999-9999</li>
                        <li><i class="fas fa-map-marker-alt"></i> São Paulo, SP</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 StudyFlow. Todos os direitos reservados. Desenvolvido com <i class="fas fa-heart" style="color: #e74c3c;"></i> para estudantes</p>
            </div>
        </div>
    </footer>
</body>
</html>