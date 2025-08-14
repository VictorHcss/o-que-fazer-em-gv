<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$establishmentId = $input['establishment_id'] ?? null;

if (!$establishmentId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do estabelecimento é obrigatório'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE establishments SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$establishmentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Visualização registrada'
    ]);
} catch (Exception $e) {
    error_log("Error in increment-view-count.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar visualização'
    ]);
}
?>
