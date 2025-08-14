<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Buscar configurações atuais
$stmt = $pdo->prepare("SELECT * FROM user_privacy_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

// Se não existir, criar com valores padrão
if (!$settings) {
    $stmt = $pdo->prepare("INSERT INTO user_privacy_settings (user_id, profile_public, show_reviews, show_favorites, email_notifications) VALUES (?, 1, 1, 1, 1)");
    $stmt->execute([$user_id]);
    $stmt = $pdo->prepare("SELECT * FROM user_privacy_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
}

if ($_POST) {
    $profile_public = isset($_POST['profile_public']) ? 1 : 0;
    $show_reviews = isset($_POST['show_reviews']) ? 1 : 0;
    $show_favorites = isset($_POST['show_favorites']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE user_privacy_settings SET profile_public = ?, show_reviews = ?, show_favorites = ?, email_notifications = ?, updated_at = NOW() WHERE user_id = ?");
        if ($stmt->execute([$profile_public, $show_reviews, $show_favorites, $email_notifications, $user_id])) {
            $message = 'Configurações de privacidade atualizadas com sucesso!';
            // Recarregar configurações
            $stmt = $pdo->prepare("SELECT * FROM user_privacy_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
        } else {
            $error = 'Erro ao atualizar configurações.';
        }
    } catch (Exception $e) {
        $error = 'Erro interno do servidor.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Privacidade - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .privacy-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .privacy-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .privacy-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .privacy-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .privacy-header p {
            color: #666;
            font-size: 1.1em;
        }

        .privacy-section {
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .privacy-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .privacy-section h3 i {
            color: #ff6b6b;
        }

        .privacy-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .privacy-option:last-child {
            border-bottom: none;
        }

        .option-info h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1em;
        }

        .option-info p {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn {
            padding: 15px 30px;
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
            margin: 5px;
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

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .privacy-container {
                padding: 10px;
            }

            .privacy-card {
                padding: 20px;
            }

            .privacy-option {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="privacy-container">
        <div class="privacy-card">
            <div class="privacy-header">
                <h1><i class="fas fa-shield-alt"></i> Configurações de Privacidade</h1>
                <p>Controle como suas informações são compartilhadas</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="privacy-section">
                    <h3><i class="fas fa-user"></i> Perfil Público</h3>
                    <div class="privacy-option">
                        <div class="option-info">
                            <h4>Perfil Visível</h4>
                            <p>Permite que outros usuários vejam seu perfil público com suas estatísticas básicas</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="profile_public" <?php echo $settings['profile_public'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="privacy-section">
                    <h3><i class="fas fa-star"></i> Avaliações</h3>
                    <div class="privacy-option">
                        <div class="option-info">
                            <h4>Mostrar Minhas Avaliações</h4>
                            <p>Permite que suas avaliações sejam visíveis publicamente nos estabelecimentos</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_reviews" <?php echo $settings['show_reviews'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="privacy-section">
                    <h3><i class="fas fa-heart"></i> Favoritos</h3>
                    <div class="privacy-option">
                        <div class="option-info">
                            <h4>Mostrar Meus Favoritos</h4>
                            <p>Permite que outros usuários vejam sua lista de estabelecimentos favoritos</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_favorites" <?php echo $settings['show_favorites'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="privacy-section">
                    <h3><i class="fas fa-envelope"></i> Notificações</h3>
                    <div class="privacy-option">
                        <div class="option-info">
                            <h4>Notificações por Email</h4>
                            <p>Receba emails sobre novos estabelecimentos, promoções e atualizações da plataforma</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Configurações
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
