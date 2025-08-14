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
        SELECT ev.*, e.name as establishment_name, e.image_url, e.category, e.neighborhood, e.address
        FROM events ev
        JOIN establishments e ON ev.establishment_id = e.id
        WHERE ev.is_active = 1 
        AND ev.event_date >= CURDATE()
        AND e.is_active = 1
    ";
    
    $params = [];
    
    if ($establishmentId) {
        $sql .= " AND ev.establishment_id = ?";
        $params[] = $establishmentId;
    }
    
    $sql .= " ORDER BY ev.event_date ASC, ev.start_time ASC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
} catch (Exception $e) {
    error_log("Error in get-events.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar eventos'
    ]);
}
?>
