<?php
// check_duplicate.php - API para verificar duplicados en el evento actual (Estructura Normalizada)
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Leer los datos recibidos en formato JSON
$input = json_decode(file_get_contents('php://input'), true);
$document_number = trim($input['document_number'] ?? '');
$event_id        = $input['event_id'] ?? null;

if (empty($document_number)) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    if (!empty($event_id)) {
        // 1. Si el frontend envía un event_id específico, unimos registrations con participants
        $stmt = $pdo->prepare("
            SELECT r.id 
            FROM registrations AS r
            INNER JOIN participants AS p ON r.participant_id = p.id
            WHERE p.document_number = ? AND r.event_id = ?
        ");
        $stmt->execute([$document_number, $event_id]);
    } else {
        // 2. Si no se envía el event_id, verificamos en el evento actual activo (events.status = 1)
        // uniendo registrations, participants y events
        $stmt = $pdo->prepare("
            SELECT r.id 
            FROM registrations AS r
            INNER JOIN participants AS p ON r.participant_id = p.id
            INNER JOIN events AS e ON r.event_id = e.id
            WHERE p.document_number = ? AND e.status = 1
        ");
        $stmt->execute([$document_number]);
    }
    
    // Si la consulta arroja algún resultado, significa que el participante ya está registrado en el evento
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de Base de Datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>