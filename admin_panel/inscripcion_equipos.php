<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores o personal autorizado
checkAccess(['Admin']);

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. OBTENER CATEGORÍAS DEL EVENTO
        if ($action === 'get_categories') {
            $eventId = (int)$_POST['event_id'];
            $stmt = $pdo->prepare("SELECT id, name FROM event_categories WHERE event_id = ?");
            $stmt->execute([$eventId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 2. BUSCAR ESTUDIANTE PREVIO (Para autocompletar si ya existe en otro evento)
        if ($action === 'search_student') {
            $documentNumber = trim($_POST['document_number']);
            // Buscamos el registro más reciente de este estudiante
            $stmt = $pdo->prepare("SELECT * FROM registrations WHERE document_number = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$documentNumber]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                echo json_encode(['status' => 'success', 'data' => $student]);
            } else {
                echo json_encode(['status' => 'not_found']);
            }
            exit;
        }

        // 3. GUARDAR INSCRIPCIÓN (INDIVIDUAL O EQUIPO)
        if ($action === 'save_team_registration') {
            $eventId = $_POST['event_id'];
            $categoryId = $_POST['category_id'];
            $teamName = trim($_POST['team_name']);
            $participants = $_POST['participants'] ?? [];

            if (empty($eventId) || empty($categoryId) || empty($participants)) {
                throw new Exception("Faltan datos obligatorios para la inscripción.");
            }

            // Usaremos el team_name como registration_number para agruparlos. Si está vacío, generamos uno único.
            $registrationNumber = !empty($teamName) ? strtoupper($teamName) : 'REG-' . time();

            $pdo->beginTransaction();

            $sql = "INSERT INTO registrations (
                registration_number, event_id, category_id, full_name, last_name, document_type, document_number,
                birth_date, age, gender, email, phone, address, state, city, institution, education_level,
                category, microbit_experience, shirt_size, is_minor, guardian_name, guardian_doc_type, guardian_document
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);

            foreach ($participants as $p) {
                $stmt->execute([
                    $registrationNumber,
                    $eventId,
                    $categoryId,
                    $p['full_name'],
                    $p['last_name'] ?? null,
                    $p['document_type'],
                    $p['document_number'],
                    $p['birth_date'],
                    $p['age'],
                    $p['gender'],
                    $p['email'],
                    $p['phone'],
                    $p['address'],
                    $p['state'],
                    $p['city'],
                    $p['institution'],
                    $p['education_level'],
                    $p['category'], // Junior/Senior
                    $p['microbit_experience'],
                    $p['shirt_size'],
                    $p['is_minor'] ?? 0,
                    $p['guardian_name'] ?? null,
                    $p['guardian_doc_type'] ?? null,
                    $p['guardian_document'] ?? null
                ]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Inscripción procesada con éxito. Equipo/Participantes registrados bajo el N°: ' . $registrationNumber]);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Obtener eventos activos
$eventsStmt = $pdo->query("SELECT id, name FROM events WHERE status = 1 OR status = 0 ORDER BY start_date DESC"); // Ajusta el WHERE según tus estados
$activeEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'sidebar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people-fill"></i> Inscripción a Competencia (Equipos / Individual)</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Volver al Panel</a>
    </div>

    <div id="alertContainer"></div>

    <form id="teamRegistrationForm" onsubmit="saveRegistration(event)">
        <input type="hidden" name="action" value="save_team_registration">

        <!-- DATOS DE LA COMPETENCIA -->
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Detalles del Evento y Equipo</h5>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Evento / Hackathon *</label>
                    <select name="event_id" id="event_id" class="form-select" required onchange="loadCategories(this.value)">
                        <option value="">-- Seleccione un Evento --</option>
                        <?php foreach ($activeEvents as $ev): ?>
                            <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Categoría *</label>
                    <select name="category_id" id="category_id" class="form-select" required disabled>
                        <option value="">-- Seleccione Categoría --</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nombre del Equipo</label>
                    <input type="text" name="team_name" class="form-control" placeholder="Ej: Tech Innovators (Opcional si es individual)">
                    <small class="text-muted">Si se registran varios participantes, se agruparán bajo este nombre.</small>
                </div>
            </div>
        </div>

        <!-- CONTENEDOR DE PARTICIPANTES -->
        <h4 class="mb-3 text-secondary border-bottom pb-2">Integrantes del Equipo</h4>
        <div id="participantsContainer">
            <!-- Los formularios de participantes se inyectan aquí vía JS -->
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
            <button type="button" class="btn btn-outline-primary" onclick="addParticipantCard()">
                <i class="bi bi-person-plus"></i> Agregar Estudiante al Equipo
            </button>
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="bi bi-check-circle"></i> Guardar Inscripción Completa
            </button>
        </div>
    </form>
</div>

<script>
    let participantCount = 0;

    // Cargar categorías al seleccionar un evento
    async function loadCategories(eventId) {
        const catSelect = document.getElementById('category_id');
        catSelect.innerHTML = '<option value="">Cargando...</option>';
        catSelect.disabled = true;

        if (!eventId) {
            catSelect.innerHTML = '<option value="">-- Seleccione Categoría --</option>';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get_categories');
        formData.append('event_id', eventId);

        try {
            const res = await fetch('', { method: 'POST', body: formData });
            const json = await res.json();
            
            catSelect.innerHTML = '<option value="">-- Seleccione Categoría --</option>';
            json.data.forEach(cat => {
                catSelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
            });
            catSelect.disabled = false;
        } catch (e) {
            console.error(e);
            catSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    // Agregar nueva tarjeta de participante
    function addParticipantCard() {
        const idx = participantCount++;
        const html = `
            <div class="card shadow-sm mb-4" id="participant_card_${idx}">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Estudiante ${idx + 1}</h6>
                    ${idx > 0 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeParticipantCard(${idx})"><i class="bi bi-x"></i> Remover</button>` : ''}
                </div>
                <div class="card-body">
                    <!-- Buscador inteligente -->
                    <div class="row g-2 mb-3 align-items-end p-2 bg-light rounded border">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Autocompletar datos (Opcional)</label>
                            <input type="text" id="search_doc_${idx}" class="form-control form-control-sm" placeholder="Ingrese Cédula para buscar...">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="searchStudentData(${idx})">Buscar <i class="bi bi-search"></i></button>
                        </div>
                        <div class="col-12" id="search_msg_${idx}"></div>
                    </div>

                    <!-- Formulario de Datos -->
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nombres *</label>
                            <input type="text" name="participants[${idx}][full_name]" id="fname_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Apellidos</label>
                            <input type="text" name="participants[${idx}][last_name]" id="lname_${idx}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Tipo Doc. *</label>
                            <select name="participants[${idx}][document_type]" id="dtype_${idx}" class="form-select" required>
                                <option value="cedula">Cédula</option>
                                <option value="pasaporte">Pasaporte</option>
                                <option value="cedula_escolar">C. Escolar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Documento *</label>
                            <input type="text" name="participants[${idx}][document_number]" id="dnum_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">F. Nacimiento *</label>
                            <input type="date" name="participants[${idx}][birth_date]" id="bdate_${idx}" class="form-control" required onchange="calcAge(${idx})">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">Edad</label>
                            <input type="number" name="participants[${idx}][age]" id="age_${idx}" class="form-control bg-light" readonly tabindex="-1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Género *</label>
                            <select name="participants[${idx}][gender]" id="gender_${idx}" class="form-select" required>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Correo *</label>
                            <input type="email" name="participants[${idx}][email]" id="email_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Teléfono *</label>
                            <input type="tel" name="participants[${idx}][phone]" id="phone_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Estado *</label>
                            <input type="text" name="participants[${idx}][state]" id="state_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ciudad *</label>
                            <input type="text" name="participants[${idx}][city]" id="city_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dirección *</label>
                            <input type="text" name="participants[${idx}][address]" id="address_${idx}" class="form-control" required>
                        </div>

                        <!-- Perfil Académico y Competencia -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Institución *</label>
                            <input type="text" name="participants[${idx}][institution]" id="inst_${idx}" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Nivel Edu. *</label>
                            <select name="participants[${idx}][education_level]" id="edlvl_${idx}" class="form-select" required>
                                <option value="primaria">Primaria</option>
                                <option value="bachillerato">Bachillerato</option>
                                <option value="universidad">Universidad</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cat. Comp. *</label>
                            <select name="participants[${idx}][category]" id="cat_${idx}" class="form-select" required>
                                <option value="Junior">Junior</option>
                                <option value="Senior">Senior</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Exp. Microbit *</label>
                            <select name="participants[${idx}][microbit_experience]" id="mexp_${idx}" class="form-select" required>
                                <option value="ninguna">Ninguna</option>
                                <option value="basica">Básica</option>
                                <option value="intermedia">Intermedia</option>
                                <option value="avanzada">Avanzada</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Talla Camisa *</label>
                            <select name="participants[${idx}][shirt_size]" id="shirt_${idx}" class="form-select" required>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="participants[${idx}][is_minor]" id="isminor_${idx}" value="0">
                </div>
            </div>
        `;
        document.getElementById('participantsContainer').insertAdjacentHTML('beforeend', html);
    }

    function removeParticipantCard(idx) {
        document.getElementById(`participant_card_${idx}`).remove();
    }

    // Calcular edad para el participante X
    function calcAge(idx) {
        const bdate = document.getElementById(`bdate_${idx}`).value;
        if (!bdate) return;
        const bd = new Date(bdate);
        const today = new Date();
        let age = today.getFullYear() - bd.getFullYear();
        if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) {
            age--;
        }
        document.getElementById(`age_${idx}`).value = age;
        document.getElementById(`isminor_${idx}`).value = age < 18 ? 1 : 0;
    }

    // Buscar estudiante en la BD para autocompletar
    async function searchStudentData(idx) {
        const docNum = document.getElementById(`search_doc_${idx}`).value.trim();
        const msgDiv = document.getElementById(`search_msg_${idx}`);
        
        if (docNum === '') {
            msgDiv.innerHTML = '<span class="text-danger small">Ingrese un documento válido.</span>';
            return;
        }

        msgDiv.innerHTML = '<span class="text-info small">Buscando...</span>';
        const formData = new FormData();
        formData.append('action', 'search_student');
        formData.append('document_number', docNum);

        try {
            const res = await fetch('', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.status === 'success') {
                const p = json.data;
                document.getElementById(`fname_${idx}`).value = p.full_name || '';
                document.getElementById(`lname_${idx}`).value = p.last_name || '';
                document.getElementById(`dtype_${idx}`).value = p.document_type || 'cedula';
                document.getElementById(`dnum_${idx}`).value = p.document_number || '';
                document.getElementById(`bdate_${idx}`).value = p.birth_date || '';
                document.getElementById(`gender_${idx}`).value = p.gender || 'masculino';
                document.getElementById(`email_${idx}`).value = p.email || '';
                document.getElementById(`phone_${idx}`).value = p.phone || '';
                document.getElementById(`state_${idx}`).value = p.state || '';
                document.getElementById(`city_${idx}`).value = p.city || '';
                document.getElementById(`address_${idx}`).value = p.address || '';
                document.getElementById(`inst_${idx}`).value = p.institution || '';
                document.getElementById(`edlvl_${idx}`).value = p.education_level || 'bachillerato';
                document.getElementById(`cat_${idx}`).value = p.category || 'Junior';
                document.getElementById(`mexp_${idx}`).value = p.microbit_experience || 'ninguna';
                document.getElementById(`shirt_${idx}`).value = p.shirt_size || 'M';
                
                calcAge(idx);
                msgDiv.innerHTML = '<span class="text-success small fw-bold"><i class="bi bi-check"></i> Datos cargados con éxito.</span>';
            } else {
                msgDiv.innerHTML = '<span class="text-warning small"><i class="bi bi-exclamation-circle"></i> Estudiante no encontrado en registros previos.</span>';
            }
        } catch (e) {
            msgDiv.innerHTML = '<span class="text-danger small">Error en la búsqueda.</span>';
        }
    }

    // Enviar el formulario maestro
    async function saveRegistration(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('teamRegistrationForm'));

        try {
            const res = await fetch('', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                // Reiniciar formulario manteniendo 1 solo participante vacío
                document.getElementById('teamRegistrationForm').reset();
                document.getElementById('participantsContainer').innerHTML = '';
                participantCount = 0;
                addParticipantCard();
                window.scrollTo(0, 0);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al procesar la solicitud al servidor.', 'danger');
        }
    }

    function showAlert(msg, type = 'success') {
        document.getElementById('alertContainer').innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        window.scrollTo(0, 0);
    }

    // Inicializar con un participante por defecto al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        addParticipantCard();
    });
</script>

<?php require_once 'footer.php'; ?>