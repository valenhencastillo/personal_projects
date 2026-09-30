<?php
// check_capacity.php - API para verificar cupos disponibles por categoría
session_start();
require_once 'config.php'; // Ajusta la ruta si es necesario

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$categoryId = $input['category_id'] ?? null;
$eventId = $input['event_id'] ?? null;

if (empty($categoryId)) {
    echo json_encode([
        'available' => false,
        'message' => 'Categoría no especificada'
    ]);
    exit;
}

try {
    // Obtener la información de la categoría
    $sql = "
        SELECT ec.id, ec.name, ec.capacity, ec.registered_count,
               e.name as event_name, e.id as event_id
        FROM event_categories ec
        INNER JOIN events e ON ec.event_id = e.id
        WHERE ec.id = ? AND e.status = 1
    ";
    $params = [$categoryId];

    if ($eventId) {
        $sql .= " AND e.id = ?";
        $params[] = $eventId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode([
            'available' => false,
            'message' => 'Categoría no encontrada o evento inactivo'
        ]);
        exit;
    }

    // *** NUEVA VALIDACIÓN: Verificar que la categoría tenga retos definidos ***
    $stmtChallenges = $pdo->prepare("
        SELECT COUNT(*) FROM challenges 
        WHERE category_id = ? AND event_id = ?
    ");
    $stmtChallenges->execute([$categoryId, $category['event_id']]);
    if ($stmtChallenges->fetchColumn() == 0) {
        echo json_encode([
            'available' => false,
            'message' => 'La categoría no está habilitada para este evento.'
        ]);
        exit;
    }

    // Si capacity es NULL o 0, significa cupos ilimitados
    if ($category['capacity'] === null || $category['capacity'] == 0) {
        echo json_encode([
            'available' => true,
            'unlimited' => true,
            'category_id' => $category['id'],
            'category_name' => $category['name'],
            'event_name' => $category['event_name'],
            'message' => 'Cupos ilimitados disponibles'
        ]);
        exit;
    }

    // Contar inscripciones activas (excluyendo pagos rechazados)
    $stmtCountActive = $pdo->prepare("
        SELECT COUNT(*) 
        FROM registrations r
        LEFT JOIN payments p ON r.id = p.registration_id
        WHERE r.category_id = ? AND r.event_id = ?
        AND (p.status IS NULL OR p.status != 'rechazado')
    ");
    $stmtCountActive->execute([$categoryId, $category['event_id']]);
    $activeCount = $stmtCountActive->fetchColumn();

    $capacity = intval($category['capacity']);
    $availableSpots = $capacity - $activeCount;

    if ($availableSpots <= 0) {
        echo json_encode([
            'available' => false,
            'unlimited' => false,
            'category_id' => $category['id'],
            'category_name' => $category['name'],
            'event_name' => $category['event_name'],
            'capacity' => $capacity,
            'registered' => $activeCount,
            'available_spots' => 0,
            'message' => "Cupos para la categoría {$category['name']} completos"
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'unlimited' => false,
            'category_id' => $category['id'],
            'category_name' => $category['name'],
            'event_name' => $category['event_name'],
            'capacity' => $capacity,
            'registered' => $activeCount,
            'available_spots' => $availableSpots,
            'message' => "Quedan {$availableSpots} cupos de {$capacity} disponibles"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor',
        'message' => 'Ocurrió un error al verificar los cupos'
    ]);
}