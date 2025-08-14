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
$reviewId = $input['review_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$reviewId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da avaliação é obrigatório'
    ]);
    exit;
}

try {
    // Check if review belongs to user
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->execute([$reviewId, $userId]);
    $review = $stmt->fetch();
    
    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Avaliação não encontrada ou não pertence ao usuário'
        ]);
        exit;
    }
    
    // Delete review
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->execute([$reviewId, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Avaliação excluída com sucesso'
    ]);
} catch (Exception $e) {
    error_log("Error in delete-review.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir avaliação'
    ]);
}
?>
