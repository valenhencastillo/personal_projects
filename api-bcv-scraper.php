<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getBCVRate() {
    $url = 'https://www.bcv.org.ve/';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$html) {
        return null;
    }
    
    // Extraer el valor del USD usando expresiones regulares
    // Buscar el patrón: USD seguido del valor
    if (preg_match('/USD.*?([0-9]{2,3}[,\.][0-9]{2,10})/s', $html, $matches)) {
        $rate = str_replace(',', '.', $matches[1]);
        
        // Extraer fecha
        $fecha = null;
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})|(\w+),\s*(\d{2})\s+(\w+)\s+(\d{4})/i', $html, $dateMatches)) {
            $fecha = $dateMatches[0];
        }
        
        return [
            'success' => true,
            'rate' => floatval($rate),
            'source' => 'bcv-direct',
            'date' => $fecha,
            'timestamp' => time()
        ];
    }
    
    return null;
}

// Sistema de caché para evitar consultas excesivas
$cacheFile = 'bcv_rate_cache.json';
$cacheTime = 3600; // 1 hora

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    echo json_encode($cachedData);
    exit;
}

// Intentar con scraping directo primero
$result = getBCVRate();

// Si falla, usar las APIs de respaldo
if (!$result) {
    $apis = [
        'https://bcv.justcarlux.dev/api/v1/rates',
        'https://bcv-api.rafnixg.dev/rates/',
        'https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page/bcv'
    ];
    
    foreach ($apis as $api) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['rates']['usd'])) {
                $result = [
                    'success' => true,
                    'rate' => $data['rates']['usd'],
                    'source' => 'justcarlux',
                    'date' => $data['updatedAt'] ?? null
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
    }
}

if ($result) {
    // Guardar en caché
    file_put_contents($cacheFile, json_encode($result));
    echo json_encode($result);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo obtener la tasa desde ninguna fuente'
    ]);
}
?>
