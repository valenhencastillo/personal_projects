<?php
// process_registration.php - Procesar el registro
session_start();
require_once 'config.php';
require_once 'email_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // ============================
    // Validar reCAPTCHA v3
    // ============================
    $recaptchaSecret = "6Lf0580rAAAAAOGOFrPQTHaMNHZW1EKWfp_K5lwy"; 
    $recaptchaToken  = $_POST['recaptcha_token'] ?? '';

    if (empty($recaptchaToken)) {
        throw new Exception("Falta el token de reCAPTCHA");
    }

    // Validar con Google usando cURL (más seguro que file_get_contents)
    $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret'   => $recaptchaSecret,
        'response' => $recaptchaToken,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    $recaptchaResult = json_decode($response, true);

    if (empty($recaptchaResult['success']) || $recaptchaResult['score'] < 0.5) {
        throw new Exception("Fallo en la validación de reCAPTCHA");
    }

    // ============================
    // Validar datos recibidos
    // ============================
    $data = $_POST;
    $files = $_FILES;

    // Validar campos obligatorios
    $required_fields = [
        'fullName', 'lastName', 'docType', 'documentNumber', 'birthDate', 'gender',
        'email', 'phone', 'institution', 'educationLevel', 'grade', 'category',
        'microbitExperience', 'shirtSize'
    ];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }

    // Verificar duplicados por cédula
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE document_number = ?");
    $stmt->execute([$data['documentNumber']]);
    if ($stmt->fetch()) {
        throw new Exception("Este documento ya está registrado");
    }

    // Calcular edad
    $birthDate = new DateTime($data['birthDate']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    if ($age < 8 || $age > 20) {
        throw new Exception("La edad debe estar entre 8 y 20 años");
    }

    // Generar número de registro único
    $registration_number = 'HC2025-' . time() . rand(1000, 9999);

    // Procesar archivos subidos
    $document_photo_path = '';
    $authorization_doc_path = '';

    if (isset($files['documentPhoto']) && $files['documentPhoto']['error'] === 0) {
    $document_photo_path = uploadFile($files['documentPhoto'], 'documents/', $registration_number . '_document');
}

if (isset($files['authorizationDocument']) && $files['authorizationDocument']['error'] === 0) {
    $authorization_doc_path = uploadFile($files['authorizationDocument'], 'authorizations/', $registration_number . '_authorization');
}

    // Insertar en base de datos
    $sql = "INSERT INTO registrations (
        registration_number, full_name, last_name, document_type, document_number, nationality,
        birth_date, age, gender, email, phone, address, state, city,
        institution, education_level, grade, category, microbit_experience,
        expectations, shirt_size, document_photo_path, is_minor,
        guardian_name, guardian_doc_type, guardian_document, guardian_email,
        guardian_phone, authorization_doc_path, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $registration_number,
        $data['fullName'],
        $data['lastName'],
        $data['docType'],
        $data['documentNumber'],
        $data['nationality'] ?? '',
        $data['birthDate'],
        $age,
        $data['gender'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['state'],
        $data['city'],
        $data['institution'],
        $data['educationLevel'],
        $data['grade'],
        ($age <= 14) ? 'Junior' : 'Senior',
        $data['microbitExperience'],
        $data['expectations'] ?? '',
        $data['shirtSize'],
        $document_photo_path,
        ($age < 18) ? 1 : 0,
        $data['guardianName'] ?? '',
        $data['guardianDocType'] ?? '',
        $data['guardianDocument'] ?? '',
        $data['guardianEmail'] ?? '',
        $data['guardianPhone'] ?? '',
        $authorization_doc_path
    ]);

    // Generar código QR
    $qr_url = generateQRCode($registration_number);

    // Enviar emails de confirmación
    sendConfirmationEmail($data['email'], $data['fullName'], $registration_number, $qr_url);

    if (!empty($data['guardianEmail'])) {
        sendGuardianNotificationEmail($data['guardianEmail'], $data['guardianName'], $data['fullName'], $registration_number);
    }

    echo json_encode([
        'success' => true,
        'registration_number' => $registration_number,
        'qr_code' => $qr_url,
        'message' => 'Registro completado exitosamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function uploadFile($file, $directory, $baseName = null) {
    $uploadDir = 'uploads/' . $directory . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Obtener extensión del archivo
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes) || !in_array($ext, ['jpg','jpeg','png','pdf'])) {
        throw new Exception('Tipo de archivo no permitido');
    }

    // Validar tamaño (5MB máximo)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Archivo demasiado grande (máximo 5MB)');
    }

    // Nombre final del archivo
    if ($baseName) {
        $fileName = $baseName . '.' . $ext;
    } else {
        $fileName = time() . '_' . basename($file['name']);
    }

    $uploadPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Error al subir el archivo');
    }

    return $uploadPath;
}


function generateQRCode($registration_number) {
    // Usando API gratuita para generar QR
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($registration_number);
}
?>
