<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT establishment_id FROM user_favorites WHERE user_id = ?");
    $stmt->execute([$userId]);
    $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'favorites' => $favorites
    ]);
} catch (Exception $e) {
    error_log("Error in get-user-favorites.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar favoritos'
    ]);
}
?>
