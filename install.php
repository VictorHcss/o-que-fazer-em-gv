<?php
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_POST) {
    if ($step == 1) {
        // Verificar conexão com banco
        $host = $_POST['host'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $database = $_POST['database'];
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Salvar configurações
            $config = "<?php\n";
            $config .= "define('DB_HOST', '$host');\n";
            $config .= "define('DB_USERNAME', '$username');\n";
            $config .= "define('DB_PASSWORD', '$password');\n";
            $config .= "define('DB_DATABASE', '$database');\n";
            $config .= "?>";
            
            file_put_contents('config/db_config.php', $config);
            
            header('Location: install.php?step=2');
            exit;
        } catch (Exception $e) {
            $error = 'Erro de conexão: ' . $e->getMessage();
        }
    } elseif ($step == 2) {
        // Executar SQL
        require_once 'config/db_config.php';
        
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = file_get_contents('scripts/complete-database-setup.sql');
            $pdo->exec($sql);
            
            header('Location: install.php?step=3');
            exit;
        } catch (Exception $e) {
            $error = 'Erro ao criar tabelas: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - O Que Fazer em GV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .install-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .btn {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: white;
            font-weight: bold;
        }
        
        .step.active {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
        }
        
        .step.completed {
            background: #28a745;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🏪 Instalação - O Que Fazer em GV</h1>
            <p>Configure sua plataforma em poucos passos</p>
        </div>
        
        <div class="steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <h2>Passo 1: Configuração do Banco de Dados</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Host do Banco:</label>
                    <input type="text" name="host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Nome do Banco:</label>
                    <input type="text" name="database" value="oque_fazer_gv" required>
                </div>
                <div class="form-group">
                    <label>Usuário:</label>
                    <input type="text" name="username" value="root" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="password">
                </div>
                <button type="submit" class="btn">Testar Conexão</button>
            </form>
        <?php elseif ($step == 2): ?>
            <h2>Passo 2: Criar Tabelas</h2>
            <p>Agora vamos criar as tabelas necessárias no banco de dados.</p>
            <form method="POST">
                <button type="submit" class="btn">Criar Tabelas</button>
            </form>
        <?php elseif ($step == 3): ?>
            <h2>🎉 Instalação Concluída!</h2>
            <div class="alert alert-success">
                <strong>Parabéns!</strong> A instalação foi concluída com sucesso.
            </div>
            <p><strong>Próximos passos:</strong></p>
            <ul>
                <li>Acesse a <a href="index.html">página inicial</a></li>
                <li>Crie sua conta de usuário</li>
                <li>Acesse o <a href="admin/login.php">painel administrativo</a></li>
            </ul>
            <p><strong>Credenciais padrão do admin:</strong></p>
            <ul>
                <li>Email: admin@oquefazeremgv.com.br</li>
                <li>Senha: admin123</li>
            </ul>
            <a href="index.html" class="btn">Ir para o Site</a>
        <?php endif; ?>
    </div>
</body>
</html>
