<?php
require_once 'database.php'; // Asegúrate de que sea tu conexión PDO

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_number = trim($_POST['registration_number'] ?? '');

    if (empty($registration_number)) {
        echo json_encode(['success' => false, 'message' => 'No se recibió ningún código.']);
        exit;
    }

    try {
        // 1. Buscar inscripción con JOIN para obtener datos del participante y categoría
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.attendance_status,
                p.full_name,
                p.last_name,
                p.document_number,
                c.name AS category
            FROM registrations r
            INNER JOIN participants p ON r.participant_id = p.id
            INNER JOIN event_categories c ON r.category_id = c.id
            WHERE r.registration_number = ?
            LIMIT 1
        ");
        $stmt->execute([$registration_number]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Validar si existe
        if (!$participant) {
            echo json_encode(['success' => false, 'message' => '❌ Código inválido. Participante no encontrado.']);
            exit;
        }

        // 3. Validar si ya fue registrado previamente
        if ($participant['attendance_status'] == 1) {
            echo json_encode([
                'success' => false,
                'message' => '⚠️ Este participante ya registró su entrada.',
                'data' => $participant
            ]);
            exit;
        }

        // 4. Marcar asistencia (actualizar por id de registro, más seguro)
        $updateStmt = $pdo->prepare("
            UPDATE registrations 
            SET attendance_status = 1, attendance_time = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$participant['id']]);

        // 5. Devolver datos actualizados (opcional: agregar attendance_time)
        $participant['attendance_time'] = date('Y-m-d H:i:s');

        echo json_encode([
            'success' => true,
            'message' => '✅ Asistencia registrada correctamente.',
            'data' => $participant
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}