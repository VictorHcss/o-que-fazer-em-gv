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
    $stmt = $pdo->prepare("
        SELECT e.*, 
               AVG(r.rating) as avg_rating,
               COUNT(r.id) as total_reviews
        FROM establishments e
        LEFT JOIN reviews r ON e.id = r.establishment_id
        WHERE e.is_active = 1 AND e.featured = 1
        GROUP BY e.id
        ORDER BY avg_rating DESC, total_reviews DESC
        LIMIT 6
    ");
    $stmt->execute();
    $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse features for each establishment
    foreach ($establishments as &$establishment) {
        if ($establishment['features']) {
            $establishment['features'] = json_decode($establishment['features'], true) ?: [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'establishments' => $establishments
    ]);
} catch (Exception $e) {
    error_log("Error in get-featured-establishments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar estabelecimentos em destaque'
    ]);
}
?>
