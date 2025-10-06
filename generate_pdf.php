<?php
// generate_pdf.php - Descarga segura de PDF estático
// Requerimientos mínimos: PHP 7.2+ (por finfo, stream_copy_to_stream, etc.)

// --- CONFIGURACIÓN ---
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Si usas composer/TCPDF en otras rutas, mantenlo; si no, puedes quitarlo.
// require_once 'vendor/autoload.php';

// --- START SESSION (para rate-limiting / tokens / autenticación opcional) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- HEADER POR DEFECTO ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Referrer-Policy: no-referrer-when-downgrade");
header('Content-Security-Policy: sandbox'); // restringe muchas capacidades en el contexto del navegador

// --- SOLO GET (o HEAD) ---
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo 'Método no permitido';
    exit;
}

// --- VALIDAR PARAMETRO ACTION DE FORMA SEGURA ---
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?? '';
if ($action !== 'download_authorization') {
    http_response_code(400);
    echo 'Parámetro inválido';
    exit;
}

// --- PROTECCIÓN ADICIONAL: RATE LIMITING SIMPLE (por sesión) ---
$maxRequestsPerMinute = 6;
$windowSeconds = 60;
if (!isset($_SESSION['download_requests'])) {
    $_SESSION['download_requests'] = [];
}
// limpiar timestamps viejos
$_SESSION['download_requests'] = array_filter(
    $_SESSION['download_requests'],
    function ($ts) use ($windowSeconds) {
        return ($ts + $windowSeconds) >= time();
    }
);
if (count($_SESSION['download_requests']) >= $maxRequestsPerMinute) {
    http_response_code(429);
    echo 'Demasiadas solicitudes. Intente nuevamente más tarde.';
    exit;
}
$_SESSION['download_requests'][] = time();

// --- OPCIONAL: VERIFICAR AUTENTICACIÓN / PERMISOS ---
// Si la descarga solo debe estar disponible para usuarios logueados,
// descomenta y adapta esta sección:
//
// if (empty($_SESSION['user']) || !$_SESSION['user']['is_allowed_to_download']) {
//     http_response_code(401);
//     echo 'No autorizado. Inicia sesión para descargar.';
//     exit;
// }

// --- DEFINIR RUTA SEGURA AL ARCHIVO (NO USAR INPUT DEL USUARIO) ---
$baseDir = realpath(__DIR__ . '/documents');
if ($baseDir === false) {
    error_log('Directorio documents no encontrado: ' . __DIR__ . '/documents');
    http_response_code(500);
    echo 'Error del servidor';
    exit;
}

// Nombre de archivo fijo (no aceptamos filename desde GET para evitar traversal)
$filename = 'autorizacion-hackathon-2025.pdf';
$filePath = $baseDir . DIRECTORY_SEPARATOR . $filename;

// Comprobar existencia y canonical path
$realFile = realpath($filePath);
if ($realFile === false || strpos($realFile, $baseDir) !== 0) {
    http_response_code(404);
    echo 'El archivo no existe.';
    exit;
}

if (!is_file($realFile) || !is_readable($realFile)) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

// --- VERIFICAR MIME CON FINFO PARA ASEGURAR QUE ES PDF ---
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $realFile) : mime_content_type($realFile);
if ($finfo) {
    finfo_close($finfo);
}

if ($mimeType !== 'application/pdf') {
    // No exponemos ruta ni detalles del archivo al usuario
    error_log("Tipo MIME inesperado para descarga: $realFile -> $mimeType");
    http_response_code(415);
    echo 'Tipo de archivo no soportado.';
    exit;
}

// --- SET HEADERS SEGUROS PARA LA RESPUESTA ---
$downloadName = 'autorizacion-hackathon-2025.pdf';

// Forzar descarga pero respetando HEAD requests
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . (string)filesize($realFile));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// --- LIMPIAR BUFFER PARA EVITAR CORRUPCIÓN --- 
// (importante si hay salidas previas o espacios en blanco)
while (ob_get_level()) {
    ob_end_clean();
}

// --- LOG SIMPLE DE ACCESO (no sensible) ---
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
error_log(sprintf('[%s] Archivo descargado: %s por %s', date('Y-m-d H:i:s'), $realFile, $clientIp));

// --- STREAMING SEGURO DEL ARCHIVO (evitar cargar todo en memoria) ---
$chunkSize = 1024 * 1024; // 1MB por chunk
$handle = fopen($realFile, 'rb');
if ($handle === false) {
    http_response_code(500);
    echo 'Error al abrir el archivo.';
    exit;
}

// Para evitar timeouts en descargas largas
set_time_limit(0);

// Si es HEAD, solo devolvemos cabeceras
if ($method === 'HEAD') {
    fclose($handle);
    exit;
}

while (!feof($handle)) {
    $buffer = fread($handle, $chunkSize);
    if ($buffer === false) break;
    echo $buffer;
    // flush para enviar al cliente inmediatamente
    if (function_exists('ob_flush')) ob_flush();
    flush();
}

fclose($handle);
exit;
