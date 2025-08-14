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

$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? '';
$filters = $input['filters'] ?? [];

try {
    $sql = "
        SELECT e.*, 
               AVG(r.rating) as avg_rating,
               COUNT(r.id) as total_reviews
        FROM establishments e
        LEFT JOIN reviews r ON e.id = r.establishment_id
        WHERE e.is_active = 1
    ";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (e.name LIKE ? OR e.description LIKE ? OR e.category LIKE ? OR e.neighborhood LIKE ?)";
        $searchTerm = "%{$query}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['category']) && $filters['category'] !== 'todos') {
        $sql .= " AND e.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['neighborhood'])) {
        $sql .= " AND e.neighborhood = ?";
        $params[] = $filters['neighborhood'];
    }
    
    if (!empty($filters['price'])) {
        $sql .= " AND e.price_range <= ?";
        $params[] = intval($filters['price']);
    }
    
    $sql .= " GROUP BY e.id";
    
    if (!empty($filters['rating'])) {
        $sql .= " HAVING avg_rating >= ?";
        $params[] = floatval($filters['rating']);
    }
    
    $sql .= " ORDER BY avg_rating DESC, total_reviews DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = intval($filters['limit']);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
        'total' => count($establishments),
        'query' => $query,
        'filters' => $filters
    ]);
} catch (Exception $e) {
    error_log("Error in search.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro na busca'
    ]);
}
?>
