<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get statistics
    $stats = [];
    
    // Total establishments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM establishments WHERE status = 'active'");
    $stats['establishments'] = $stmt->fetch()['total'];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stats['users'] = $stmt->fetch()['total'];
    
    // Total reviews
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews WHERE status = 'approved'");
    $stats['reviews'] = $stmt->fetch()['total'];
    
    // Average rating
    $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE status = 'approved'");
    $stats['avg_rating'] = round($stmt->fetch()['avg_rating'], 1);
    
    // Monthly growth data
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM establishments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $monthlyGrowth = $stmt->fetchAll();
    
    // Top establishments by rating
    $stmt = $pdo->query("
        SELECT 
            e.name,
            e.category,
            AVG(r.rating) as avg_rating,
            COUNT(r.id) as review_count
        FROM establishments e
        LEFT JOIN reviews r ON e.id = r.establishment_id AND r.status = 'approved'
        WHERE e.status = 'active'
        GROUP BY e.id
        HAVING review_count > 0
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT 10
    ");
    $topEstablishments = $stmt->fetchAll();
    
    // Most active users
    $stmt = $pdo->query("
        SELECT 
            u.name,
            u.email,
            COUNT(r.id) as review_count,
            AVG(r.rating) as avg_rating
        FROM users u
        LEFT JOIN reviews r ON u.id = r.user_id AND r.status = 'approved'
        WHERE u.status = 'active'
        GROUP BY u.id
        HAVING review_count > 0
        ORDER BY review_count DESC
        LIMIT 10
    ");
    $activeUsers = $stmt->fetchAll();
    
    // Category distribution
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as count
        FROM establishments 
        WHERE status = 'active'
        GROUP BY category
        ORDER BY count DESC
    ");
    $categoryDistribution = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Erro na conexão: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--space-6);
            background: var(--gray-50);
            min-height: 100vh;
        }

        .admin-header {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            margin-bottom: var(--space-8);
            box-shadow: var(--shadow-lg);
        }

        .admin-header h1 {
            font-size: var(--text-3xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .admin-header p {
            color: var(--text-muted);
            font-size: var(--text-lg);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-4);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-full);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-white);
            font-size: var(--text-xl);
        }

        .stat-value {
            font-size: var(--text-4xl);
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: var(--space-1);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: var(--text-sm);
            font-weight: 500;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--space-8);
            margin-bottom: var(--space-8);
        }

        .chart-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-lg);
        }

        .chart-card h3 {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-8);
        }

        .table-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-lg);
        }

        .table-card h3 {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: var(--space-3) var(--space-4);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .data-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: var(--text-sm);
        }

        .data-table td {
            color: var(--text-primary);
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .rating-stars {
            color: var(--accent-color);
        }

        .category-badge {
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-restaurante {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .category-bar {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        .category-cafe {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .category-cultura {
            background: rgba(139, 92, 246, 0.1);
            color: #7c3aed;
        }

        .export-buttons {
            display: flex;
            gap: var(--space-3);
            margin-bottom: var(--space-8);
        }

        .export-btn {
            padding: var(--space-3) var(--space-4);
            border: none;
            border-radius: var(--radius-lg);
            background: var(--gradient-primary);
            color: var(--text-white);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-4);
            background: var(--gray-100);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 500;
            transition: all var(--transition-fast);
            margin-bottom: var(--space-6);
        }

        .back-btn:hover {
            background: var(--gray-200);
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: var(--space-4);
            }

            .charts-grid,
            .tables-grid {
                grid-template-columns: 1fr;
            }

            .export-buttons {
                flex-direction: column;
            }

            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Dashboard
        </a>

        <div class="admin-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Relatórios e Analytics
            </h1>
            <p>Análise detalhada do desempenho da plataforma</p>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="export-btn" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i>
                Exportar PDF
            </button>
            <button class="export-btn" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i>
                Exportar Excel
            </button>
            <button class="export-btn" onclick="exportToCSV()">
                <i class="fas fa-file-csv"></i>
                Exportar CSV
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['establishments']); ?></div>
                <div class="stat-label">Estabelecimentos Ativos</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-label">Usuários Cadastrados</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['reviews']); ?></div>
                <div class="stat-label">Avaliações Aprovadas</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['avg_rating']; ?></div>
                <div class="stat-label">Avaliação Média</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    Crescimento Mensal de Estabelecimentos
                </h3>
                <div class="chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    Distribuição por Categoria
                </h3>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="tables-grid">
            <div class="table-card">
                <h3>
                    <i class="fas fa-trophy"></i>
                    Top 10 Estabelecimentos
                </h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Avaliação</th>
                            <th>Reviews</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topEstablishments as $establishment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($establishment['name']); ?></td>
                            <td>
                                <span class="category-badge category-<?php echo $establishment['category']; ?>">
                                    <?php echo ucfirst($establishment['category']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="rating-stars">
                                    <?php echo str_repeat('★', round($establishment['avg_rating'])); ?>
                                </span>
                                <?php echo number_format($establishment['avg_rating'], 1); ?>
                            </td>
                            <td><?php echo $establishment['review_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <h3>
                    <i class="fas fa-user-star"></i>
                    Usuários Mais Ativos
                </h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Reviews</th>
                            <th>Média</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeUsers as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo $user['review_count']; ?></td>
                            <td>
                                <span class="rating-stars">
                                    <?php echo str_repeat('★', round($user['avg_rating'])); ?>
                                </span>
                                <?php echo number_format($user['avg_rating'], 1); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Growth Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const growthChart = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyGrowth, 'month')); ?>,
                datasets: [{
                    label: 'Estabelecimentos Cadastrados',
                    data: <?php echo json_encode(array_column($monthlyGrowth, 'count')); ?>,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($categoryDistribution, 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($categoryDistribution, 'count')); ?>,
                    backgroundColor: [
                        '#ff6b6b',
                        '#4ecdc4',
                        '#ffa500',
                        '#8b5cf6',
                        '#10b981'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Export functions
        function exportToPDF() {
            alert('Funcionalidade de exportação PDF será implementada em breve!');
        }

        function exportToExcel() {
            alert('Funcionalidade de exportação Excel será implementada em breve!');
        }

        function exportToCSV() {
            alert('Funcionalidade de exportação CSV será implementada em breve!');
        }
    </script>
</body>
</html>
