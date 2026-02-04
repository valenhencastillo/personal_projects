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

    // Validar con Google usando cURL
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

    // Validar campos obligatorios básicos
    $required_fields = [
        'fullName',
        'lastName',
        'docType',
        'documentNumber',
        'birthDate',
        'gender',
        'email',
        'phone',
        'institution',
        'educationLevel',
        'grade',
        'category',
        'microbitExperience',
        'shirtSize',
        'paymentMethod'
    ];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }

    // Validar campos específicos de pago móvil
    if ($data['paymentMethod'] === 'pago_movil') {
        $payment_required = ['paymentPhone', 'paymentBank', 'paymentDate', 'paymentReference'];

        foreach ($payment_required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo de pago requerido faltante: $field");
            }
        }

        // Validar que se subió el comprobante
        if (!isset($files['paymentProof']) || $files['paymentProof']['error'] !== 0) {
            throw new Exception("Debe subir el comprobante de pago");
        }

        // Validar formato de referencia (4 dígitos)
        if (!preg_match('/^\d{4}$/', $data['paymentReference'])) {
            throw new Exception("La referencia debe tener exactamente 4 dígitos");
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

    // Aquí obtienes la categoría según la edad
    $category = ($age <= 14) ? 'Junior' : 'Senior';

    // Contar cuántos registros activos existen para la categoría
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE category = ? AND status = 1");
    $stmt->execute([$category]);
    $count = $stmt->fetchColumn();

    if ($count >= 40) {
        throw new Exception("Cupos para la categoría $category completos");
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

    // Procesar comprobante de pago
    $payment_proof_path = '';
    $payment_amount_bs = null;
    $bcv_rate = null;

    if ($data['paymentMethod'] === 'pago_movil') {
        if (isset($files['paymentProof']) && $files['paymentProof']['error'] === 0) {
            $payment_proof_path = uploadFile($files['paymentProof'], 'receipts/', $registration_number . '_payment');
        }

        // Obtener monto y tasa del formulario
        $payment_amount_bs = floatval($data['payment_amount_bs'] ?? 0);
        $bcv_rate = floatval($data['bcv_rate'] ?? 0);
    }

    $paymentDate = !empty($data['paymentDate']) ? $data['paymentDate'] : null;
    $paymentPhone = !empty($data['paymentPhone']) ? $data['paymentPhone'] : null;
    $paymentBank = !empty($data['paymentBank']) ? $data['paymentBank'] : null;
    $paymentReference = !empty($data['paymentReference']) ? $data['paymentReference'] : null;

    // Insertar en base de datos
    $sql = "INSERT INTO registrations (
        registration_number, full_name, last_name, document_type, document_number, nationality,
        birth_date, age, gender, email, phone, address, state, city,
        institution, education_level, grade, category, microbit_experience,
        expectations, shirt_size, document_photo_path, is_minor,
        guardian_name, guardian_doc_type, guardian_document, guardian_email,
        guardian_phone, authorization_doc_path,
        payment_method, payment_phone, payment_bank, payment_date, 
        payment_reference, payment_proof_path, payment_amount_bs, bcv_rate,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

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
        $authorization_doc_path,
        $data['paymentMethod'],
        $paymentPhone,      // Variable limpia
        $paymentBank,       // Variable limpia
        $paymentDate,       // Variable limpia (esta era la del error)
        $paymentReference,  // Variable limpia
        $payment_proof_path,
        $payment_amount_bs,
        $bcv_rate
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

function uploadFile($file, $directory, $baseName = null)
{
    // La ruta será: uploads/receipts/ o uploads/documents/ etc
    $uploadDir = 'uploads/' . $directory;

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Obtener extensión del archivo
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes) || !in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
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

function generateQRCode($registration_number)
{
    // Usando API gratuita para generar QR
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($registration_number);
}
