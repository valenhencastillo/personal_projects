<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores
checkAccess(['Admin']);

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

/**
 * Genera la plantilla CSV sin la columna institution.
 */
function downloadTemplate()
{
    $headers = [
        'full_name',
        'last_name',
        'document_number',
        'birth_date',
        'gender',
        'email',
        'phone'
    ];

    $examples = [
        [
            'María',
            'Pérez',
            '12345678',
            '2010-05-15',
            'femenino',
            'maria.perez@example.com',
            '0412-1234567'
        ],
        [
            'Juan',
            'Rodríguez',
            '87654321',
            '2009-11-02',
            'masculino',
            'juan.rodriguez@example.com',
            '0424-7654321'
        ]
    ];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_participantes_colegio.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($examples as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

/**
 * Procesa el archivo CSV subido.
 * Ahora recibe el grado y el colegio seleccionados y los aplica a todos los estudiantes.
 */
function processUploadedFile($pdo, $eventId, $grade, $schoolId, $file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo.");
    }

    // Validar que el grado sea válido
    $allowedGrades = ['Primer año', 'Segundo año', 'Tercer año', 'Cuarto año', 'Quinto año'];
    if (!in_array($grade, $allowedGrades)) {
        throw new Exception("El grado seleccionado no es válido.");
    }

    // Obtener nombre del colegio
    $stmtSchool = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
    $stmtSchool->execute([$schoolId]);
    $schoolName = $stmtSchool->fetchColumn();
    if (!$schoolName) {
        throw new Exception("El colegio seleccionado no existe.");
    }

    // Buscar la categoría correspondiente al grado y evento
    $stmtCat = $pdo->prepare("SELECT id, capacity, registered_count FROM event_categories WHERE event_id = ? AND name = ?");
    $stmtCat->execute([$eventId, $grade]);
    $category = $stmtCat->fetch(PDO::FETCH_ASSOC);
    if (!$category) {
        throw new Exception("No existe categoría para el grado '$grade' en este evento.");
    }

    $tmpName = $file['tmp_name'];
    $handle = fopen($tmpName, 'r');
    if (!$handle) {
        throw new Exception("No se pudo abrir el archivo.");
    }

    // Leer encabezados (sin institution)
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        throw new Exception("El archivo está vacío.");
    }

    $map = [];
    foreach ($headers as $index => $header) {
        $header = trim($header);
        $map[$header] = $index;
    }

    // Columnas obligatorias
    $requiredColumns = ['full_name', 'last_name', 'document_number', 'birth_date', 'gender'];
    foreach ($requiredColumns as $col) {
        if (!isset($map[$col])) {
            fclose($handle);
            throw new Exception("Falta la columna obligatoria: $col");
        }
    }

    $successCount = 0;
    $errors = [];
    $rowNumber = 1;

    $pdo->beginTransaction();

    // Consultas preparadas
    $stmtCheckParticipant = $pdo->prepare("
        SELECT id FROM participants 
        WHERE document_type = 'cedula_escolar' AND document_number = ?
        LIMIT 1
    ");
    $stmtInsertParticipant = $pdo->prepare("
        INSERT INTO participants (
            full_name, last_name, document_type, document_number,
            nationality, birth_date, age, gender, email, phone,
            address, state, city, institution, education_level,
            grade, microbit_experience, is_minor,
            guardian_name, guardian_doc_type, guardian_document,
            guardian_email, guardian_phone
        ) VALUES (?, ?, 'cedula_escolar', ?, 'V', ?, ?, ?, ?, ?, '', '', '', ?, 'bachillerato', ?, 'ninguna', ?, NULL, 'cedula', NULL, NULL, NULL)
    ");

    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if (empty(array_filter($row))) continue;

        try {
            $fullName = trim($row[$map['full_name']] ?? '');
            $lastName = trim($row[$map['last_name']] ?? '');
            $docNumber = trim($row[$map['document_number']] ?? '');
            $birthDate = trim($row[$map['birth_date']] ?? '');
            $gender = strtolower(trim($row[$map['gender']] ?? ''));

            // Valores opcionales
            $email = trim($row[$map['email']] ?? '');
            $phone = trim($row[$map['phone']] ?? '');

            if (empty($fullName) || empty($docNumber) || empty($birthDate) || empty($gender)) {
                throw new Exception("Faltan campos obligatorios.");
            }

            if (!in_array($gender, ['masculino', 'femenino'])) {
                throw new Exception("Género inválido (debe ser 'masculino' o 'femenino').");
            }

            $dateObj = DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate) {
                throw new Exception("Fecha de nacimiento inválida (formato esperado: YYYY-MM-DD).");
            }

            $today = new DateTime();
            $age = $today->diff($dateObj)->y;

            // Verificar cupo
            if ($category['capacity'] !== null && $category['capacity'] > 0) {
                if ($category['registered_count'] >= $category['capacity']) {
                    throw new Exception("Cupos completos para la categoría '$grade'.");
                }
            }

            // Verificar duplicado en este evento
            $stmtCheck = $pdo->prepare("
                SELECT r.id FROM registrations r
                INNER JOIN participants p ON r.participant_id = p.id
                WHERE p.document_number = ? AND r.event_id = ?
            ");
            $stmtCheck->execute([$docNumber, $eventId]);
            if ($stmtCheck->fetchColumn()) {
                throw new Exception("El documento $docNumber ya está registrado en este evento.");
            }

            $isMinor = $age < 18 ? 1 : 0;

            // Reutilizar participante existente o insertar nuevo
            $stmtCheckParticipant->execute([$docNumber]);
            $participantId = $stmtCheckParticipant->fetchColumn();

            if (!$participantId) {
                $stmtInsertParticipant->execute([
                    $fullName,
                    $lastName,
                    $docNumber,
                    $birthDate,
                    $age,
                    $gender,
                    $email,
                    $phone,
                    $schoolName, // <-- colegio seleccionado
                    $grade,
                    $isMinor
                ]);
                $participantId = $pdo->lastInsertId();
            }

            // Generar número de registro
            $registrationNumber = 'HC' . $eventId . '-' . time() . rand(1000, 9999);

            // Insertar inscripción con talla por defecto 'M' y estado confirmado
            $stmtReg = $pdo->prepare("
                INSERT INTO registrations (
                    registration_number, participant_id, event_id, category_id,
                    shirt_size, expectations, authorization_doc_path,
                    image_rights_accepted, data_verified, status
                ) VALUES (?, ?, ?, ?, 'M', NULL, NULL, 1, 1, 'confirmado')
            ");
            $stmtReg->execute([
                $registrationNumber,
                $participantId,
                $eventId,
                $category['id']
            ]);

            // Incrementar contador de categoría
            if ($category['capacity'] !== null && $category['capacity'] > 0) {
                $stmtUpdate = $pdo->prepare("UPDATE event_categories SET registered_count = registered_count + 1 WHERE id = ?");
                $stmtUpdate->execute([$category['id']]);
                $category['registered_count']++;
            }

            $successCount++;
        } catch (Exception $e) {
            $errors[] = "Fila $rowNumber: " . $e->getMessage();
        }
    }

    fclose($handle);

    if ($successCount === 0 && !empty($errors)) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'No se pudo insertar ningún registro.', 'errors' => $errors];
    }

    $pdo->commit();
    return ['success' => true, 'message' => "Se insertaron $successCount participantes correctamente.", 'errors' => $errors];
}

