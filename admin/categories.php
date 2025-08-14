<?php
session_start();
require_once '../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'add':
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $icon = trim($_POST['icon']);
                    $color = trim($_POST['color']);
                    
                    if (!empty($name)) {
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon, color, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([$name, $description, $icon, $color]);
                        $success_message = "Categoria adicionada com sucesso!";
                    } else {
                        $error_message = "Nome da categoria é obrigatório!";
                    }
                    break;
                    
                case 'edit':
                    $id = (int)$_POST['id'];
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $icon = trim($_POST['icon']);
                    $color = trim($_POST['color']);
                    
                    if (!empty($name)) {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, icon = ?, color = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $description, $icon, $color, $id]);
                        $success_message = "Categoria atualizada com sucesso!";
                    } else {
                        $error_message = "Nome da categoria é obrigatório!";
                    }
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    // Verificar se há estabelecimentos usando esta categoria
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM establishments WHERE category = (SELECT name FROM categories WHERE id = ?)");
                    $stmt->execute([$id]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $error_message = "Não é possível excluir esta categoria pois há estabelecimentos associados a ela.";
                    } else {
                        $pdo->exec("DELETE FROM categories WHERE id = $id");
                        $success_message = "Categoria excluída com sucesso!";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $error_message = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

// Buscar categorias
$stmt = $pdo->query("SELECT c.*, 
                     COUNT(e.id) as establishment_count 
                     FROM categories c 
                     LEFT JOIN establishments e ON c.name = e.category 
                     GROUP BY c.id 
                     ORDER BY c.name");
$categories = $stmt->fetchAll();

// Estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
$total_categories = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT category) as used FROM establishments");
$used_categories = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - O Que Fazer em GV</title>
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

        .stats-cards {
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
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5em;
            color: white;
        }

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

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }

        .category-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.3em;
        }

        .category-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .category-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .establishment-count {
            color: #ff6b6b;
            font-weight: 600;
        }

        .category-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px 8px;
            border-radius: 6px;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
        }

        .btn-edit { background: #10b981; }
        .btn-delete { background: #ef4444; }

        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .color-picker {
            width: 60px !important;
            height: 40px !important;
            padding: 0 !important;
            border: none !important;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-save, .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-save {
            background: #10b981;
            color: white;
        }

        .btn-cancel {
            background: #6b7280;
            color: white;
        }

        .btn-save:hover, .btn-cancel:hover {
            opacity: 0.9;
            transform: translateY(-1px);
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
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
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
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i> Avaliações
                </a>
                <a href="categories.php" class="menu-item active">
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
                <h1><i class="fas fa-tags"></i> Gerenciar Categorias</h1>
                <button class="btn-add" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nova Categoria
                </button>
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

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Total de Categorias</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $used_categories; ?></div>
                    <div class="stat-label">Categorias em Uso</div>
                </div>
            </div>

            <?php if (count($categories) > 0): ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-icon" style="background-color: <?php echo $category['color'] ?: '#ff6b6b'; ?>">
                                    <i class="<?php echo $category['icon'] ?: 'fas fa-tag'; ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <p><?php echo $category['establishment_count']; ?> estabelecimentos</p>
                                </div>
                            </div>
                            
                            <?php if ($category['description']): ?>
                                <div class="category-description">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="category-stats">
                                <span class="establishment-count">
                                    <i class="fas fa-store"></i> <?php echo $category['establishment_count']; ?> estabelecimentos
                                </span>
                                <div class="category-actions">
                                    <button class="btn-edit" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($category['establishment_count'] == 0): ?>
                                        <button class="btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>Nenhuma categoria encontrada</h3>
                    <p>Comece criando sua primeira categoria de estabelecimentos.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal para Adicionar/Editar Categoria -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nova Categoria</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form id="categoryForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName">Nome da Categoria *</label>
                    <input type="text" id="categoryName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="categoryDescription">Descrição</label>
                    <textarea id="categoryDescription" name="description" placeholder="Descrição opcional da categoria"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="categoryIcon">Ícone (FontAwesome)</label>
                    <input type="text" id="categoryIcon" name="icon" placeholder="Ex: fas fa-utensils">
                </div>
                
                <div class="form-group">
                    <label for="categoryColor">Cor</label>
                    <input type="color" id="categoryColor" name="color" class="color-picker" value="#ff6b6b">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form para deletar categoria -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        function openModal(action) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('categoryForm');
            const actionInput = document.getElementById('formAction');
            
            if (action === 'add') {
                title.textContent = 'Nova Categoria';
                actionInput.value = 'add';
                form.reset();
                document.getElementById('categoryColor').value = '#ff6b6b';
            }
            
            modal.style.display = 'block';
        }

        function editCategory(category) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('modalTitle');
            const actionInput = document.getElementById('formAction');
            
            title.textContent = 'Editar Categoria';
            actionInput.value = 'edit';
            
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryIcon').value = category.icon || '';
            document.getElementById('categoryColor').value = category.color || '#ff6b6b';
            
            modal.style.display = 'block';
        }

        function deleteCategory(id) {
            if (confirm('Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
