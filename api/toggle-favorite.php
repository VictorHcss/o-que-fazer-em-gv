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
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND establishment_id = ?");
    $stmt->execute([$userId, $establishmentId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND establishment_id = ?");
        $stmt->execute([$userId, $establishmentId]);
        
        echo json_encode([
            'success' => true,
            'favorited' => false,
            'message' => 'Removido dos favoritos'
        ]);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, establishment_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $establishmentId]);
        
        echo json_encode([
            'success' => true,
            'favorited' => true,
            'message' => 'Adicionado aos favoritos'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in toggle-favorite.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar favoritos'
    ]);
}
?>
