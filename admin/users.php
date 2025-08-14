<?php
session_start();
require_once '../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Processar exclusão se solicitado
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Primeiro excluir registros relacionados (avaliações, favoritos)
        $pdo->exec("DELETE FROM reviews WHERE user_id = $id");
        $pdo->exec("DELETE FROM favorites WHERE user_id = $id");
        // Depois excluir o usuário
        $pdo->exec("DELETE FROM users WHERE id = $id");
        $success_message = "Usuário excluído com sucesso!";
    } catch (PDOException $e) {
        $error_message = "Erro ao excluir: " . $e->getMessage();
    }
}

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Busca
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if ($search) {
    $search_condition = "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%'";
}

// Contagem total para paginação
$stmt = $pdo->query("SELECT COUNT(*) FROM users $search_condition");
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Buscar usuários
$stmt = $pdo->query("SELECT * FROM users $search_condition ORDER BY id DESC LIMIT $offset, $per_page");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - O Que Fazer em GV</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 2em;
        }

        .btn-add {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .search-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .search-form {
            display: flex;
            gap: 15px;
        }

        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        .search-input:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        .btn-search {
            background: #333;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-search:hover {
            background: #555;
        }

        .users-table {
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-active {
            background: #e3fcef;
            color: #0d9488;
        }

        .status-pending {
            background: #fef9c3;
            color: #ca8a04;
        }

        .status-inactive {
            background: #fecaca;
            color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-view, .btn-edit, .btn-delete {
            padding: 8px;
            border-radius: 6px;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-view {
            background: #3b82f6;
        }

        .btn-edit {
            background: #10b981;
        }

        .btn-delete {
            background: #ef4444;
        }

        .btn-view:hover, .btn-edit:hover, .btn-delete:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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
            background: #e3fcef;
            color: #0d9488;
            border: 1px solid #ccfbf1;
        }

        .alert-error {
            background: #fecaca;
            color: #dc2626;
            border: 1px solid #fee2e2;
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
            margin-right: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
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

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .search-form {
                flex-direction: column;
            }

            .users-table {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
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
                <a href="users.php" class="menu-item active">
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
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                <a href="add-user.php" class="btn-add">
                    <i class="fas fa-user-plus"></i> Adicionar Usuário
                </a>
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

            <div class="search-bar">
                <form action="" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Buscar usuários..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="users-table">
                <?php if (count($users) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Data de Registro</th>
                                <th>Status</th>
                                <th>Avaliações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'] ?? $user['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="status-badge status-active">Ativo</span>
                                        <?php elseif ($user['status'] == 'pending'): ?>
                                            <span class="status-badge status-pending">Pendente</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            // Contar avaliações do usuário
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
                                            $stmt->execute([$user['id']]);
                                            echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="view-user.php?id=<?php echo $user['id']; ?>" class="btn-view" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Não há usuários cadastrados ou correspondentes à sua busca.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
