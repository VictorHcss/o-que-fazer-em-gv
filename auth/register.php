<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../profile.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                // Create user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                $success = 'Conta criada com sucesso! Você pode fazer login agora.';
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-hero);
            padding: 2rem;
        }
        
        .auth-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: 3rem;
            box-shadow: var(--shadow-2xl);
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--white);
            font-size: 1.5rem;
        }
        
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: var(--transition-normal);
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c53030;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid #fed7d7;
        }
        
        .success-message {
            background: #f0fff4;
            color: #22543d;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid #9ae6b4;
        }
        
        .auth-button {
            width: 100%;
            background: var(--gradient-primary);
            color: var(--white);
            border: none;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            margin-bottom: 1rem;
        }
        
        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--gray-500);
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-300);
        }
        
        .divider span {
            background: var(--white);
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="auth-title">Crie sua conta</h1>
                <p class="auth-subtitle">Junte-se à nossa comunidade</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">Nome completo</label>
                    <input type="text" id="name" name="name" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           placeholder="Seu nome completo" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Mínimo 6 caracteres" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Digite a senha novamente" required>
                </div>
                
                <button type="submit" class="auth-button">
                    <i class="fas fa-user-plus"></i>
                    Criar conta
                </button>
            </form>
            
            <div class="divider">
                <span>ou</span>
            </div>
            
            <div class="auth-links">
                <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
                <p><a href="../index.html">← Voltar ao início</a></p>
            </div>
        </div>
    </div>
</body>
</html>
