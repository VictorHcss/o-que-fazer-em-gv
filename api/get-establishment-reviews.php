<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$establishmentId = $_GET['establishment_id'] ?? null;

if (!$establishmentId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do estabelecimento é obrigatório'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, u.avatar
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.establishment_id = ? AND r.is_active = 1
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$establishmentId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
} catch (Exception $e) {
    error_log("Error in get-establishment-reviews.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar avaliações'
    ]);
}
?>
