<?php
session_start();
require_once 'config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.html');
    exit;
}

// Buscar estabelecimento
$stmt = $pdo->prepare("
    SELECT e.*, 
           AVG(r.rating) as avg_rating,
           COUNT(r.id) as total_reviews
    FROM establishments e
    LEFT JOIN reviews r ON e.id = r.establishment_id
    WHERE e.id = ? AND e.is_active = 1
    GROUP BY e.id
");
$stmt->execute([$id]);
$establishment = $stmt->fetch();

if (!$establishment) {
    header('Location: index.html');
    exit;
}

// Buscar avaliações
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.avatar
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.establishment_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Verificar se usuário favoritou (se logado)
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND establishment_id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $is_favorited = $stmt->fetch() ? true : false;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($establishment['name']); ?> - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .establishment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .establishment-hero {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .hero-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            position: relative;
        }

        .hero-content {
            padding: 40px;
        }

        .establishment-category {
            color: #ff6b6b;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .establishment-title {
            font-size: 3em;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .establishment-rating {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .rating-stars {
            color: #ffa500;
            font-size: 1.5em;
        }

        .rating-text {
            color: #666;
            font-size: 1.1em;
        }

        .establishment-description {
            font-size: 1.2em;
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .establishment-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 1.1em;
            transition: all 0.3s ease;
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

        .btn-favorite {
            background: #e91e63;
            color: white;
        }

        .btn-favorite.active {
            background: #c2185b;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .establishment-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .details-card,
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #ff6b6b;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .info-content h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .info-content p {
            margin: 0;
            color: #666;
        }

        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .feature-tag {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .reviews-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .review-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .rating-input {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
        }

        .rating-star {
            font-size: 2em;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #ffa500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1em;
            resize: vertical;
            min-height: 100px;
            box-sizing: border-box;
        }

        .review-item {
            display: flex;
            gap: 20px;
            padding: 25px 0;
            border-bottom: 1px solid #eee;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }

        .review-content h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .review-rating {
            color: #ffa500;
            margin-bottom: 10px;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .review-date {
            color: #999;
            font-size: 0.9em;
        }

        .map-container {
            height: 300px;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .establishment-container {
                padding: 80px 10px 20px;
            }

            .establishment-title {
                font-size: 2em;
            }

            .establishment-details {
                grid-template-columns: 1fr;
            }

            .establishment-actions {
                justify-content: center;
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

    <div class="establishment-container">
        <div class="establishment-hero">
            <img src="<?php echo $establishment['image_url'] ?: '/placeholder.svg?height=400&width=1200'; ?>" 
                 alt="<?php echo htmlspecialchars($establishment['name']); ?>" 
                 class="hero-image">
            
            <div class="hero-content">
                <div class="establishment-category"><?php echo htmlspecialchars($establishment['category']); ?></div>
                <h1 class="establishment-title"><?php echo htmlspecialchars($establishment['name']); ?></h1>
                
                <div class="establishment-rating">
                    <div class="rating-stars">
                        <?php 
                        $rating = round($establishment['avg_rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <i class="fas fa-star<?php echo $i <= $rating ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text">
                        <?php echo number_format($establishment['avg_rating'] ?? 0, 1); ?> 
                        (<?php echo $establishment['total_reviews']; ?> avaliações)
                    </span>
                </div>
                
                <p class="establishment-description">
                    <?php echo htmlspecialchars($establishment['description']); ?>
                </p>
                
                <div class="establishment-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-favorite <?php echo $is_favorited ? 'active' : ''; ?>" 
                                onclick="toggleFavorite(<?php echo $establishment['id']; ?>)">
                            <i class="fas fa-heart"></i>
                            <?php echo $is_favorited ? 'Favoritado' : 'Favoritar'; ?>
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary" onclick="getDirections()">
                        <i class="fas fa-directions"></i>
                        Como Chegar
                    </button>
                    
                    <button class="btn btn-secondary" onclick="shareEstablishment()">
                        <i class="fas fa-share-alt"></i>
                        Compartilhar
                    </button>
                    
                    <?php if ($establishment['phone']): ?>
                        <a href="tel:<?php echo $establishment['phone']; ?>" class="btn btn-secondary">
                            <i class="fas fa-phone"></i>
                            Ligar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="establishment-details">
            <div class="details-card">
                <h2 class="card-title">
                    <i class="fas fa-info-circle"></i>
                    Informações Detalhadas
                </h2>
                
                <?php if ($establishment['features']): ?>
                    <h3>Características</h3>
                    <div class="features-list">
                        <?php 
                        $features = json_decode($establishment['features'], true) ?: [];
                        foreach ($features as $feature): 
                        ?>
                            <span class="feature-tag"><?php echo htmlspecialchars($feature); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($establishment['latitude'] && $establishment['longitude']): ?>
                    <h3 style="margin-top: 30px;">Localização</h3>
                    <div class="map-container" id="map"></div>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <h2 class="card-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Informações de Contato
                </h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h4>Endereço</h4>
                        <p><?php echo htmlspecialchars($establishment['address']); ?></p>
                    </div>
                </div>

                <?php if ($establishment['phone']): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <h4>Telefone</h4>
                            <p><?php echo htmlspecialchars($establishment['phone']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h4>Horário</h4>
                        <p><?php echo htmlspecialchars($establishment['opening_hours']); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="info-content">
                        <h4>Faixa de Preço</h4>
                        <p><?php echo str_repeat('$', $establishment['price_range']); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-circle <?php echo $establishment['is_open'] ? 'text-success' : 'text-danger'; ?>"></i>
                    </div>
                    <div class="info-content">
                        <h4>Status</h4>
                        <p><?php echo $establishment['is_open'] ? 'Aberto' : 'Fechado'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h2 class="card-title">
                <i class="fas fa-star"></i>
                Avaliações (<?php echo count($reviews); ?>)
            </h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="review-form">
                    <h3>Deixe sua avaliação</h3>
                    <form id="reviewForm">
                        <div class="rating-input">
                            <span class="rating-star" data-rating="1"><i class="fas fa-star"></i></span>
                            <span class="rating-star" data-rating="2"><i class="fas fa-star"></i></span>
                            <span class="rating-star" data-rating="3"><i class="fas fa-star"></i></span>
                            <span class="rating-star" data-rating="4"><i class="fas fa-star"></i></span>
                            <span class="rating-star" data-rating="5"><i class="fas fa-star"></i></span>
                        </div>
                        <div class="form-group">
                            <textarea id="reviewComment" placeholder="Conte sobre sua experiência..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Avaliação
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="review-form">
                    <p style="text-align: center; color: #666;">
                        <a href="auth/login.php" style="color: #ff6b6b;">Faça login</a> para deixar sua avaliação
                    </p>
                </div>
            <?php endif; ?>

            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Ainda não há avaliações para este estabelecimento.
                    </p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-avatar">
                                <?php if ($review['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($review['avatar']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="review-content">
                                <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <p class="review-date"><?php echo date('d/m/Y às H:i', strtotime($review['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Dados do estabelecimento
        const establishment = <?php echo json_encode($establishment); ?>;
        let selectedRating = 0;

        // Inicializar mapa se houver coordenadas
        if (establishment.latitude && establishment.longitude) {
            const map = L.map('map').setView([establishment.latitude, establishment.longitude], 16);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            L.marker([establishment.latitude, establishment.longitude])
                .addTo(map)
                .bindPopup(establishment.name);
        }

        // Sistema de avaliação por estrelas
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                updateStarDisplay();
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.rating);
                highlightStars(rating);
            });
        });

        document.querySelector('.rating-input').addEventListener('mouseleave', function() {
            updateStarDisplay();
        });

        function highlightStars(rating) {
            document.querySelectorAll('.rating-star').forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function updateStarDisplay() {
            highlightStars(selectedRating);
        }

        // Enviar avaliação
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedRating === 0) {
                alert('Por favor, selecione uma avaliação de 1 a 5 estrelas.');
                return;
            }
            
            const comment = document.getElementById('reviewComment').value.trim();
            
            fetch('api/add-review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    establishment_id: establishment.id,
                    rating: selectedRating,
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Avaliação enviada com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao enviar avaliação.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao enviar avaliação. Tente novamente.');
            });
        });

        // Favoritar estabelecimento
        function toggleFavorite(establishmentId) {
            fetch('api/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    establishment_id: establishmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao atualizar favoritos.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao atualizar favoritos. Tente novamente.');
            });
        }

        // Obter direções
        function getDirections() {
            if (establishment.latitude && establishment.longitude) {
                const url = `https://www.google.com/maps/dir/?api=1&destination=${establishment.latitude},${establishment.longitude}`;
                window.open(url, '_blank');
            } else {
                alert('Coordenadas não disponíveis para este estabelecimento.');
            }
        }

        // Compartilhar estabelecimento
        function shareEstablishment() {
            if (navigator.share) {
                navigator.share({
                    title: establishment.name,
                    text: establishment.description,
                    url: window.location.href
                });
            } else {
                // Fallback para navegadores que não suportam Web Share API
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link copiado para a área de transferência!');
                });
            }
        }
    </script>
</body>
</html>
