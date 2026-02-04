<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// AGREGAR: Función para logging
function logError($message, $data = null) {
    $logFile = __DIR__ . '/bcv-api-errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= "\n" . print_r($data, true);
    }
    $logMessage .= "\n---\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

$apis = [
    'https://bcv.justcarlux.dev/api/v1/rates',
    'https://bcv-api.rafnixg.dev/rates/',
    'https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page/bcv'
];

$result = null;
$errors = []; // AGREGAR: Array para guardar errores

foreach ($apis as $api) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch); // AGREGAR
    curl_close($ch);
    
    // AGREGAR: Log de cada intento
    if ($httpCode !== 200 || !$response) {
        $errorInfo = [
            'api' => $api,
            'httpCode' => $httpCode,
            'curlError' => $curlError,
            'response' => substr($response, 0, 200) // Primeros 200 caracteres
        ];
        $errors[] = $errorInfo;
        logError("Fallo al consultar API", $errorInfo);
        continue;
    }
    
    $data = json_decode($response, true);
    
    // AGREGAR: Log de respuesta exitosa
    logError("Respuesta recibida de $api", [
        'httpCode' => $httpCode,
        'data' => $data
    ]);
    
    // Normalizar respuesta según la API
    if (isset($data['rates']['usd'])) {
        $result = [
            'success' => true,
            'rate' => $data['rates']['usd'],
            'source' => 'justcarlux',
            'updatedAt' => $data['updatedAt'] ?? null
        ];
        break;
    } elseif (isset($data['dollar'])) {
        $result = [
            'success' => true,
            'rate' => $data['dollar'],
            'source' => 'rafnixg',
            'date' => $data['date'] ?? null
        ];
        break;
    } elseif (isset($data['price'])) {
        $result = [
            'success' => true,
            'rate' => floatval($data['price']),
            'source' => 'pydolar',
            'date' => $data['date'] ?? null
        ];
        break;
    }
}

if ($result) {
    echo json_encode($result);
} else {
    // AGREGAR: Log final de fallo
    logError("Todas las APIs fallaron", $errors);
    
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo obtener la tasa desde ninguna fuente',
        'debug' => $errors // Incluir detalles de errores
    ]);
}
?>
