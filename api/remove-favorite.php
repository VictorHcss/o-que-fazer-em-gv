<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$input = json_decode(file_get_contents('php://input'), true);
$establishmentId = $input['establishment_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$establishmentId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do estabelecimento é obrigatório'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND establishment_id = ?");
    $stmt->execute([$userId, $establishmentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Removido dos favoritos'
    ]);
} catch (Exception $e) {
    error_log("Error in remove-favorite.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao remover dos favoritos'
    ]);
}
?>
