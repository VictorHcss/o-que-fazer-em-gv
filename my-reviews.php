<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Buscar avaliações do usuário
$stmt = $pdo->prepare("
    SELECT r.*, e.name as establishment_name, e.image_url, e.category
    FROM reviews r
    JOIN establishments e ON r.establishment_id = e.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

// Estatísticas das avaliações
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        MAX(created_at) as last_review
    FROM reviews 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Avaliações - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reviews-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .reviews-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-info h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .header-info p {
            color: #666;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 1em;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-2px);
        }

        .review-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .review-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
        }

        .review-info h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.4em;
        }

        .review-category {
            color: #ff6b6b;
            font-size: 0.9em;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .review-stars {
            color: #ffa500;
            font-size: 1.2em;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-content {
            margin-bottom: 20px;
        }

        .review-text {
            color: #333;
            line-height: 1.6;
            font-size: 1.1em;
            margin-bottom: 15px;
        }

        .review-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .review-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .review-buttons {
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

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #ddd;
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
            .reviews-container {
                padding: 10px;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .review-header {
                flex-direction: column;
                text-align: center;
            }

            .review-actions {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="reviews-container">
        <div class="reviews-header">
            <div class="header-content">
                <div class="header-info">
                    <h1><i class="fas fa-star"></i> Minhas Avaliações</h1>
                    <p>Todas as suas avaliações e comentários sobre estabelecimentos</p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                    <div class="stat-label">Total de Avaliações</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                    <div class="stat-label">Média das Suas Notas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php echo $stats['last_review'] ? date('d/m', strtotime($stats['last_review'])) : 'N/A'; ?>
                    </div>
                    <div class="stat-label">Última Avaliação</div>
                </div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <h3>Nenhuma avaliação ainda</h3>
                <p>Você ainda não fez nenhuma avaliação.<br>
                   Visite alguns estabelecimentos e compartilhe sua experiência!</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Explorar Estabelecimentos
                </a>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img src="<?php echo $review['image_url'] ?: '/placeholder.svg?height=80&width=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($review['establishment_name']); ?>" 
                                 class="review-image">
                            <div class="review-info">
                                <h3><?php echo htmlspecialchars($review['establishment_name']); ?></h3>
                                <div class="review-category"><?php echo htmlspecialchars($review['category']); ?></div>
                                <div class="review-rating">
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="review-date">
                                        <?php echo date('d/m/Y às H:i', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-content">
                            <div class="review-text">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </div>
                        </div>
                        
                        <div class="review-actions">
                            <div class="review-meta">
                                <span><i class="fas fa-thumbs-up"></i> Útil para outros usuários</span>
                            </div>
                            <div class="review-buttons">
                                <a href="establishment.php?id=<?php echo $review['establishment_id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i>
                                    Ver Estabelecimento
                                </a>
                                <button onclick="editReview(<?php echo $review['id']; ?>)" class="btn btn-primary">
                                    <i class="fas fa-edit"></i>
                                    Editar
                                </button>
                                <button onclick="deleteReview(<?php echo $review['id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                    Excluir
                                </button>
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
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Página Inicial
            </a>
        </div>
    </div>

    <script>
        function editReview(reviewId) {
            // Implementar modal de edição ou redirecionar para página de edição
            window.location.href = `edit-review.php?id=${reviewId}`;
        }

        function deleteReview(reviewId) {
            if (confirm('Tem certeza que deseja excluir esta avaliação?')) {
                fetch('api/delete-review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: reviewId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir avaliação. Tente novamente.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao excluir avaliação. Tente novamente.');
                });
            }
        }
    </script>
</body>
</html>
