<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: auth/login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $error = 'Erro ao carregar dados do usuário.';
}

// Get user statistics
$stats = [
    'reviews' => 0,
    'favorites' => 0,
    'avg_rating' => 0
];

try {
    // Count reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['reviews'] = $stmt->fetchColumn();
    
    // Count favorites
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['favorites'] = $stmt->fetchColumn();
    
    // Average rating given
    $stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['avg_rating'] = round($stmt->fetchColumn() ?: 0, 1);
} catch (Exception $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 120px 20px 40px;
            background: var(--gray-50);
            min-height: 100vh;
        }
        
        .profile-header {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: var(--text-white);
            font-weight: bold;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .profile-email {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-fast);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: var(--text-white);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .section-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .quick-action {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            text-decoration: none;
            color: var(--text-secondary);
            transition: all var(--transition-fast);
            margin-bottom: 0.5rem;
        }
        
        .quick-action:hover {
            background: var(--gray-50);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            background: var(--gray-100);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }
        
        .quick-action:hover .action-icon {
            background: var(--primary-color);
            color: var(--text-white);
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 100px 10px 20px;
            }
            
            .profile-sections {
                grid-template-columns: 1fr;
            }
            
            .profile-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="logo-text">
                        <h1><a href="index.html" style="text-decoration: none; color: inherit;">O Que Fazer em GV</a></h1>
                        <span>Descubra Governador Valadares</span>
                    </div>
                </div>
                
                <div class="header-actions">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="user-dropdown">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                                <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                            <a href="my-favorites.php"><i class="fas fa-heart"></i> Favoritos</a>
                            <a href="my-reviews.php"><i class="fas fa-star"></i> Avaliações</a>
                            <div class="dropdown-divider"></div>
                            <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <div class="profile-actions">
                <a href="edit-profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    Editar Perfil
                </a>
                <a href="change-password.php" class="btn btn-secondary">
                    <i class="fas fa-lock"></i>
                    Alterar Senha
                </a>
                <a href="privacy-settings.php" class="btn btn-secondary">
                    <i class="fas fa-shield-alt"></i>
                    Privacidade
                </a>
            </div>
        </div>

        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?php echo $stats['reviews']; ?></div>
                <div class="stat-label">Avaliações</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-number"><?php echo $stats['favorites']; ?></div>
                <div class="stat-label">Favoritos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo $stats['avg_rating']; ?></div>
                <div class="stat-label">Média de Avaliação</div>
            </div>
        </div>

        <div class="profile-sections">
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Minhas Atividades
                </h2>
                
                <a href="my-reviews.php" class="quick-action">
                    <div class="action-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <strong>Minhas Avaliações</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Veja e gerencie suas avaliações
                        </p>
                    </div>
                </a>
                
                <a href="my-favorites.php" class="quick-action">
                    <div class="action-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div>
                        <strong>Meus Favoritos</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Estabelecimentos que você favoritou
                        </p>
                    </div>
                </a>
            </div>

            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-cog"></i>
                    Configurações
                </h2>
                
                <a href="edit-profile.php" class="quick-action">
                    <div class="action-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <strong>Editar Perfil</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Atualize suas informações pessoais
                        </p>
                    </div>
                </a>
                
                <a href="change-password.php" class="quick-action">
                    <div class="action-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <strong>Alterar Senha</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Mantenha sua conta segura
                        </p>
                    </div>
                </a>
                
                <a href="privacy-settings.php" class="quick-action">
                    <div class="action-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <strong>Privacidade</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Gerencie suas configurações de privacidade
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
