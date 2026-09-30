<?php
// process_registration.php - Procesar el registro de participantes (Adaptado a nueva BD)
session_start();
require_once 'config.php';
require_once 'email_functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // // ============================
    // // 1. Validar reCAPTCHA v3
    // // ============================
    // $recaptchaSecret = "6Lf0580rAAAAAOGOFrPQTHaMNHZW1EKWfp_K5lwy";
    // $recaptchaToken  = $_POST['recaptcha_token'] ?? '';

    // if (empty($recaptchaToken)) {
    //     throw new Exception("Falta el token de reCAPTCHA");
    // }

    // // Validar con Google usando cURL
    // $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    //     'secret'   => $recaptchaSecret,
    //     'response' => $recaptchaToken,
    //     'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    // ]));
    // $response = curl_exec($ch);
    // curl_close($ch);

    // $recaptchaResult = json_decode($response, true);

    // if (empty($recaptchaResult['success']) || ($recaptchaResult['score'] ?? 0) < 0.5) {
    //     throw new Exception("Fallo en la validación de reCAPTCHA");
    // }

    // ============================
    // 2. Extraer datos recibidos
    // ============================
    $data  = $_POST;
    $files = $_FILES;

    // *** LIMPIEZA DE TELÉFONOS (solo dígitos) ***
    $data['phone'] = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
    $data['guardianPhone'] = preg_replace('/[^0-9]/', '', $data['guardianPhone'] ?? '');
    $data['paymentPhone'] = preg_replace('/[^0-9]/', '', $data['paymentPhone'] ?? '');

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
        'microbitExperience',
        'shirtSize',
        'paymentMethod'
    ];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }

    // Limpiar número de documento (solo dígitos)
    $documentNumber = preg_replace('/[^0-9]/', '', $data['documentNumber'] ?? '');

    if (empty($documentNumber)) {
        throw new Exception("El número de documento no es válido.");
    }

    // ============================
    // 3. Identificar evento y categoría
    // ============================
    $eventId = !empty($data['eventId']) ? intval($data['eventId']) : (!empty($data['event_id']) ? intval($data['event_id']) : null);

    if (!$eventId) {
        $stmtEvent = $pdo->query("SELECT id FROM events WHERE status = 1 ORDER BY id DESC LIMIT 1");
        $activeEvent = $stmtEvent->fetch();
        if (!$activeEvent) {
            throw new Exception("No hay ningún evento activo actualmente para registrarse.");
        }
        $eventId = intval($activeEvent['id']);
    }

    // Calcular edad
    $birthDate = new DateTime($data['birthDate']);
    $today     = new DateTime();
    $age       = $today->diff($birthDate)->y;

    if ($age < 8 || $age > 20) {
        throw new Exception("La edad debe estar entre 8 y 20 años");
    }

    $categoryName = ($age <= 14) ? 'Junior' : 'Senior';

    // Obtener categoría del evento activo
    $stmtCat = $pdo->prepare("SELECT id, capacity FROM event_categories WHERE event_id = ? AND name = ?");
    $stmtCat->execute([$eventId, $categoryName]);
    $category = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception("No se encontró la categoría $categoryName para este evento");
    }
    $categoryId = $category['id'];

    // *** NUEVA VALIDACIÓN: Verificar que la categoría tenga retos definidos ***
    $stmtCheckChallenges = $pdo->prepare("
        SELECT COUNT(*) FROM challenges 
        WHERE category_id = ? AND event_id = ?
    ");
    $stmtCheckChallenges->execute([$categoryId, $eventId]);
    if ($stmtCheckChallenges->fetchColumn() == 0) {
        throw new Exception("La categoría $categoryName no está habilitada en este evento.");
    }

    // ============================
    // 4. Control de duplicados por evento
    // ============================
    $stmtCheck = $pdo->prepare("
        SELECT r.id 
        FROM registrations r
        INNER JOIN participants p ON r.participant_id = p.id
        WHERE p.document_number = ? AND r.event_id = ?
    ");
    $stmtCheck->execute([$documentNumber, $eventId]);
    $existingRegistration = $stmtCheck->fetchColumn();

    if ($existingRegistration) {
        throw new Exception("Ya estás registrado en este evento con el documento " . $documentNumber);
    }

    // ============================
    // 5. Validar datos de pago
    // ============================
    if ($data['paymentMethod'] === 'pago_movil') {
        $payment_required = ['paymentPhone', 'paymentBank', 'paymentDate', 'paymentReference'];
        foreach ($payment_required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo de pago requerido faltante: $field");
            }
        }
        if (!isset($files['paymentProof']) || $files['paymentProof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Debe subir el comprobante de pago");
        }
        if (!preg_match('/^\d{4}$/', $data['paymentReference'])) {
            throw new Exception("La referencia debe tener exactamente 4 dígitos");
        }
    }

    // ============================
    // 6. Verificar cupos de la categoría
    // ============================
    if ($category['capacity'] !== null && $category['capacity'] > 0) {
        // Contar inscripciones activas (no rechazadas) en la categoría
        $stmtCountActive = $pdo->prepare("
            SELECT COUNT(*) 
            FROM registrations r
            LEFT JOIN payments p ON r.id = p.registration_id
            WHERE r.category_id = ? AND r.event_id = ?
            AND (p.status IS NULL OR p.status != 'rechazado')
        ");
        $stmtCountActive->execute([$categoryId, $eventId]);
        $activeCount = $stmtCountActive->fetchColumn();

        if ($activeCount >= $category['capacity']) {
            throw new Exception("Cupos para la categoría $categoryName en este evento están completos");
        }
    }

    // ============================
    // 7. Subir archivos y preparar rutas
    // ============================
    $registration_number = 'HC' . $eventId . '-' . time() . rand(1000, 9999);

    $document_photo_path = null;
    if (isset($files['documentPhoto']) && $files['documentPhoto']['error'] === UPLOAD_ERR_OK) {
        $document_photo_path = uploadFile($files['documentPhoto'], 'documents/', $registration_number . '_document');
    }

    $authorization_doc_path = null;
    if (isset($files['authorizationDocument']) && $files['authorizationDocument']['error'] === UPLOAD_ERR_OK) {
        $authorization_doc_path = uploadFile($files['authorizationDocument'], 'authorizations/', $registration_number . '_authorization');
    }

    $payment_proof_path = null;
    if ($data['paymentMethod'] === 'pago_movil' && isset($files['paymentProof']) && $files['paymentProof']['error'] === UPLOAD_ERR_OK) {
        $payment_proof_path = uploadFile($files['paymentProof'], 'receipts/', $registration_number . '_payment');
    }

    // ============================
    // 8. Insertar datos en la base de datos
    // ============================
    $pdo->beginTransaction();

    try {
        // 8.1 Insertar participante (o actualizar si ya existe)
        $stmtCheckParticipant = $pdo->prepare("
            SELECT id FROM participants WHERE document_number = ? AND document_type = ?
        ");
        $stmtCheckParticipant->execute([$documentNumber, $data['docType']]);
        $participantId = $stmtCheckParticipant->fetchColumn();

        $isMinor = ($age < 18) ? 1 : 0;

        if (!$participantId) {
            // Crear nuevo participante
            $stmtParticipant = $pdo->prepare("
                INSERT INTO participants (
                    full_name, last_name, document_type, document_number,
                    nationality, birth_date, age, gender, email, phone,
                    address, state, city, institution, education_level,
                    grade, microbit_experience, document_photo_path, is_minor,
                    guardian_name, guardian_doc_type, guardian_document,
                    guardian_email, guardian_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtParticipant->execute([
                $data['fullName'],
                $data['lastName'],
                $data['docType'],
                $documentNumber, // <-- usando número limpio
                $data['nationality'] ?? 'V',
                $data['birthDate'],
                $age,
                $data['gender'],
                $data['email'],
                $data['phone'], // teléfono ya limpio
                $data['address'] ?? '',
                $data['state'] ?? '',
                $data['city'] ?? '',
                $data['institution'],
                $data['educationLevel'],
                $data['grade'] ?? '',
                $data['microbitExperience'],
                $document_photo_path,
                $isMinor,
                $data['guardianName'] ?? null,
                $data['guardianDocType'] ?? null,
                $data['guardianDocument'] ?? null,
                $data['guardianEmail'] ?? null,
                $data['guardianPhone'] ?? null // teléfono ya limpio
            ]);
            $participantId = $pdo->lastInsertId();
        } else {
            // Actualizar datos del participante existente
            $stmtUpdate = $pdo->prepare("
                UPDATE participants SET
                    full_name = ?, last_name = ?, nationality = ?, birth_date = ?,
                    age = ?, gender = ?, email = ?, phone = ?, address = ?,
                    state = ?, city = ?, institution = ?, education_level = ?,
                    grade = ?, microbit_experience = ?, document_photo_path = COALESCE(?, document_photo_path),
                    is_minor = ?, guardian_name = ?, guardian_doc_type = ?,
                    guardian_document = ?, guardian_email = ?, guardian_phone = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $data['fullName'],
                $data['lastName'],
                $data['nationality'] ?? 'V',
                $data['birthDate'],
                $age,
                $data['gender'],
                $data['email'],
                $data['phone'], // teléfono ya limpio
                $data['address'] ?? '',
                $data['state'] ?? '',
                $data['city'] ?? '',
                $data['institution'],
                $data['educationLevel'],
                $data['grade'] ?? '',
                $data['microbitExperience'],
                $document_photo_path, // puede ser null, no actualiza si null
                $isMinor,
                $data['guardianName'] ?? null,
                $data['guardianDocType'] ?? null,
                $data['guardianDocument'] ?? null,
                $data['guardianEmail'] ?? null,
                $data['guardianPhone'] ?? null, // teléfono ya limpio
                $participantId
            ]);
        }

        // 8.2 Insertar registro
        $stmtRegistration = $pdo->prepare("
            INSERT INTO registrations (
                registration_number, participant_id, event_id, category_id,
                shirt_size, expectations, authorization_doc_path,
                image_rights_accepted, data_verified, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmtRegistration->execute([
            $registration_number,
            $participantId,
            $eventId,
            $categoryId,
            $data['shirtSize'],
            $data['expectations'] ?? null,
            $authorization_doc_path,
            isset($data['imageRightsAccepted']) ? 1 : 0, // o usar $data['image_rights_accepted'] ?? 1
            1 // data_verified por defecto en 1
        ]);
        $registrationId = $pdo->lastInsertId();

        // 8.3 Insertar pago si corresponde
        if ($data['paymentMethod'] === 'pago_movil' || $data['paymentMethod'] === 'efectivo') {
            $paymentAmount = !empty($data['payment_amount_bs']) ? floatval($data['payment_amount_bs']) : 0;
            $bcvRate = !empty($data['bcv_rate']) ? floatval($data['bcv_rate']) : 0;

            $stmtPayment = $pdo->prepare("
                INSERT INTO payments (
                    registration_id, payment_method, payment_phone,
                    payment_bank, payment_date, payment_reference,
                    payment_proof_path, payment_amount_bs, bcv_rate, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            $stmtPayment->execute([
                $registrationId,
                $data['paymentMethod'],
                $data['paymentPhone'] ?? null, // teléfono ya limpio
                $data['paymentBank'] ?? null,
                $data['paymentDate'] ?? null,
                $data['paymentReference'] ?? null,
                $payment_proof_path,
                $paymentAmount,
                $bcvRate
            ]);
        }

        // 8.4 Actualizar contador de registrados en la categoría con recálculo real
        $stmtUpdateCount = $pdo->prepare("
            UPDATE event_categories ec
            SET ec.registered_count = (
                SELECT COUNT(*) FROM registrations r
                LEFT JOIN payments p ON r.id = p.registration_id
                WHERE r.category_id = ec.id AND r.event_id = ?
                AND (p.status IS NULL OR p.status != 'rechazado')
            )
            WHERE ec.id = ?
        ");
        $stmtUpdateCount->execute([$eventId, $categoryId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // ============================
    // 9. Actualizar lead parcial
    // ============================
    $stmtLead = $pdo->prepare("UPDATE partial_leads SET status = 'completado' WHERE document_number = ? OR email = ?");
    $stmtLead->execute([$documentNumber, $data['email']]);

    // ============================
    // 10. Generar QR y enviar correos
    // ============================
    $qr_url = generateQRCode($registration_number);
    sendConfirmationEmail($data['email'], $data['fullName'], $registration_number, $qr_url);
    if ($isMinor && !empty($data['guardianEmail'])) {
        sendGuardianNotificationEmail($data['guardianEmail'], $data['guardianName'] ?? '', $data['fullName'], $registration_number);
    }

    // Respuesta JSON de éxito
    echo json_encode([
        'success'             => true,
        'registration_number' => $registration_number,
        'qr_code'             => $qr_url,
        'message'             => 'Registro completado exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// ============================
// Funciones auxiliares
// ============================

function uploadFile($file, $directory, $baseName = null)
{
    $uploadDir = 'uploads/' . $directory;

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes) || !in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
        throw new Exception('Tipo de archivo no permitido (solo JPG, PNG y PDF)');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Archivo demasiado grande (máximo 5MB)');
    }

    $fileName = $baseName ? ($baseName . '.' . $ext) : (time() . '_' . basename($file['name']));
    $uploadPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Error al subir el archivo');
    }

    return $uploadPath;
}

function generateQRCode($registration_number)
{
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($registration_number);
}