// ==========================================
// PROCESAR ACCIONES POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'download_template') {
            downloadTemplate();
        } elseif ($_POST['action'] === 'upload_file') {
            $eventId = intval($_POST['event_id'] ?? 0);
            $grade = $_POST['grade'] ?? '';
            $schoolId = intval($_POST['school_id'] ?? 0);

            if (!$eventId) {
                $error = "Debe seleccionar un evento.";
            } elseif (empty($grade)) {
                $error = "Debe seleccionar el año escolar.";
            } elseif (!$schoolId) {
                $error = "Debe seleccionar un colegio.";
            } else {
                try {
                    $result = processUploadedFile($pdo, $eventId, $grade, $schoolId, $_FILES['csv_file']);
                    if ($result['success']) {
                        $success = $result['message'];
                        $rowErrors = $result['errors'];
                    } else {
                        $error = $result['message'];
                        $rowErrors = $result['errors'];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Obtener eventos privados
$eventsList = $pdo->query("SELECT id, name FROM events WHERE event_type = 'colegio_interno' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener colegios
$schoolsList = $pdo->query("SELECT id, name FROM schools ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'sidebar.php';
?>

<style>
    :root {
        --primary: #6C63FF;
        --dark: #2C3E50;
        --light-bg: #F5F7FA;
        --card-radius: 20px;
        --shadow: 0 8px 24px rgba(0,0,0,0.08);
        --success: #2ECC71;
        --danger: #E74C3C;
    }

    .massive-wrapper {
        padding: 1rem;
    }

    .massive-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .massive-header h2 {
        font-weight: 700;
        color: var(--dark);
        margin: 0;
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

    .btn-gradient-primary {
        background: linear-gradient(135deg, #6C63FF, #8B82FF);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(108, 99, 255, 0.4);
        color: white;
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

    .info-card {
        background: #f0f4ff;
        border-left: 4px solid var(--primary);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-top: 1rem;
    }
</style>

<div class="massive-wrapper">
    <div class="massive-header">
        <h2><i class="bi bi-people-fill"></i> Carga Masiva - Colegio Interno</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <div id="alertContainer">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($rowErrors) && !empty($rowErrors)): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Errores por fila:</h5>
            <ul class="mb-0">
                <?php foreach ($rowErrors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card-modern">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-upload"></i> Proceso de Carga</h5>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label fw-bold">1. Selecciona el Evento Privado (Colegio Interno):</label>
                <select id="event_select" class="form-select form-select-modern">
                    <option value="">-- Selecciona un evento --</option>
                    <?php foreach ($eventsList as $evt): ?>
                        <option value="<?= $evt['id'] ?>"><?= htmlspecialchars($evt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">2. Selecciona el Colegio:</label>
                <select id="school_select" class="form-select form-select-modern" required>
                    <option value="">-- Selecciona un colegio --</option>
                    <?php foreach ($schoolsList as $school): ?>
                        <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">3. Selecciona el Año Escolar:</label>
                <select id="grade_select" class="form-select form-select-modern" name="grade" form="uploadForm" required>
                    <option value="">-- Selecciona el año --</option>
                    <option value="Primer año">Primer año</option>
                    <option value="Segundo año">Segundo año</option>
                    <option value="Tercer año">Tercer año</option>
                    <option value="Cuarto año">Cuarto año</option>
                    <option value="Quinto año">Quinto año</option>
                </select>
                <small class="text-muted">Todos los estudiantes del archivo se asignarán a este año escolar y colegio.</small>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">4. Descarga la plantilla CSV:</label>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="download_template">
                    <button type="submit" class="btn btn-gradient-primary">
                        <i class="bi bi-download"></i> Descargar Plantilla CSV
                    </button>
                </form>
            </div>

            <div>
                <label class="form-label fw-bold">5. Sube el archivo relleno:</label>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="action" value="upload_file">
                    <input type="hidden" name="event_id" id="selected_event_id" value="">
                    <input type="hidden" name="school_id" id="selected_school_id" value="">
                    <input type="hidden" name="grade" id="selected_grade" value="">
                    <div class="input-group">
                        <input type="file" name="csv_file" class="form-control form-control-modern" accept=".csv" required>
                        <button type="submit" class="btn btn-gradient-success">
                            <i class="bi bi-upload"></i> Subir y Procesar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h5 class="fw-bold mb-2"><i class="bi bi-info-circle-fill"></i> Instrucciones y Notas</h5>
        <ul class="mb-0">
            <li>El año escolar y el colegio se seleccionan en el formulario y se aplican a todos los estudiantes.</li>
            <li>La fecha de nacimiento debe estar en formato <strong>YYYY-MM-DD</strong>.</li>
            <li>Para género use <strong>masculino</strong> o <strong>femenino</strong>.</li>
            <li>Los campos <strong>email</strong> y <strong>phone</strong> son opcionales.</li>
            <li>Si un documento ya está registrado en el evento, se omitirá y se reportará el error.</li>
            <li>Los participantes se inscriben con estado <strong>confirmado</strong> automáticamente (no requieren pago).</li>
            <li>La talla de franela no es necesaria; se asigna una por defecto.</li>
        </ul>
    </div>
</div>

<script>
    document.getElementById('event_select').addEventListener('change', function() {
        document.getElementById('selected_event_id').value = this.value;
    });

    document.getElementById('school_select').addEventListener('change', function() {
        document.getElementById('selected_school_id').value = this.value;
    });

    document.getElementById('grade_select').addEventListener('change', function() {
        document.getElementById('selected_grade').value = this.value;
    });
</script>

<?php require_once 'footer.php'; ?>