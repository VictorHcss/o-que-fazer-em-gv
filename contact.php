<?php
session_start();
require_once 'config/database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validação
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email inválido.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$name, $email, $subject, $message])) {
                $success_message = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
                
                // Limpar campos após sucesso
                $name = $email = $subject = $message = '';
            } else {
                $error_message = 'Erro ao enviar mensagem. Tente novamente.';
            }
        } catch (PDOException $e) {
            $error_message = 'Erro interno. Tente novamente mais tarde.';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - O Que Fazer em GV</title>
    <meta name="description" content="Entre em contato conosco. Tire suas dúvidas, envie sugestões ou relate problemas.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .contact-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            padding-top: 80px;
            display: flex;
            align-items: center;
        }
        
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-8);
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-12);
            align-items: start;
        }
        
        .contact-info {
            color: var(--text-white);
        }
        
        .contact-info h1 {
            font-family: var(--font-secondary);
            font-size: var(--text-5xl);
            font-weight: 800;
            margin-bottom: var(--space-6);
            color: var(--text-white);
        }
        
        .contact-info p {
            font-size: var(--text-xl);
            margin-bottom: var(--space-8);
            opacity: 0.9;
            line-height: var(--leading-relaxed);
        }
        
        .contact-details {
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-4);
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            backdrop-filter: blur(10px);
        }
        
        .contact-item-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-2xl);
            color: var(--text-white);
        }
        
        .contact-item-content h3 {
            font-size: var(--text-lg);
            font-weight: 600;
            margin-bottom: var(--space-1);
            color: var(--text-white);
        }
        
        .contact-item-content p {
            opacity: 0.8;
            margin: 0;
        }
        
        .contact-form {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-2xl);
        }
        
        .contact-form h2 {
            font-family: var(--font-secondary);
            font-size: var(--text-3xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-6);
            text-align: center;
        }
        
        .form-group {
            margin-bottom: var(--space-6);
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: var(--space-2);
            font-size: var(--text-sm);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: var(--space-4);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: var(--text-base);
            color: var(--text-primary);
            background: var(--white);
            transition: all var(--transition-fast);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            width: 100%;
            background: var(--gradient-primary);
            color: var(--text-white);
            border: none;
            padding: var(--space-4) var(--space-6);
            border-radius: var(--radius-lg);
            font-size: var(--text-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .alert {
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .back-link {
            position: fixed;
            top: 100px;
            left: var(--space-6);
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-white);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 500;
            backdrop-filter: blur(10px);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
        
        @media (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: var(--space-8);
            }
            
            .contact-info h1 {
                font-size: var(--text-4xl);
            }
            
            .contact-info p {
                font-size: var(--text-lg);
            }
            
            .contact-form {
                padding: var(--space-6);
            }
            
            .back-link {
                position: static;
                margin-bottom: var(--space-4);
                align-self: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .contact-container {
                padding: var(--space-4);
            }
            
            .contact-info h1 {
                font-size: var(--text-3xl);
            }
            
            .contact-form {
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <div class="contact-page">
        <div class="contact-container">
            <a href="index.html" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Início</span>
            </a>
            
            <div class="contact-content">
                <div class="contact-info">
                    <h1>Entre em Contato</h1>
                    <p>Tem alguma dúvida, sugestão ou quer reportar um problema? Estamos aqui para ajudar! Entre em contato conosco através do formulário ou pelos canais abaixo.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-item-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-item-content">
                                <h3>Email</h3>
                                <p>contato@oquefazeremgv.com.br</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-item-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-item-content">
                                <h3>Telefone</h3>
                                <p>(33) 99999-9999</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-item-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-item-content">
                                <h3>Localização</h3>
                                <p>Governador Valadares, MG</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-item-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-item-content">
                                <h3>Horário de Atendimento</h3>
                                <p>Segunda a Sexta: 9h às 18h</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h2>Envie sua Mensagem</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Assunto *</label>
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Mensagem *</label>
                            <textarea id="message" name="message" placeholder="Descreva sua dúvida, sugestão ou problema..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviar Mensagem</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
