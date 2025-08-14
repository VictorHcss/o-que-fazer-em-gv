<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Buscar favoritos do usuário
$stmt = $pdo->prepare("
    SELECT e.*, uf.created_at as favorited_at,
           AVG(r.rating) as avg_rating,
           COUNT(r.id) as total_reviews
    FROM user_favorites uf
    JOIN establishments e ON uf.establishment_id = e.id
    LEFT JOIN reviews r ON e.id = r.establishment_id
    WHERE uf.user_id = ?
    GROUP BY e.id, uf.created_at
    ORDER BY uf.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Favoritos - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .favorites-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .favorites-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .favorites-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .favorites-header p {
            color: #666;
            font-size: 1.1em;
        }

        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .favorite-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .favorite-card:hover {
            transform: translateY(-5px);
        }

        .favorite-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            position: relative;
        }

        .favorite-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 107, 107, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .favorite-content {
            padding: 25px;
        }

        .favorite-title {
            font-size: 1.4em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .favorite-category {
            color: #ff6b6b;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .favorite-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .favorite-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .favorite-rating {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .favorite-rating .stars {
            color: #ffa500;
        }

        .favorite-rating .rating-text {
            color: #666;
            font-size: 0.9em;
        }

        .favorite-price {
            color: #28a745;
            font-weight: 600;
        }

        .favorite-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
        }

        .favorite-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .favorite-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .back-button {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .favorites-container {
                padding: 10px;
            }

            .favorites-grid {
                grid-template-columns: 1fr;
            }

            .favorite-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="favorites-container">
        <div class="favorites-header">
            <h1><i class="fas fa-heart"></i> Meus Favoritos</h1>
            <p>Seus estabelecimentos favoritos salvos para fácil acesso</p>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>Nenhum favorito ainda</h3>
                <p>Você ainda não salvou nenhum estabelecimento como favorito.<br>
                   Explore nossa plataforma e salve os lugares que mais gosta!</p>
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Explorar Estabelecimentos
                </a>
            </div>
        <?php else: ?>
            <div class="favorites-grid">
                <?php foreach ($favorites as $favorite): ?>
                    <div class="favorite-card">
                        <div style="position: relative;">
                            <img src="<?php echo $favorite['image_url'] ?: '/placeholder.svg?height=200&width=350'; ?>" 
                                 alt="<?php echo htmlspecialchars($favorite['name']); ?>" 
                                 class="favorite-image">
                            <div class="favorite-badge">
                                <i class="fas fa-heart"></i> Favorito
                            </div>
                        </div>
                        
                        <div class="favorite-content">
                            <div class="favorite-category"><?php echo htmlspecialchars($favorite['category']); ?></div>
                            <h3 class="favorite-title"><?php echo htmlspecialchars($favorite['name']); ?></h3>
                            <p class="favorite-description">
                                <?php echo htmlspecialchars(substr($favorite['description'], 0, 120)); ?>...
                            </p>
                            
                            <div class="favorite-info">
                                <div class="favorite-rating">
                                    <div class="stars">
                                        <?php 
                                        $rating = round($favorite['avg_rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star<?php echo $i <= $rating ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text">
                                        (<?php echo $favorite['total_reviews']; ?> avaliações)
                                    </span>
                                </div>
                                <div class="favorite-price">
                                    <?php echo str_repeat('$', $favorite['price_range']); ?>
                                </div>
                            </div>
                            
                            <div class="favorite-meta">
                                <div class="favorite-date">
                                    <i class="fas fa-calendar"></i>
                                    Salvo em <?php echo date('d/m/Y', strtotime($favorite['favorited_at'])); ?>
                                </div>
                                <div class="favorite-actions">
                                    <a href="establishment.php?id=<?php echo $favorite['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        Ver
                                    </a>
                                    <button onclick="removeFavorite(<?php echo $favorite['id']; ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="back-button">
            <a href="profile.php" class="btn btn-primary">
                <i class="fas fa-user"></i>
                Voltar ao Perfil
            </a>
            <a href="index.html" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Página Inicial
            </a>
        </div>
    </div>

    <script>
        function removeFavorite(establishmentId) {
            if (confirm('Tem certeza que deseja remover este estabelecimento dos seus favoritos?')) {
                fetch('api/remove-favorite.php', {
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
                        alert('Erro ao remover favorito. Tente novamente.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao remover favorito. Tente novamente.');
                });
            }
        }
    </script>
</body>
</html>
