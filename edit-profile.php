<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Buscar dados atuais do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    
    // Validações
    if (empty($name) || empty($email)) {
        $error = 'Nome e email são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        // Verificar se email já existe (exceto o atual)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Este email já está sendo usado por outro usuário.';
        } else {
            // Atualizar dados
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $email, $phone, $bio, $user_id])) {
                $message = 'Perfil atualizado com sucesso!';
                // Atualizar dados na sessão
                $_SESSION['user_name'] = $name;
                // Recarregar dados do usuário
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Erro ao atualizar perfil.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .edit-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .edit-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .edit-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .edit-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 1.1em;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .current-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            color: white;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .current-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
            .edit-container {
                padding: 10px;
            }

            .edit-card {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-card">
            <div class="edit-header">
                <h1><i class="fas fa-user-edit"></i> Editar Perfil</h1>
                <p>Atualize suas informações pessoais</p>
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

            <div class="avatar-section">
                <div class="current-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <p style="color: #666;">Avatar atual</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Nome Completo *
                    </label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Telefone
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           placeholder="(33) 99999-9999">
                </div>

                <div class="form-group">
                    <label for="bio">
                        <i class="fas fa-quote-left"></i> Biografia
                    </label>
                    <textarea id="bio" name="bio" 
                              placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
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
