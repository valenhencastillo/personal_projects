<?php
session_start();
require_once 'database.php';
require_once 'auth.php'; // Asegura que el usuario esté autenticado

// Verificar acceso
checkAccess(['Admin', 'Juez']); // Ajustar roles permitidos

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];

    if ($eventId > 0) {
        // Verificar que el evento exista y esté activo
        $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND status = 1");
        $stmt->execute([$eventId]);
        if ($stmt->fetch()) {
            $_SESSION['current_event_id'] = $eventId;
        } else {
            $_SESSION['current_event_id'] = null;
            $_SESSION['error_message'] = "El evento seleccionado no es válido o no está activo.";
        }
    } else {
        $_SESSION['current_event_id'] = null;
    }
}

// Redirigir de vuelta a la página anterior (si existe) o al dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
