<?php
session_start();
require_once 'config/database.php';

// Buscar promoções ativas
$stmt = $pdo->query("
    SELECT p.*, e.name as establishment_name, e.image_url, e.category, e.neighborhood
    FROM promotions p
    JOIN establishments e ON p.establishment_id = e.id
    WHERE p.is_active = 1 
    AND p.start_date <= CURDATE() 
    AND p.end_date >= CURDATE()
    AND e.is_active = 1
    ORDER BY p.created_at DESC
");
$promotions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promoções - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .promotions-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .promotions-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .promotions-header h1 {
            font-size: 3em;
            color: #333;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .promotions-header p {
            font-size: 1.2em;
            color: #666;
        }

        .promotions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
        }

        .promotion-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .promotion-card:hover {
            transform: translateY(-10px);
        }

        .promotion-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #e91e63, #f06292);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1em;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
        }

        .promotion-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            position: relative;
        }

        .promotion-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 30px 25px 20px;
        }

        .promotion-category {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .promotion-establishment {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .promotion-location {
            font-size: 0.9em;
            opacity: 0.8;
        }

        .promotion-content {
            padding: 30px;
        }

        .promotion-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .promotion-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        .promotion-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .promotion-discount {
            text-align: center;
        }

        .discount-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #e91e63;
            line-height: 1;
        }

        .discount-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .promotion-validity {
            text-align: center;
        }

        .validity-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .validity-date {
            color: #333;
            font-weight: bold;
        }

        .promotion-actions {
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
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
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
            .promotions-container {
                padding: 80px 10px 20px;
            }

            .promotions-grid {
                grid-template-columns: 1fr;
            }

            .promotion-details {
                flex-direction: column;
                gap: 20px;
            }

            .promotion-actions {
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

    <div class="promotions-container">
        <div class="promotions-header">
            <h1><i class="fas fa-tags"></i> Promoções Especiais</h1>
            <p>Aproveite as melhores ofertas dos estabelecimentos de Governador Valadares</p>
        </div>

        <?php if (empty($promotions)): ?>
            <div class="empty-state">
                <i class="fas fa-percentage"></i>
                <h3>Nenhuma promoção ativa</h3>
                <p>No momento não há promoções disponíveis.<br>
                   Volte em breve para conferir as novidades!</p>
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Explorar Estabelecimentos
                </a>
            </div>
        <?php else: ?>
            <div class="promotions-grid">
                <?php foreach ($promotions as $promotion): ?>
                    <div class="promotion-card">
                        <div class="promotion-badge">
                            <?php if ($promotion['discount_percentage']): ?>
                                <?php echo number_format($promotion['discount_percentage'], 0); ?>% OFF
                            <?php else: ?>
                                OFERTA
                            <?php endif; ?>
                        </div>
                        
                        <div style="position: relative;">
                            <img src="<?php echo $promotion['image_url'] ?: '/placeholder.svg?height=250&width=400'; ?>" 
                                 alt="<?php echo htmlspecialchars($promotion['establishment_name']); ?>" 
                                 class="promotion-image">
                            
                            <div class="promotion-overlay">
                                <div class="promotion-category"><?php echo htmlspecialchars($promotion['category']); ?></div>
                                <div class="promotion-establishment"><?php echo htmlspecialchars($promotion['establishment_name']); ?></div>
                                <div class="promotion-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($promotion['neighborhood']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="promotion-content">
                            <h3 class="promotion-title"><?php echo htmlspecialchars($promotion['title']); ?></h3>
                            <p class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></p>
                            
                            <div class="promotion-details">
                                <?php if ($promotion['discount_percentage']): ?>
                                    <div class="promotion-discount">
                                        <div class="discount-value"><?php echo number_format($promotion['discount_percentage'], 0); ?>%</div>
                                        <div class="discount-label">Desconto</div>
                                    </div>
                                <?php elseif ($promotion['discount_amount']): ?>
                                    <div class="promotion-discount">
                                        <div class="discount-value">R$ <?php echo number_format($promotion['discount_amount'], 2, ',', '.'); ?></div>
                                        <div class="discount-label">Desconto</div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="promotion-validity">
                                    <div class="validity-label">Válido até</div>
                                    <div class="validity-date"><?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($promotion['terms_conditions']): ?>
                                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                                    <small style="color: #856404;">
                                        <strong>Termos:</strong> <?php echo htmlspecialchars($promotion['terms_conditions']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="promotion-actions">
                                <a href="establishment.php?id=<?php echo $promotion['establishment_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    Ver Estabelecimento
                                </a>
                                <button onclick="sharePromotion(<?php echo $promotion['id']; ?>)" class="btn btn-secondary">
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
        function sharePromotion(promotionId) {
            const url = window.location.href + '?promotion=' + promotionId;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Promoção Especial - O Que Fazer em GV',
                    text: 'Confira esta promoção incrível!',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link da promoção copiado para a área de transferência!');
                });
            }
        }
    </script>
</body>
</html>
