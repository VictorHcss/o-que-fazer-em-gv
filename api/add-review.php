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
$rating = $input['rating'] ?? null;
$comment = $input['comment'] ?? '';
$userId = $_SESSION['user_id'];

// Validate input
if (!$establishmentId || !$rating) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados obrigatórios não fornecidos'
    ]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Avaliação deve ser entre 1 e 5 estrelas'
    ]);
    exit;
}

try {
    // Check if user already reviewed this establishment
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND establishment_id = ?");
    $stmt->execute([$userId, $establishmentId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing review
        $stmt = $pdo->prepare("
            UPDATE reviews 
            SET rating = ?, comment = ?, updated_at = NOW() 
            WHERE user_id = ? AND establishment_id = ?
        ");
        $stmt->execute([$rating, $comment, $userId, $establishmentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Avaliação atualizada com sucesso'
        ]);
    } else {
        // Insert new review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, establishment_id, rating, comment, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $establishmentId, $rating, $comment]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Avaliação adicionada com sucesso'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in add-review.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar avaliação'
    ]);
}
?>
