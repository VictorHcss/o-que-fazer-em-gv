<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_POST) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Buscar senha atual do usuário
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Validações
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Todos os campos são obrigatórios.';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Senha atual incorreta.';
    } elseif (strlen($new_password) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'A confirmação da nova senha não confere.';
    } else {
        // Atualizar senha
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $message = 'Senha alterada com sucesso!';
        } else {
            $error = 'Erro ao alterar senha.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - O Que Fazer em GV</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .password-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
        }

        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .password-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .password-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 1.1em;
        }

        .form-group input {
            width: 100%;
            padding: 15px 50px 15px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 1.2em;
        }

        .password-toggle:hover {
            color: #ff6b6b;
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

        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .password-requirements h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .password-requirements li {
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-requirements li i {
            color: #28a745;
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
            .password-container {
                padding: 10px;
            }

            .password-card {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-card">
            <div class="password-header">
                <h1><i class="fas fa-lock"></i> Alterar Senha</h1>
                <p>Mantenha sua conta segura com uma senha forte</p>
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

            <div class="password-requirements">
                <h4><i class="fas fa-shield-alt"></i> Requisitos da Senha</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Mínimo de 6 caracteres</li>
                    <li><i class="fas fa-check"></i> Combine letras e números</li>
                    <li><i class="fas fa-check"></i> Use caracteres especiais para maior segurança</li>
                    <li><i class="fas fa-check"></i> Evite informações pessoais óbvias</li>
                </ul>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-key"></i> Senha Atual *
                    </label>
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i> Nova Senha *
                    </label>
                    <input type="password" id="new_password" name="new_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmar Nova Senha *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Alterar Senha
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
