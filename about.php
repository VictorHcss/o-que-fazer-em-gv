<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - O Que Fazer em GV</title>
    <meta name="description" content="Conheça a história e missão do O Que Fazer em GV. Descubra como ajudamos você a encontrar os melhores lugares de Governador Valadares.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .about-page {
            min-height: 100vh;
            background: var(--white);
            padding-top: 80px;
        }
        
        .hero-about {
            background: var(--gradient-hero);
            color: var(--text-white);
            padding: var(--space-20) 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-about::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        
        .hero-about .container {
            position: relative;
            z-index: 2;
        }
        
        .hero-about h1 {
            font-family: var(--font-secondary);
            font-size: var(--text-5xl);
            font-weight: 800;
            margin-bottom: var(--space-6);
            color: var(--text-white);
        }
        
        .hero-about p {
            font-size: var(--text-xl);
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: var(--leading-relaxed);
        }
        
        .about-content {
            padding: var(--space-20) 0;
        }
        
        .about-section {
            margin-bottom: var(--space-16);
        }
        
        .about-section h2 {
            font-family: var(--font-secondary);
            font-size: var(--text-4xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-6);
            text-align: center;
        }
        
        .about-section p {
            font-size: var(--text-lg);
            color: var(--text-muted);
            line-height: var(--leading-relaxed);
            max-width: 800px;
            margin: 0 auto var(--space-6);
            text-align: center;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-8);
            margin-top: var(--space-12);
        }
        
        .feature-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            border: 1px solid var(--gray-200);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-3xl);
            color: var(--text-white);
            margin: 0 auto var(--space-6);
        }
        
        .feature-card h3 {
            font-family: var(--font-secondary);
            font-size: var(--text-2xl);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-4);
        }
        
        .feature-card p {
            color: var(--text-muted);
            line-height: var(--leading-relaxed);
            text-align: center;
            margin: 0;
        }
        
        .stats-section {
            background: var(--gray-50);
            padding: var(--space-16) 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-8);
        }
        
        .stat-card {
            text-align: center;
            padding: var(--space-6);
        }
        
        .stat-number {
            font-family: var(--font-secondary);
            font-size: var(--text-5xl);
            font-weight: 800;
            color: var(--primary-color);
            display: block;
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            font-size: var(--text-lg);
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .team-section {
            padding: var(--space-20) 0;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-8);
            margin-top: var(--space-12);
        }
        
        .team-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--space-6);
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .team-avatar {
            width: 120px;
            height: 120px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-4xl);
            color: var(--text-white);
            margin: 0 auto var(--space-4);
        }
        
        .team-card h4 {
            font-size: var(--text-xl);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }
        
        .team-role {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: var(--space-3);
        }
        
        .team-card p {
            color: var(--text-muted);
            font-size: var(--text-sm);
            line-height: var(--leading-relaxed);
            margin: 0;
        }
        
        .cta-section {
            background: var(--gradient-primary);
            color: var(--text-white);
            padding: var(--space-16) 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-family: var(--font-secondary);
            font-size: var(--text-4xl);
            font-weight: 700;
            margin-bottom: var(--space-4);
            color: var(--text-white);
        }
        
        .cta-section p {
            font-size: var(--text-xl);
            opacity: 0.9;
            margin-bottom: var(--space-8);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: var(--space-8);
        }
        
        .cta-buttons {
            display: flex;
            gap: var(--space-4);
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: var(--white);
            color: var(--primary-color);
            padding: var(--space-4) var(--space-8);
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--text-lg);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-outline-white {
            background: transparent;
            color: var(--text-white);
            border: 2px solid var(--white);
            padding: var(--space-4) var(--space-8);
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--text-lg);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .btn-outline-white:hover {
            background: var(--white);
            color: var(--primary-color);
        }
        
        .back-link {
            position: fixed;
            top: 100px;
            left: var(--space-6);
            background: var(--white);
            color: var(--text-primary);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 500;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-2);
            z-index: 1000;
        }
        
        .back-link:hover {
            transform: translateX(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        @media (max-width: 768px) {
            .hero-about h1 {
                font-size: var(--text-4xl);
            }
            
            .hero-about p {
                font-size: var(--text-lg);
            }
            
            .about-section h2 {
                font-size: var(--text-3xl);
            }
            
            .features-grid,
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .back-link {
                position: static;
                margin: var(--space-4) var(--space-4) 0;
                align-self: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .hero-about {
                padding: var(--space-12) 0;
            }
            
            .hero-about h1 {
                font-size: var(--text-3xl);
            }
            
            .about-content {
                padding: var(--space-12) 0;
            }
            
            .feature-card,
            .team-card {
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <div class="about-page">
        <a href="index.html" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Voltar ao Início</span>
        </a>
        
        <!-- Hero Section -->
        <section class="hero-about">
            <div class="container">
                <h1>Sobre o O Que Fazer em GV</h1>
                <p>Conectamos pessoas aos melhores lugares de Governador Valadares, criando experiências únicas e memoráveis na nossa querida cidade.</p>
            </div>
        </section>
        
        <!-- About Content -->
        <section class="about-content">
            <div class="container">
                <div class="about-section">
                    <h2>Nossa Missão</h2>
                    <p>Somos apaixonados por Governador Valadares e acreditamos que nossa cidade tem muito a oferecer. Nossa missão é ser a ponte entre você e os melhores estabelecimentos da região, facilitando a descoberta de novos lugares e experiências incríveis.</p>
                    <p>Através de uma plataforma moderna e intuitiva, reunimos informações detalhadas sobre restaurantes, bares, cafés, espaços culturais e muito mais, sempre com avaliações reais de usuários como você.</p>
                </div>
                
                <!-- Features -->
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Busca Inteligente</h3>
                        <p>Encontre exatamente o que procura com nossos filtros avançados e sistema de busca inteligente por categoria, localização e preferências.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Avaliações Reais</h3>
                        <p>Todas as avaliações são feitas por usuários reais que visitaram os estabelecimentos, garantindo informações confiáveis e atualizadas.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Localização Precisa</h3>
                        <p>Mapa interativo com localização exata de todos os estabelecimentos, facilitando o planejamento da sua visita.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3>Promoções Exclusivas</h3>
                        <p>Descubra ofertas especiais e promoções exclusivas dos estabelecimentos parceiros, economizando em suas experiências.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <h3>Agenda de Eventos</h3>
                        <p>Fique por dentro de todos os eventos, shows e atividades culturais que acontecem na cidade.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Totalmente Responsivo</h3>
                        <p>Acesse de qualquer dispositivo - computador, tablet ou smartphone - com a mesma experiência de qualidade.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="about-section">
                    <h2>Nossos Números</h2>
                    <p>Veja o impacto que estamos causando na comunidade de Governador Valadares</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Estabelecimentos</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-number">10k+</span>
                        <span class="stat-label">Usuários Ativos</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-number">15k+</span>
                        <span class="stat-label">Avaliações</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-number">4.8</span>
                        <span class="stat-label">Avaliação Média</span>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Team Section -->
        <section class="team-section">
            <div class="container">
                <div class="about-section">
                    <h2>Nossa Equipe</h2>
                    <p>Conheça as pessoas apaixonadas que trabalham todos os dias para melhorar sua experiência</p>
                </div>
                
                <div class="team-grid">
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4>João Silva</h4>
                        <div class="team-role">Fundador & CEO</div>
                        <p>Apaixonado por tecnologia e gastronomia, João criou a plataforma para conectar pessoas aos melhores lugares de GV.</p>
                    </div>
                    
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4>Maria Santos</h4>
                        <div class="team-role">Diretora de Marketing</div>
                        <p>Especialista em marketing digital, Maria cuida da comunicação e relacionamento com estabelecimentos parceiros.</p>
                    </div>
                    
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4>Pedro Costa</h4>
                        <div class="team-role">Desenvolvedor</div>
                        <p>Responsável pelo desenvolvimento e manutenção da plataforma, garantindo a melhor experiência para os usuários.</p>
                    </div>
                    
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4>Ana Oliveira</h4>
                        <div class="team-role">Community Manager</div>
                        <p>Cuida da comunidade de usuários, moderando avaliações e garantindo a qualidade do conteúdo da plataforma.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>Faça Parte da Nossa Comunidade</h2>
                <p>Junte-se a milhares de pessoas que já descobriram os melhores lugares de Governador Valadares através da nossa plataforma.</p>
                
                <div class="cta-buttons">
                    <a href="index.html" class="btn-white">
                        <i class="fas fa-home"></i>
                        <span>Explorar Agora</span>
                    </a>
                    <a href="contact.php" class="btn-outline-white">
                        <i class="fas fa-envelope"></i>
                        <span>Entre em Contato</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
