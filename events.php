<?php
session_start();
require_once 'config/database.php';

// Buscar eventos futuros
$stmt = $pdo->query("
    SELECT ev.*, e.name as establishment_name, e.image_url, e.category, e.neighborhood, e.address
    FROM events ev
    JOIN establishments e ON ev.establishment_id = e.id
    WHERE ev.is_active = 1 
    AND ev.event_date >= CURDATE()
    AND e.is_active = 1
    ORDER BY ev.event_date ASC, ev.start_time ASC
");
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .events-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .events-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .events-header h1 {
            font-size: 3em;
            color: #333;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .events-header p {
            font-size: 1.2em;
            color: #666;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-10px);
        }

        .event-date-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .date-day {
            font-size: 1.8em;
            font-weight: bold;
            line-height: 1;
        }

        .date-month {
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .event-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            position: relative;
        }

        .event-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 30px 25px 20px;
        }

        .event-category {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .event-establishment {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .event-location {
            font-size: 0.9em;
            opacity: 0.8;
        }

        .event-content {
            padding: 30px;
        }

        .event-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .event-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 1.1em;
        }

        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .event-detail {
            text-align: center;
        }

        .detail-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: white;
            font-size: 1.2em;
        }

        .detail-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #333;
            font-weight: bold;
        }

        .event-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #ddd;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .events-container {
                padding: 80px 10px 20px;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .event-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .event-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><a href="index.html" style="text-decoration: none; color: inherit;"><i class="fas fa-map-marker-alt"></i> O Que Fazer em GV</a></h1>
            </div>
            <nav class="nav">
                <a href="index.html" class="nav-link">Início</a>
                <a href="promotions.php" class="nav-link">Promoções</a>
                <a href="events.php" class="nav-link">Eventos</a>
                <a href="about.php" class="nav-link">Sobre</a>
            </nav>
            <div class="auth-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-dropdown">
                            <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                            <a href="my-favorites.php"><i class="fas fa-heart"></i> Favoritos</a>
                            <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="auth/login.php" class="btn-login">Entrar</a>
                        <a href="auth/register.php" class="btn-register">Cadastrar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="events-container">
        <div class="events-header">
            <h1><i class="fas fa-calendar-alt"></i> Eventos</h1>
            <p>Descubra os melhores eventos acontecendo em Governador Valadares</p>
        </div>

        <?php if (empty($events)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Nenhum evento programado</h3>
                <p>No momento não há eventos futuros cadastrados.<br>
                   Volte em breve para conferir a programação!</p>
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Explorar Estabelecimentos
                </a>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="event-date-badge">
                            <div class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                            <div class="date-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                        </div>
                        
                        <div style="position: relative;">
                            <img src="<?php echo $event['image_url'] ?: '/placeholder.svg?height=250&width=400'; ?>" 
                                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                 class="event-image">
                            
                            <div class="event-overlay">
                                <div class="event-category"><?php echo htmlspecialchars($event['category']); ?></div>
                                <div class="event-establishment"><?php echo htmlspecialchars($event['establishment_name']); ?></div>
                                <div class="event-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($event['neighborhood']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="event-content">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <div class="event-details">
                                <div class="event-detail">
                                    <div class="detail-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="detail-label">Data</div>
                                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($event['event_date'])); ?></div>
                                </div>
                                
                                <?php if ($event['start_time']): ?>
                                    <div class="event-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="detail-label">Horário</div>
                                        <div class="detail-value">
                                            <?php echo date('H:i', strtotime($event['start_time'])); ?>
                                            <?php if ($event['end_time']): ?>
                                                - <?php echo date('H:i', strtotime($event['end_time'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($event['price']): ?>
                                    <div class="event-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                        <div class="detail-label">Ingresso</div>
                                        <div class="detail-value">R$ <?php echo number_format($event['price'], 2, ',', '.'); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($event['max_capacity']): ?>
                                    <div class="event-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="detail-label">Vagas</div>
                                        <div class="detail-value">
                                            <?php echo ($event['max_capacity'] - $event['current_bookings']); ?> / <?php echo $event['max_capacity']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="event-actions">
                                <a href="establishment.php?id=<?php echo $event['establishment_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Ver Local
                                </a>
                                <button onclick="shareEvent(<?php echo $event['id']; ?>)" class="btn btn-secondary">
                                    <i class="fas fa-share-alt"></i>
                                    Compartilhar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function shareEvent(eventId) {
            const url = window.location.href + '?event=' + eventId;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Evento - O Que Fazer em GV',
                    text: 'Confira este evento incrível!',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link do evento copiado para a área de transferência!');
                });
            }
        }
    </script>
</body>
</html>
