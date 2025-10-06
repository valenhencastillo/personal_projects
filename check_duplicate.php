<?php
// check_duplicate.php - API para verificar duplicados
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$document_number = $input['document_number'] ?? '';

if (empty($document_number)) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE document_number = ?");
    $stmt->execute([$document_number]);
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>