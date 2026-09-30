<?php
// auth.php - Funciones de autenticación y autorización seguras

if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies de sesión de forma segura antes de iniciar la sesión
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,           // Hasta cerrar navegador
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,    // Solo enviar por HTTPS si está disponible
        'httponly' => true,        // Evitar acceso desde JavaScript
        'samesite' => 'Strict'     // Prevenir CSRF
    ]);
    session_start();
}

// Configuración de seguridad de sesión
$sessionTimeout = 1800; // 30 minutos de inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    // Sesión expirada, destruirla
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Regenerar ID de sesión cada 15 minutos para mitigar session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 900) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

/**
 * Verifica si el usuario tiene sesión iniciada y posee uno de los roles permitidos.
 * 
 * @param array $allowedRoles Lista de roles permitidos (vacío = cualquier usuario autenticado)
 * @param PDO|null $pdo Conexión a base de datos para verificar que el usuario sigue existiendo y su rol no ha cambiado
 * @return bool True si tiene acceso, de lo contrario termina la ejecución.
 */
function checkAccess(array $allowedRoles = [], ?PDO $pdo = null): bool
{
    // 1. Verificar que la sesión tenga los datos básicos
    if (!isset($_SESSION['user_id'], $_SESSION['role_name'])) {
        handleUnauthorized();
    }

    // 2. Verificar rol permitido (si se especificaron roles)
    if (!empty($allowedRoles) && !in_array($_SESSION['role_name'], $allowedRoles)) {
        handleUnauthorized();
    }

    // 3. (Opcional pero recomendado) Verificar en BD que el usuario sigue activo y su rol es válido
    if ($pdo !== null) {
        $stmt = $pdo->prepare("
            SELECT r.name 
            FROM users u 
            INNER JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ? AND r.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['role_name']]);
        if (!$stmt->fetch()) {
            // El usuario ya no existe o su rol cambió, destruir sesión
            session_unset();
            session_destroy();
            handleUnauthorized();
        }
    }

    return true;
}

/**
 * Maneja la respuesta para accesos no autorizados.
 * Si es una petición AJAX devuelve JSON con código 403, de lo contrario redirige.
 */
function handleUnauthorized(): void
{
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    // Registrar intento no autorizado
    error_log("Acceso no autorizado desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . " a " . $_SERVER['REQUEST_URI']);

    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
        exit();
    }

    // Redirigir a página de login (ajusta la ruta real)
    header("Location: index.php");
    exit();
}

/**
 * Genera o devuelve el token CSRF almacenado en la sesión.
 * Debe usarse en formularios y peticiones POST.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF recibido coincida con el almacenado.
 * 
 * @param string $token Token enviado por el cliente
 * @return bool True si es válido
 */
function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}