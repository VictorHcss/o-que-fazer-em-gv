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

try {
    $stmt = $pdo->query("
        SELECT neighborhood, COUNT(*) as count
        FROM establishments 
        WHERE is_active = 1 AND neighborhood IS NOT NULL AND neighborhood != ''
        GROUP BY neighborhood 
        ORDER BY count DESC
    ");
    $neighborhoods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'neighborhoods' => $neighborhoods
    ]);
} catch (Exception $e) {
    error_log("Error in get-neighborhoods.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar bairros'
    ]);
}
?>
