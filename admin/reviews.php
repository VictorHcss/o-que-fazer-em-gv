<?php
session_start();
require_once '../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Processar ações
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    try {
        switch ($action) {
            case 'approve':
                $pdo->exec("UPDATE reviews SET status = 'approved' WHERE id = $id");
                $success_message = "Avaliação aprovada com sucesso!";
                break;
            case 'reject':
                $pdo->exec("UPDATE reviews SET status = 'rejected' WHERE id = $id");
                $success_message = "Avaliação rejeitada com sucesso!";
                break;
            case 'delete':
                $pdo->exec("DELETE FROM reviews WHERE id = $id");
                $success_message = "Avaliação excluída com sucesso!";
                break;
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where_conditions = [];
if ($status_filter) {
    $where_conditions[] = "r.status = '$status_filter'";
}
if ($rating_filter) {
    $where_conditions[] = "r.rating = $rating_filter";
}
if ($search) {
    $where_conditions[] = "(u.name LIKE '%$search%' OR e.name LIKE '%$search%' OR r.comment LIKE '%$search%')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contagem total
$stmt = $pdo->query("SELECT COUNT(*) FROM reviews r 
                     JOIN users u ON r.user_id = u.id 
                     JOIN establishments e ON r.establishment_id = e.id 
                     $where_clause");
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

// Buscar avaliações
$stmt = $pdo->query("SELECT r.*, u.name as user_name, e.name as establishment_name 
                     FROM reviews r 
                     JOIN users u ON r.user_id = u.id 
                     JOIN establishments e ON r.establishment_id = e.id 
                     $where_clause 
                     ORDER BY r.created_at DESC 
                     LIMIT $offset, $per_page");
$reviews = $stmt->fetchAll();

// Estatísticas
$stats = [];
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM reviews");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Avaliações - O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 2em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5em;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.pending { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.approved { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.rejected { background: linear-gradient(135deg, #43e97b, #38f9d7); }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .filters-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        .btn-filter {
            background: #333;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            height: 42px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            background: #555;
        }

        .reviews-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .review-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1em;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background: #d1fae5;
            color: #059669;
        }

        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-approve, .btn-reject, .btn-delete, .btn-view {
            padding: 6px 8px;
            border-radius: 6px;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
        }

        .btn-approve { background: #10b981; }
        .btn-reject { background: #f59e0b; }
        .btn-delete { background: #ef4444; }
        .btn-view { background: #3b82f6; }

        .btn-approve:hover, .btn-reject:hover, .btn-delete:hover, .btn-view:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #f8f9fa;
        }

        .page-link.active {
            background: #ff6b6b;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .filters-form {
                grid-template-columns: 1fr;
            }

            .reviews-table {
                overflow-x: auto;
            }

            table {
                min-width: 900px;
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
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="establishments.php" class="menu-item">
                    <i class="fas fa-store"></i> Estabelecimentos
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Usuários
                </a>
                <a href="reviews.php" class="menu-item active">
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
            <div class="page-header">
                <h1><i class="fas fa-star"></i> Gerenciar Avaliações</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total de Avaliações</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon approved">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Aprovadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejeitadas</div>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="filters-bar">
                <form action="" method="GET" class="filters-form">
                    <div class="filter-group">
                        <label>Buscar</label>
                        <input type="text" name="search" placeholder="Buscar por usuário, estabelecimento ou comentário..." class="filter-input" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="filter-select">
                            <option value="">Todos</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Avaliação</label>
                        <select name="rating" class="filter-select">
                            <option value="">Todas</option>
                            <option value="5" <?php echo $rating_filter == '5' ? 'selected' : ''; ?>>5 estrelas</option>
                            <option value="4" <?php echo $rating_filter == '4' ? 'selected' : ''; ?>>4 estrelas</option>
                            <option value="3" <?php echo $rating_filter == '3' ? 'selected' : ''; ?>>3 estrelas</option>
                            <option value="2" <?php echo $rating_filter == '2' ? 'selected' : ''; ?>>2 estrelas</option>
                            <option value="1" <?php echo $rating_filter == '1' ? 'selected' : ''; ?>>1 estrela</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            <div class="reviews-table">
                <?php if (count($reviews) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Estabelecimento</th>
                                <th>Avaliação</th>
                                <th>Comentário</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?php echo $review['id']; ?></td>
                                    <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($review['establishment_name']); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-content" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                            <?php echo htmlspecialchars($review['comment']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $review['status']; ?>">
                                            <?php 
                                            switch($review['status']) {
                                                case 'pending': echo 'Pendente'; break;
                                                case 'approved': echo 'Aprovado'; break;
                                                case 'rejected': echo 'Rejeitado'; break;
                                                default: echo 'Pendente';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <?php if ($review['status'] != 'approved'): ?>
                                            <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn-approve" title="Aprovar">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($review['status'] != 'rejected'): ?>
                                            <a href="?action=reject&id=<?php echo $review['id']; ?>" class="btn-reject" title="Rejeitar">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="view-review.php?id=<?php echo $review['id']; ?>" class="btn-view" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $review['id']; ?>" class="btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-star-half-alt"></i>
                        <h3>Nenhuma avaliação encontrada</h3>
                        <p>Não há avaliações correspondentes aos filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>" class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
