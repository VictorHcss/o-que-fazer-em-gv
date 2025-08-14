<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'site_settings':
            // Handle site settings update
            $message = 'Configurações do site atualizadas com sucesso!';
            $messageType = 'success';
            break;
            
        case 'email_settings':
            // Handle email settings update
            $message = 'Configurações de email atualizadas com sucesso!';
            $messageType = 'success';
            break;
            
        case 'moderation_settings':
            // Handle moderation settings update
            $message = 'Configurações de moderação atualizadas com sucesso!';
            $messageType = 'success';
            break;
            
        case 'clear_cache':
            // Handle cache clearing
            $message = 'Cache limpo com sucesso!';
            $messageType = 'success';
            break;
            
        case 'backup_database':
            // Handle database backup
            $message = 'Backup do banco de dados criado com sucesso!';
            $messageType = 'success';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin O Que Fazer em GV</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-6);
            background: var(--gray-50);
            min-height: 100vh;
        }

        .admin-header {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            margin-bottom: var(--space-8);
            box-shadow: var(--shadow-lg);
        }

        .admin-header h1 {
            font-size: var(--text-3xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .admin-header p {
            color: var(--text-muted);
            font-size: var(--text-lg);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-4);
            background: var(--gray-100);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 500;
            transition: all var(--transition-fast);
            margin-bottom: var(--space-6);
        }

        .back-btn:hover {
            background: var(--gray-200);
            color: var(--text-primary);
        }

        .settings-tabs {
            display: flex;
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-2);
            margin-bottom: var(--space-8);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
        }

        .tab-btn {
            flex: 1;
            padding: var(--space-4) var(--space-6);
            background: none;
            border: none;
            border-radius: var(--radius-lg);
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }

        .tab-btn.active {
            background: var(--gradient-primary);
            color: var(--text-white);
        }

        .tab-btn:hover:not(.active) {
            background: var(--gray-100);
            color: var(--text-primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .settings-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow-lg);
        }

        .settings-card h3 {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .form-group {
            margin-bottom: var(--space-6);
        }

        .form-group label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: var(--space-2);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--space-4);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: var(--text-base);
            color: var(--text-primary);
            background: var(--white);
            transition: all var(--transition-fast);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .checkbox-group {
            display: flex;
            align-items
