<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$apis = [
    'https://bcv.justcarlux.dev/api/v1/rates',
    'https://bcv-api.rafnixg.dev/rates/',
    'https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page/bcv'
];

$result = null;

foreach ($apis as $api) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        // Normalizar respuesta según la API
        if (isset($data['rates']['usd'])) {
            // API justcarlux
            $result = [
                'success' => true,
                'rate' => $data['rates']['usd'],
                'source' => 'justcarlux',
                'updatedAt' => $data['updatedAt'] ?? null
            ];
            break;
        } elseif (isset($data['dollar'])) {
            // API rafnixg
            $result = [
                'success' => true,
                'rate' => $data['dollar'],
                'source' => 'rafnixg',
                'date' => $data['date'] ?? null
            ];
            break;
        } elseif (isset($data['price'])) {
            // API PyDolar
            $result = [
                'success' => true,
                'rate' => floatval($data['price']),
                'source' => 'pydolar',
                'date' => $data['date'] ?? null
            ];
            break;
        }
    }
}

if ($result) {
    echo json_encode($result);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo obtener la tasa desde ninguna fuente'
    ]);
}
?>
