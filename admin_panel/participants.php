<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores pueden gestionar participantes
checkAccess(['Admin']);

// ==========================================
// FUNCIÓN PARA SUBIR ARCHIVOS
// ==========================================
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

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. LISTAR PARTICIPANTES (CON PAGINACIÓN Y BÚSQUEDA DINÁMICA) - FILTRADO POR EVENTO
        if ($action === 'list_participants') {
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $search = trim($_POST['search'] ?? '');

            $eventId = $_SESSION['current_event_id'] ?? null;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }

            // Verificar que el evento siga activo
            $stmtCheckEvent = $pdo->prepare("SELECT status FROM events WHERE id = ?");
            $stmtCheckEvent->execute([$eventId]);
            if ($stmtCheckEvent->fetchColumn() != 1) {
                echo json_encode(['status' => 'error', 'message' => 'El evento seleccionado ya no está activo.']);
                exit;
            }

            $whereClause = "WHERE r.event_id = ?";
            $params = [$eventId];

            if (!empty($search)) {
                $whereClause .= " AND (p.full_name LIKE ? OR p.last_name LIKE ? OR p.document_number LIKE ? OR p.email LIKE ?)";
                $searchTerm = "%$search%";
                array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            }

            $countQuery = "SELECT COUNT(*) 
                           FROM participants p
                           INNER JOIN registrations r ON p.id = r.participant_id
                           $whereClause";
            $stmtCount = $pdo->prepare($countQuery);
            $stmtCount->execute($params);
            $totalRecords = $stmtCount->fetchColumn();
            $totalPages = ceil($totalRecords / $limit);

            $query = "
                SELECT p.id, p.full_name, p.last_name, p.document_type, p.document_number, p.nationality, 
                       p.age, p.education_level, p.microbit_experience, p.created_at,
                       r.id as registration_id, r.category_id, r.shirt_size,
                       pay.id as payment_id, pay.status as payment_status
                FROM participants p
                INNER JOIN registrations r ON p.id = r.participant_id
                LEFT JOIN (
                    SELECT id, registration_id, status
                    FROM payments
                    WHERE id IN (SELECT MAX(id) FROM payments GROUP BY registration_id)
                ) pay ON r.id = pay.registration_id
                $whereClause
                ORDER BY p.id DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords
                ]
            ]);
            exit;
        }

        // 2. OBTENER DETALLE DE UN PARTICIPANTE (INCLUYE DATOS DE INSCRIPCIÓN Y PAGO)
        if ($action === 'get_participant') {
            $participantId = $_POST['id'];
            $eventId = $_SESSION['current_event_id'] ?? null;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }

            // Verificar que el evento siga activo
            $stmtCheckEvent = $pdo->prepare("SELECT status FROM events WHERE id = ?");
            $stmtCheckEvent->execute([$eventId]);
            if ($stmtCheckEvent->fetchColumn() != 1) {
                echo json_encode(['status' => 'error', 'message' => 'El evento seleccionado ya no está activo.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM participants WHERE id = ?");
            $stmt->execute([$participantId]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                echo json_encode(['status' => 'error', 'message' => 'Participante no encontrado.']);
                exit;
            }

            // Obtener inscripción
            $stmtReg = $pdo->prepare("SELECT id, category_id, shirt_size, expectations, authorization_doc_path 
                                      FROM registrations 
                                      WHERE participant_id = ? AND event_id = ?");
            $stmtReg->execute([$participantId, $eventId]);
            $registration = $stmtReg->fetch(PDO::FETCH_ASSOC);

            if ($registration) {
                $participant['registration_data'] = $registration;

                // Obtener pago asociado
                $stmtPay = $pdo->prepare("SELECT * FROM payments WHERE registration_id = ?");
                $stmtPay->execute([$registration['id']]);
                $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);
                if ($payment) {
                    $participant['payment_data'] = $payment;
                }
            }

            echo json_encode(['status' => 'success', 'data' => $participant]);
            exit;
        }

        // 3. GUARDAR / EDITAR PARTICIPANTE
        if ($action === 'save_participant') {
            $eventId = $_SESSION['current_event_id'] ?? null;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }

            // *** NUEVA VALIDACIÓN: El evento debe estar activo ***
            $stmtCheckEvent = $pdo->prepare("SELECT status FROM events WHERE id = ?");
            $stmtCheckEvent->execute([$eventId]);
            if ($stmtCheckEvent->fetchColumn() != 1) {
                echo json_encode(['status' => 'error', 'message' => 'El evento seleccionado no está activo. No se pueden registrar participantes.']);
                exit;
            }

            $id = $_POST['participant_id'] ?? '';
            $isEditing = !empty($id);

            // Sanitizar número de documento
            $documentNumber = preg_replace('/[^0-9]/', '', $_POST['document_number'] ?? '');

            $data = [
                $_POST['full_name'],
                $_POST['last_name'],
                $_POST['document_type'],
                $documentNumber,
                $_POST['nationality'],
                $_POST['birth_date'],
                $_POST['age'],
                $_POST['gender'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['state'],
                $_POST['city'],
                $_POST['institution'],
                $_POST['education_level'],
                $_POST['grade'],
                $_POST['microbit_experience'],
                $_POST['is_minor'] ?? 0,
                $_POST['guardian_name'] ?? null,
                $_POST['guardian_doc_type'] ?? null,
                $_POST['guardian_document'] ?? null,
                $_POST['guardian_email'] ?? null,
                $_POST['guardian_phone'] ?? null
            ];

            // Verificar si el participante ya existe
            $stmtCheck = $pdo->prepare("SELECT id FROM participants WHERE document_type = ? AND document_number = ? LIMIT 1");
            $stmtCheck->execute([$_POST['document_type'], $documentNumber]);
            $existingParticipant = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingParticipant) {
                $participantId = $existingParticipant['id'];
                $data[] = $participantId;
                $sql = "UPDATE participants SET 
                    full_name=?, last_name=?, document_type=?, document_number=?, nationality=?, birth_date=?, age=?, gender=?, 
                    email=?, phone=?, address=?, state=?, city=?, institution=?, education_level=?, grade=?, microbit_experience=?, 
                    is_minor=?, guardian_name=?, guardian_doc_type=?, guardian_document=?, guardian_email=?, guardian_phone=?
                WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                $message = 'Datos del participante actualizados correctamente.';
            } else {
                $sql = "INSERT INTO participants (
                    full_name, last_name, document_type, document_number, nationality, birth_date, age, gender, 
                    email, phone, address, state, city, institution, education_level, grade, microbit_experience, 
                    is_minor, guardian_name, guardian_doc_type, guardian_document, guardian_email, guardian_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                $participantId = $pdo->lastInsertId();
                $message = 'Participante registrado con éxito.';
            }

            // Obtener categoría y validar
            $categoryId = $_POST['category_id'] ?? null;
            $shirtSize = $_POST['shirt_size'] ?? 'M';
            $expectations = $_POST['expectations'] ?? null;
            $authorizationDocPath = $_POST['authorization_doc_path'] ?? null;

            if (!$categoryId) {
                echo json_encode(['status' => 'error', 'message' => 'Falta la categoría para la inscripción.']);
                exit;
            }

            // Verificar que la categoría tenga retos
            $stmtCheckChallenges = $pdo->prepare("SELECT COUNT(*) FROM challenges WHERE category_id = ? AND event_id = ?");
            $stmtCheckChallenges->execute([$categoryId, $eventId]);
            if ($stmtCheckChallenges->fetchColumn() == 0) {
                echo json_encode(['status' => 'error', 'message' => 'La categoría seleccionada no tiene retos definidos y no permite inscripciones.']);
                exit;
            }

            // =============================================
            // VALIDACIÓN DE CUPOS (CON CONTEO REAL)
            // =============================================
            $stmtCapacity = $pdo->prepare("SELECT capacity FROM event_categories WHERE id = ?");
            $stmtCapacity->execute([$categoryId]);
            $capacity = $stmtCapacity->fetchColumn();

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

            if ($capacity !== null && $capacity > 0 && $activeCount >= $capacity) {
                echo json_encode(['status' => 'error', 'message' => 'La categoría está llena.']);
                exit;
            }

            // =============================================
            // LÓGICA DE EDICIÓN O CREACIÓN DE INSCRIPCIÓN
            // =============================================
            if ($isEditing) {
                // Buscar la inscripción existente
                $stmtFindReg = $pdo->prepare("SELECT id FROM registrations WHERE participant_id = ? AND event_id = ? LIMIT 1");
                $stmtFindReg->execute([$participantId, $eventId]);
                $registrationId = $stmtFindReg->fetchColumn();

                if (!$registrationId) {
                    echo json_encode(['status' => 'error', 'message' => 'No se encontró la inscripción del participante en este evento.']);
                    exit;
                }

                // Actualizar la inscripción
                $stmtUpdateReg = $pdo->prepare("UPDATE registrations SET 
                    category_id = ?, shirt_size = ?, expectations = ?, authorization_doc_path = ?
                    WHERE id = ?");
                $stmtUpdateReg->execute([$categoryId, $shirtSize, $expectations, $authorizationDocPath, $registrationId]);

                $message = 'Inscripción actualizada correctamente.';
            } else {
                // Verificar si ya está inscrito en ESTE evento (solo para creación)
                $stmtCheckReg = $pdo->prepare("SELECT id FROM registrations WHERE participant_id = ? AND event_id = ?");
                $stmtCheckReg->execute([$participantId, $eventId]);
                if ($stmtCheckReg->fetchColumn()) {
                    echo json_encode(['status' => 'error', 'message' => 'Este participante ya está inscrito en el evento seleccionado.']);
                    exit;
                }

                // Generar número de registro
                $registrationNumber = 'HC' . $eventId . '-' . time() . rand(1000, 9999);

                // Insertar inscripción
                $stmtReg = $pdo->prepare("INSERT INTO registrations (
                    registration_number, participant_id, event_id, category_id, shirt_size, expectations,
                    authorization_doc_path, status, image_rights_accepted, data_verified
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', 1, 1)");
                $stmtReg->execute([
                    $registrationNumber,
                    $participantId,
                    $eventId,
                    $categoryId,
                    $shirtSize,
                    $expectations,
                    $authorizationDocPath
                ]);
                $registrationId = $pdo->lastInsertId();
                $message = 'Participante registrado e inscrito correctamente.';
            }

            // =============================================
            // PROCESAR PAGO (COMÚN PARA CREAR Y EDITAR)
            // =============================================
            $paymentMethod = $_POST['payment_method'] ?? null;
            if ($paymentMethod) {
                $paymentPhone = $_POST['payment_phone'] ?? null;
                $paymentBank = $_POST['payment_bank'] ?? null;
                $paymentDate = $_POST['payment_date'] ?? null;
                $paymentReference = $_POST['payment_reference'] ?? null;
                $paymentAmountBs = $_POST['payment_amount_bs'] ?? 0;
                $bcvRate = $_POST['bcv_rate'] ?? 0;

                // Procesar archivo de comprobante si se subió
                $paymentProofPath = null;
                if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                    $paymentProofPath = uploadFile($_FILES['payment_proof'], 'receipts/', ($isEditing ? 'edit_' : '') . $registrationId . '_payment');
                } elseif (!empty($_POST['existing_payment_proof_path'])) {
                    $paymentProofPath = $_POST['existing_payment_proof_path'];
                }

                // Verificar si ya existe un pago para esta inscripción
                $stmtCheckPay = $pdo->prepare("SELECT id FROM payments WHERE registration_id = ? LIMIT 1");
                $stmtCheckPay->execute([$registrationId]);
                $existingPayId = $stmtCheckPay->fetchColumn();

                if ($existingPayId) {
                    // Actualizar pago existente
                    $stmtUpdatePay = $pdo->prepare("UPDATE payments SET 
                        payment_method = ?, payment_phone = ?, payment_bank = ?, payment_date = ?,
                        payment_reference = ?, payment_proof_path = ?, payment_amount_bs = ?, bcv_rate = ?, status = 'pendiente'
                        WHERE id = ?");
                    $stmtUpdatePay->execute([
                        $paymentMethod,
                        $paymentPhone,
                        $paymentBank,
                        $paymentDate,
                        $paymentReference,
                        $paymentProofPath,
                        $paymentAmountBs,
                        $bcvRate,
                        $existingPayId
                    ]);
                } else {
                    // Insertar nuevo pago
                    $stmtPay = $pdo->prepare("INSERT INTO payments (
                        registration_id, payment_method, payment_phone, payment_bank, payment_date,
                        payment_reference, payment_proof_path, payment_amount_bs, bcv_rate, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')");
                    $stmtPay->execute([
                        $registrationId,
                        $paymentMethod,
                        $paymentPhone,
                        $paymentBank,
                        $paymentDate,
                        $paymentReference,
                        $paymentProofPath,
                        $paymentAmountBs,
                        $bcvRate
                    ]);
                }
            }

            // =============================================
            // RECALCULAR registered_count DE LA CATEGORÍA
            // =============================================
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

            echo json_encode([
                'status' => 'success',
                'message' => $message . ($paymentMethod ? ' Pago actualizado correctamente.' : '')
            ]);
            exit;
        }

        // 4. ELIMINAR PARTICIPANTE (eliminar inscripción y pago asociado)
        if ($action === 'delete_participant') {
            $id = $_POST['id'];
            $eventId = $_SESSION['current_event_id'] ?? null;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }

            // Verificar que el evento siga activo
            $stmtCheckEvent = $pdo->prepare("SELECT status FROM events WHERE id = ?");
            $stmtCheckEvent->execute([$eventId]);
            if ($stmtCheckEvent->fetchColumn() != 1) {
                echo json_encode(['status' => 'error', 'message' => 'El evento seleccionado ya no está activo.']);
                exit;
            }

            // Obtener registration_id y category_id para eliminar pagos y recalcular contador
            $stmtReg = $pdo->prepare("SELECT id, category_id FROM registrations WHERE participant_id = ? AND event_id = ?");
            $stmtReg->execute([$id, $eventId]);
            $registration = $stmtReg->fetch(PDO::FETCH_ASSOC);

            if ($registration) {
                // Eliminar pagos asociados
                $stmtDelPay = $pdo->prepare("DELETE FROM payments WHERE registration_id = ?");
                $stmtDelPay->execute([$registration['id']]);

                // Eliminar inscripción
                $stmtDelReg = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
                $stmtDelReg->execute([$registration['id']]);

                // Recalcular contador de la categoría afectada
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
                $stmtUpdateCount->execute([$eventId, $registration['category_id']]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Inscripción y pagos eliminados correctamente.']);
            exit;
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'El documento ya está registrado.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

require_once 'header.php';
require_once 'sidebar.php';

// Obtener el evento actual desde la sesión
$currentEventId = $_SESSION['current_event_id'] ?? null;

// Verificar que el evento exista y esté activo
if ($currentEventId) {
    $stmtCheckEvent = $pdo->prepare("SELECT status FROM events WHERE id = ?");
    $stmtCheckEvent->execute([$currentEventId]);
    $eventStatus = $stmtCheckEvent->fetchColumn();
    if ($eventStatus != 1) {
        unset($_SESSION['current_event_id']);
        $currentEventId = null;
    }
}

// Si no hay evento seleccionado, mostrar mensaje y no permitir registro
if (!$currentEventId) {
    echo '<div class="container py-4"><div class="alert alert-warning">Debe seleccionar un evento en el menú lateral antes de gestionar participantes.</div></div>';
    require_once 'footer.php';
    exit;
}

// Cargar solo categorías que tengan retos definidos
$stmtCategories = $pdo->prepare("
    SELECT c.id, c.name
    FROM event_categories c
    INNER JOIN challenges ch ON ch.category_id = c.id AND ch.event_id = c.event_id
    WHERE c.event_id = ?
    GROUP BY c.id, c.name
    HAVING COUNT(ch.id) > 0
    ORDER BY c.name
");
$stmtCategories->execute([$currentEventId]);
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color: #2C3E50;"><i class="bi bi-people-fill"></i> Gestión de Participantes - Evento Actual</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <div id="alertContainer"></div>

    <style>
        :root {
            --primary: #6C63FF;
            --dark: #2C3E50;
            --light-bg: #F5F7FA;
            --card-radius: 20px;
            --shadow: 0 8px 24px rgba(0,0,0,0.08);
            --success: #2ECC71;
            --danger: #E74C3C;
            --warning: #F39C12;
        }

        .card-modern {
            background: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-modern .card-header {
            background: var(--dark);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .card-modern .card-body {
            padding: 1.5rem;
        }

        .form-control-modern, .form-select-modern {
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .form-control-modern:focus, .form-select-modern:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn-gradient-success {
            background: linear-gradient(135deg, #2ECC71, #58D68D);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-gradient-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 204, 113, 0.4);
            color: white;
        }

        .table-modern {
            margin-bottom: 0;
        }

        .table-modern th {
            font-weight: 600;
            color: #7f8c8d;
            border-bottom: 2px solid #f0f0f0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
        }

        .table-modern td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
        }

        .input-group-modern {
            border-radius: 12px;
            overflow: hidden;
        }

        .input-group-modern .form-control {
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 12px 0 0 12px;
        }

        .input-group-modern .btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0 12px 12px 0;
        }
    </style>

    <!-- FORMULARIO DE REGISTRO / EDICIÓN -->
    <div class="card card-modern" id="formCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0" id="formTitle">Registrar Nuevo Participante</h5>
            <button type="button" class="btn btn-sm btn-outline-light d-none" id="btnCancelEdit" onclick="resetForm()">Cancelar Edición</button>
        </div>
        <div class="card-body">
            <form id="participantForm" onsubmit="saveParticipant(event)" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_participant">
                <input type="hidden" name="participant_id" id="participant_id" value="">
                <input type="hidden" name="is_minor" id="is_minor" value="0">
                <input type="hidden" name="event_id" id="event_id" value="<?= $currentEventId ?>">

                <!-- DATOS PERSONALES -->
                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person-badge"></i> Datos Personales</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Nombres *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control form-control-modern" required placeholder="Ej: Juan Carlos">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Apellidos</label>
                        <input type="text" name="last_name" id="last_name" class="form-control form-control-modern" placeholder="Ej: Pérez Gómez">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Nacionalidad</label>
                        <select name="nationality" id="nationality" class="form-select form-select-modern">
                            <option value="V">Venezolano (V)</option>
                            <option value="E">Extranjero (E)</option>
                            <option value="P">Pasaporte (P)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Tipo Doc. *</label>
                        <select name="document_type" id="document_type" class="form-select form-select-modern" required>
                            <option value="cedula">Cédula</option>
                            <option value="pasaporte">Pasaporte</option>
                            <option value="cedula_escolar">C. Escolar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">N° Documento *</label>
                        <input type="text" name="document_number" id="document_number" class="form-control form-control-modern" required placeholder="Ej: 30123456"
                            pattern="[0-9]+" title="Solo números" oninput="this.value = this.value.replace(/\D/g, '')">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Nacimiento *</label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control form-control-modern" onchange="calculateAge()" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-bold">Edad</label>
                        <input type="number" name="age" id="age" class="form-control form-control-modern bg-light" readonly tabindex="-1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Género *</label>
                        <select name="gender" id="gender" class="form-select form-select-modern" required>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Correo Electrónico *</label>
                        <input type="email" name="email" id="email" class="form-control form-control-modern" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Teléfono *</label>
                        <input type="tel" name="phone" id="phone" class="form-control form-control-modern" required placeholder="0412-1234567">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado *</label>
                        <input type="text" name="state" id="state" class="form-control form-control-modern" required placeholder="Ej: Portuguesa">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Ciudad *</label>
                        <input type="text" name="city" id="city" class="form-control form-control-modern" required placeholder="Ej: Araure">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dirección Completa *</label>
                        <input type="text" name="address" id="address" class="form-control form-control-modern" required placeholder="Av. Principal, Casa #123">
                    </div>
                </div>

                <!-- DATOS ACADÉMICOS -->
                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-mortarboard"></i> Información Académica y Experiencia</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Institución Educativa *</label>
                        <input type="text" name="institution" id="institution" class="form-control form-control-modern" required placeholder="Colegio o Universidad">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Nivel Académico *</label>
                        <select name="education_level" id="education_level" class="form-select form-select-modern" required>
                            <option value="primaria">Primaria</option>
                            <option value="bachillerato">Bachillerato</option>
                            <option value="universidad">Universidad</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Grado / Año</label>
                        <input type="text" name="grade" id="grade" class="form-control form-control-modern" placeholder="Ej: 4to Año">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Exp. Micro:bit *</label>
                        <select name="microbit_experience" id="microbit_experience" class="form-select form-select-modern" required>
                            <option value="ninguna">Ninguna</option>
                            <option value="basica">Básica</option>
                            <option value="intermedia">Intermedia</option>
                            <option value="avanzada">Avanzada</option>
                        </select>
                    </div>
                </div>

                <!-- INSCRIPCIÓN AL EVENTO -->
                <h6 class="text-success border-bottom pb-2 mb-3"><i class="bi bi-ticket-perforated"></i> Inscripción al Evento</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Categoría *</label>
                        <select name="category_id" id="category_id" class="form-select form-select-modern" required>
                            <option value="">-- Seleccione categoría --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Talla de franela *</label>
                        <select name="shirt_size" id="shirt_size" class="form-select form-select-modern" required>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Expectativas (opcional)</label>
                        <textarea name="expectations" id="expectations" class="form-control form-control-modern" rows="2"></textarea>
                    </div>
                </div>

                <!-- DATOS DE PAGO -->
                <h6 class="text-info border-bottom pb-2 mb-3"><i class="bi bi-credit-card"></i> Datos de Pago</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Método de Pago *</label>
                        <select name="payment_method" id="payment_method" class="form-select form-select-modern" required onchange="togglePaymentFields()">
                            <option value="">-- Seleccione --</option>
                            <option value="pago_movil">Pago Móvil</option>
                            <option value="efectivo">Efectivo</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="payment_phone_group" style="display:none;">
                        <label class="form-label fw-bold">Teléfono Pago Móvil</label>
                        <input type="text" name="payment_phone" id="payment_phone" class="form-control form-control-modern" placeholder="0412-1234567">
                    </div>
                    <div class="col-md-3" id="payment_bank_group" style="display:none;">
                        <label class="form-label fw-bold">Banco</label>
                        <input type="text" name="payment_bank" id="payment_bank" class="form-control form-control-modern" placeholder="Ej: Mercantil">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Pago</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control form-control-modern">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Referencia</label>
                        <input type="text" name="payment_reference" id="payment_reference" class="form-control form-control-modern" placeholder="N° de referencia">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Monto en Bs.</label>
                        <input type="number" step="0.01" name="payment_amount_bs" id="payment_amount_bs" class="form-control form-control-modern" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tasa BCV</label>
                        <input type="number" step="0.01" name="bcv_rate" id="bcv_rate" class="form-control form-control-modern" placeholder="0.00">
                    </div>
                    <!-- CAMPO DE COMPROBANTE COMO ARCHIVO -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Comprobante de Pago</label>
                        <input type="file" name="payment_proof" id="payment_proof" class="form-control form-control-modern" accept=".jpg,.jpeg,.png,.pdf">
                        <input type="hidden" name="existing_payment_proof_path" id="existing_payment_proof_path" value="">
                        <div id="currentProofLink" class="mt-2 small"></div>
                    </div>
                </div>

                <!-- DATOS DEL REPRESENTANTE (CONDICIONAL) -->
                <div id="guardianSection" class="d-none">
                    <h6 class="text-warning border-bottom border-warning pb-2 mb-3"><i class="bi bi-shield-exclamation"></i> Datos del Representante Legal (Obligatorio menores de 18 años)</h6>
                    <div class="row g-3 mb-4 p-3 bg-light rounded border border-warning">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Nombre del Representante *</label>
                            <input type="text" name="guardian_name" id="guardian_name" class="form-control form-control-modern" placeholder="Nombre completo">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Tipo Doc. Rep.</label>
                            <select name="guardian_doc_type" id="guardian_doc_type" class="form-select form-select-modern">
                                <option value="V">V</option>
                                <option value="E">E</option>
                                <option value="P">P</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Documento Rep. *</label>
                            <input type="text" name="guardian_document" id="guardian_document" class="form-control form-control-modern" placeholder="Cédula">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Teléfono Rep.</label>
                            <input type="tel" name="guardian_phone" id="guardian_phone" class="form-control form-control-modern" placeholder="Teléfono">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Correo Rep.</label>
                            <input type="email" name="guardian_email" id="guardian_email" class="form-control form-control-modern" placeholder="Correo">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-gradient-success" id="btnSave">
                        <i class="bi bi-save"></i> Guardar e Inscribir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTA DE PARTICIPANTES INSCRITOS EN EL EVENTO ACTUAL -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-table"></i> Participantes Inscritos en este Evento</h5>
            <div class="input-group input-group-modern" style="width: 280px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o cédula..." onkeyup="debounceSearch()">
                <button class="btn btn-light" type="button" onclick="loadParticipantsList(1)"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-modern" id="participantsTable">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Edad</th>
                            <th>Nivel / Experiencia</th>
                            <th>Registro</th>
                            <th>Pago</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carga dinámica por JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small" id="paginationInfo">Mostrando registros...</span>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimer = null;

    function calculateAge() {
        const birthDateVal = document.getElementById('birth_date').value;
        if (!birthDateVal) return;

        const birthDate = new Date(birthDateVal);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        document.getElementById('age').value = age;

        const isMinor = age < 18;
        document.getElementById('is_minor').value = isMinor ? 1 : 0;

        const guardianSection = document.getElementById('guardianSection');
        const reqFields = ['guardian_name', 'guardian_document'];

        if (isMinor) {
            guardianSection.classList.remove('d-none');
            reqFields.forEach(id => document.getElementById(id).setAttribute('required', 'required'));
        } else {
            guardianSection.classList.add('d-none');
            reqFields.forEach(id => document.getElementById(id).removeAttribute('required'));
        }
    }

    function togglePaymentFields() {
        const method = document.getElementById('payment_method').value;
        const phoneGroup = document.getElementById('payment_phone_group');
        const bankGroup = document.getElementById('payment_bank_group');
        if (method === 'pago_movil') {
            phoneGroup.style.display = '';
            bankGroup.style.display = '';
        } else {
            phoneGroup.style.display = 'none';
            bankGroup.style.display = 'none';
        }
    }

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadParticipantsList(1);
        }, 300);
    }

    async function loadParticipantsList(page = 1) {
        currentPage = page;
        const searchValue = document.getElementById('searchInput').value;
        const formData = new FormData();
        formData.append('action', 'list_participants');
        formData.append('page', page);
        formData.append('search', searchValue);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'error') {
                showAlert(json.message, 'warning');
                return;
            }

            const tbody = document.querySelector('#participantsTable tbody');
            tbody.innerHTML = '';

            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No se encontraron participantes inscritos en este evento.</td></tr>';
                document.getElementById('paginationInfo').innerText = 'Sin registros';
                document.getElementById('paginationControls').innerHTML = '';
                return;
            }

            json.data.forEach(p => {
                const date = new Date(p.created_at).toLocaleDateString();
                const expBadge = p.microbit_experience === 'avanzada' ? 'bg-danger' : (p.microbit_experience === 'intermedia' ? 'bg-warning text-dark' : 'bg-info text-dark');
                const paymentStatus = p.payment_status ? p.payment_status : 'sin_pago';
                const paymentBadge = paymentStatus === 'verificado' ? 'bg-success' : (paymentStatus === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary');

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${p.nationality}-${p.document_number}</strong></td>
                        <td>${p.full_name} ${p.last_name || ''}</td>
                        <td>${p.age} años</td>
                        <td>
                            <div class="small fw-bold">${p.education_level.toUpperCase()}</div>
                            <span class="badge ${expBadge}">${p.microbit_experience}</span>
                        </td>
                        <td>${date}</td>
                        <td><span class="badge ${paymentBadge}">${paymentStatus}</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary" onclick="editParticipant(${p.id})"><i class="bi bi-pencil"></i> Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteParticipant(${p.id})"><i class="bi bi-trash"></i> Quitar de evento</button>
                        </td>
                    </tr>
                `;
            });

            const pag = json.pagination;
            document.getElementById('paginationInfo').innerText = `Página ${pag.current_page} de ${pag.total_pages || 1} (Total: ${pag.total_records} registros)`;
            renderPagination(pag.current_page, pag.total_pages);

        } catch (e) {
            console.error(e);
        }
    }

    function renderPagination(current, total) {
        const controls = document.getElementById('paginationControls');
        controls.innerHTML = '';

        if (total <= 1) return;

        controls.innerHTML += `
            <li class="page-item ${current === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadParticipantsList(${current - 1})">Anterior</button>
            </li>
        `;

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - 1 && i <= current + 1)) {
                controls.innerHTML += `
                    <li class="page-item ${i === current ? 'active' : ''}">
                        <button class="page-link" onclick="loadParticipantsList(${i})">${i}</button>
                    </li>
                `;
            }
        }

        controls.innerHTML += `
            <li class="page-item ${current === total ? 'disabled' : ''}">
                <button class="page-link" onclick="loadParticipantsList(${current + 1})">Siguiente</button>
            </li>
        `;
    }

    async function saveParticipant(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSave');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

        const formData = new FormData(document.getElementById('participantForm'));

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                resetForm();
                loadParticipantsList(currentPage);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al procesar la solicitud.', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Guardar e Inscribir';
        }
    }


    async function editParticipant(id) {
        resetForm();
        const formData = new FormData();
        formData.append('action', 'get_participant');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            const p = json.data;

            // Llenar campos del participante
            Object.keys(p).forEach(key => {
                if (key === 'registration_data' || key === 'payment_data') return;
                const el = document.getElementById(key);
                if (el) el.value = p[key] ?? '';
            });

            // Llenar datos de inscripción
            if (p.registration_data) {
                document.getElementById('category_id').value = p.registration_data.category_id ?? '';
                document.getElementById('shirt_size').value = p.registration_data.shirt_size ?? 'M';
                document.getElementById('expectations').value = p.registration_data.expectations ?? '';
            }

            // Llenar datos de pago si existen
            if (p.payment_data) {
                document.getElementById('payment_method').value = p.payment_data.payment_method ?? '';
                document.getElementById('payment_phone').value = p.payment_data.payment_phone ?? '';
                document.getElementById('payment_bank').value = p.payment_data.payment_bank ?? '';
                document.getElementById('payment_date').value = p.payment_data.payment_date ?? '';
                document.getElementById('payment_reference').value = p.payment_data.payment_reference ?? '';
                document.getElementById('payment_amount_bs').value = p.payment_data.payment_amount_bs ?? '';
                document.getElementById('bcv_rate').value = p.payment_data.bcv_rate ?? '';

                // Guardar ruta existente en campo oculto
                const existingPath = p.payment_data.payment_proof_path || '';
                document.getElementById('existing_payment_proof_path').value = existingPath;

                // Mostrar enlace si existe
                const proofLinkDiv = document.getElementById('currentProofLink');
                if (existingPath) {
                    proofLinkDiv.innerHTML = `<a href="${existingPath}" target="_blank">Ver comprobante actual</a>`;
                } else {
                    proofLinkDiv.innerHTML = '';
                }
            }

            togglePaymentFields();

            document.getElementById('participant_id').value = p.id;
            calculateAge();

            document.getElementById('formTitle').innerText = 'Editando Participante: ' + p.full_name;
            document.getElementById('btnSave').innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar Inscripción';
            document.getElementById('btnCancelEdit').classList.remove('d-none');
            document.getElementById('formCard').classList.add('border-warning');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } catch (e) {
            showAlert('Error al obtener los datos del participante.', 'danger');
        }
    }

    async function deleteParticipant(id) {
        if (!confirm('¿Seguro que deseas quitar a este participante del evento actual? (Se eliminará también su pago asociado)')) return;

        const formData = new FormData();
        formData.append('action', 'delete_participant');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                loadParticipantsList(currentPage);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al intentar eliminar.', 'danger');
        }
    }

    function resetForm() {
        document.getElementById('participantForm').reset();
        document.getElementById('participant_id').value = '';
        document.getElementById('is_minor').value = '0';
        document.getElementById('guardianSection').classList.add('d-none');
        document.getElementById('event_id').value = <?= json_encode($currentEventId) ?>;
        document.getElementById('existing_payment_proof_path').value = '';
        document.getElementById('currentProofLink').innerHTML = '';
        togglePaymentFields();

        document.getElementById('formTitle').innerText = 'Registrar Nuevo Participante';
        document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Guardar e Inscribir';
        document.getElementById('btnCancelEdit').classList.add('d-none');
        document.getElementById('formCard').classList.remove('border-warning');
    }

    function showAlert(msg, type = 'success') {
        document.getElementById('alertContainer').innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        window.scrollTo(0, 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadParticipantsList(1);
        togglePaymentFields();
    });
</script>

<?php require_once 'footer.php'; ?>