<?php
// check_capacity.php - API para verificar cupos disponibles por categoría
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$category = $input['category'] ?? '';

if (empty($category)) {
    echo json_encode(['available' => false, 'message' => 'Categoría no especificada']);
    exit;
}

try {

    // Contar registros activos en la categoría recibida
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE category = ? AND status = 1");
    $stmt->execute([$category]);
    $count = $stmt->fetchColumn();

    if ($count >= 40) {
        echo json_encode(['available' => false, 'message' => "Cupos para la categoría $category completos"]);
    } else {
        echo json_encode(['available' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
