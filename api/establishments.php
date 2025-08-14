<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    // Check if specific establishment is requested
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   AVG(r.rating) as avg_rating,
                   COUNT(r.id) as total_reviews
            FROM establishments e
            LEFT JOIN reviews r ON e.id = r.establishment_id
            WHERE e.id = ? AND e.is_active = 1
            GROUP BY e.id
        ");
        $stmt->execute([$id]);
        $establishment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($establishment) {
            // Parse features if it's JSON
            if ($establishment['features']) {
                $establishment['features'] = json_decode($establishment['features'], true) ?: [];
            }
            
            echo json_encode([
                'success' => true,
                'establishment' => $establishment
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Estabelecimento não encontrado'
            ]);
        }
    } else {
        // Get all establishments
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   AVG(r.rating) as avg_rating,
                   COUNT(r.id) as total_reviews
            FROM establishments e
            LEFT JOIN reviews r ON e.id = r.establishment_id
            WHERE e.is_active = 1
            GROUP BY e.id
            ORDER BY avg_rating DESC, total_reviews DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse features for each establishment
        foreach ($establishments as &$establishment) {
            if ($establishment['features']) {
                $establishment['features'] = json_decode($establishment['features'], true) ?: [];
            }
        }
        
        echo json_encode([
            'success' => true,
            'establishments' => $establishments,
            'total' => count($establishments)
        ]);
    }
} catch (Exception $e) {
    error_log("Error in establishments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>
