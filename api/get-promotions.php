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
    $establishmentId = $_GET['establishment_id'] ?? null;
    
    $sql = "
        SELECT p.*, e.name as establishment_name, e.image_url as establishment_image, e.category, e.neighborhood
        FROM promotions p
        JOIN establishments e ON p.establishment_id = e.id
        WHERE p.is_active = 1 
        AND p.start_date <= CURDATE() 
        AND p.end_date >= CURDATE()
        AND e.is_active = 1
    ";
    
    $params = [];
    
    if ($establishmentId) {
        $sql .= " AND p.establishment_id = ?";
        $params[] = $establishmentId;
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'promotions' => $promotions
    ]);
} catch (Exception $e) {
    error_log("Error in get-promotions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar promoções'
    ]);
}
?>
