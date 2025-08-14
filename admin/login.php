<?php
session_start();

$error = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Credenciais admin simples (em produção, usar banco de dados)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciais inválidas.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2em;
            color: white;
        }

        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .login-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
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

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #ff6b6b;
        }

        .credentials-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Admin Login</h1>
                <p>Acesso ao painel administrativo</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="credentials-info">
                <strong>Credenciais de demonstração:</strong><br>
                Usuário: admin<br>
                Senha: admin123
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <a href="../index.html" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao site
            </a>
        </div>
    </div>
</body>
</html>
