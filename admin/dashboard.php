<?php
session_start();
require_once '../config/database.php';

// Verificar se é admin (simulado)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Estatísticas do dashboard
$stats = [];

// Total de estabelecimentos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM establishments");
$stats['establishments'] = $stmt->fetch()['total'];

// Total de usuários
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Total de avaliações
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
$stats['reviews'] = $stmt->fetch()['total'];

// Avaliação média geral
$stmt = $pdo->query("SELECT AVG(rating) as avg FROM reviews");
$stats['avg_rating'] = number_format($stmt->fetch()['avg'] ?? 0, 1);

// Estabelecimentos por categoria
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM establishments GROUP BY category ORDER BY count DESC");
$categories = $stmt->fetchAll();

// Últimas avaliações
$stmt = $pdo->query("
    SELECT r.*, u.name as user_name, e.name as establishment_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN establishments e ON r.establishment_id = e.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$recent_reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5em;
            color: #ff6b6b;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ff6b6b;
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .dashboard-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2.5em;
        }

        .dashboard-header p {
            color: #666;
            margin: 0;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8em;
            color: white;
        }

        .stat-icon.establishments {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
        }

        .stat-icon.reviews {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-icon.rating {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .chart-card,
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #ff6b6b;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .activity-content h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1em;
        }

        .activity-content p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .activity-rating {
            color: #ffa500;
            margin-left: auto;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cog"></i> Admin Panel</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="establishments.php" class="menu-item">
                    <i class="fas fa-store"></i> Estabelecimentos
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Usuários
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i> Avaliações
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-tags"></i> Categorias
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i> Configurações
                </a>
                <a href="../index.html" class="menu-item">
                    <i class="fas fa-home"></i> Ver Site
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Visão geral da plataforma O Que Fazer em GV</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon establishments">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['establishments']; ?></div>
                    <div class="stat-label">Estabelecimentos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">Usuários</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon reviews">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['reviews']; ?></div>
                    <div class="stat-label">Avaliações</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon rating">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['avg_rating']; ?></div>
                    <div class="stat-label">Média Geral</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="chart-card">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Estabelecimentos por Categoria
                    </h3>
                    <canvas id="categoryChart"></canvas>
                </div>

                <div class="activity-card">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i>
                        Atividade Recente
                    </h3>
                    <div class="activity-list">
                        <?php foreach ($recent_reviews as $review): ?>
                            <div class="activity-item">
                                <div class="activity-avatar">
                                    <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                </div>
                                <div class="activity-content">
                                    <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                    <p>Avaliou <?php echo htmlspecialchars($review['establishment_name']); ?></p>
                                </div>
                                <div class="activity-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Gráfico de categorias
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($categories); ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.category),
                datasets: [{
                    data: categoryData.map(item => item.count),
                    backgroundColor: [
                        '#ff6b6b',
                        '#4ecdc4',
                        '#45b7d1',
                        '#96ceb4',
                        '#ffeaa7',
                        '#dda0dd',
                        '#98d8c8'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
