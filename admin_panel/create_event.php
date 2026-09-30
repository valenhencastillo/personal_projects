<?php
require_once 'database.php';

// Verificación de seguridad de administrador y manejo de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit();
    }
    header("Location: login.php");
    exit();
}

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. LISTAR EVENTOS
        if ($action === 'list_events') {
            $stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $events]);
            exit;
        }

        // 2. OBTENER DETALLE DE UN EVENTO
        if ($action === 'get_event') {
            $id = $_POST['id'];

            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($event) {
                $stmtCat = $pdo->prepare("SELECT * FROM event_categories WHERE event_id = ?");
                $stmtCat->execute([$id]);
                $event['categories'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

                foreach ($event['categories'] as &$cat) {
                    $stmtChal = $pdo->prepare("SELECT * FROM challenges WHERE category_id = ? AND event_id = ?");
                    $stmtChal->execute([$cat['id'], $id]);
                    $cat['challenges'] = $stmtChal->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($cat['challenges'] as &$chal) {
                        $stmtItem = $pdo->prepare("SELECT * FROM challenge_items WHERE challenge_id = ?");
                        $stmtItem->execute([$chal['id']]);
                        $chal['items'] = $stmtItem->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            }
            echo json_encode(['status' => 'success', 'data' => $event]);
            exit;
        }

        // 3. GUARDAR EVENTO (Crear o Editar)
        if ($action === 'save_event') {
            $eventId = $_POST['event_id'] ?? '';
            $name = trim($_POST['event_name']);
            $description = trim($_POST['event_description']);
            $status = isset($_POST['event_status']) ? 1 : 0;
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $event_type = $_POST['event_type'] ?? 'publico';
            $registration_start_date = $_POST['registration_start_date'] ?? null;
            $registration_end_date = $_POST['registration_end_date'] ?? null;

            $pdo->beginTransaction();

            // ⚠️ Validación: solo un evento público activo a la vez
            if ($status === 1 && $event_type === 'publico') {
                $stmtCheck = $pdo->prepare("SELECT id, name FROM events WHERE event_type = 'publico' AND status = 1 AND id != ? LIMIT 1");
                $stmtCheck->execute([$eventId ? $eventId : 0]);
                $existingActivePublic = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existingActivePublic) {
                    throw new Exception("Ya existe un evento público activo: '{$existingActivePublic['name']}'. Debe cerrarlo antes de crear o activar otro evento público.");
                }
            }

            if (empty($eventId)) {
                // ============ CREAR ============
                $stmtEvent = $pdo->prepare("INSERT INTO events (name, description, status, event_type, registration_start_date, registration_end_date, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtEvent->execute([$name, $description, $status, $event_type, $registration_start_date, $registration_end_date, $start_date, $end_date]);
                $eventId = $pdo->lastInsertId();

                // Insertar categorías y retos
                if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
                    foreach ($_POST['categories'] as $cat) {
                        if (empty(trim($cat['name']))) continue;

                        $capacity = null;
                        if (isset($cat['has_capacity']) && $cat['has_capacity'] === '1') {
                            $capacity = !empty($cat['capacity']) ? intval($cat['capacity']) : null;
                        }

                        $stmtCat = $pdo->prepare("INSERT INTO event_categories (event_id, name, description, capacity) VALUES (?, ?, ?, ?)");
                        $stmtCat->execute([$eventId, trim($cat['name']), trim($cat['description'] ?? ''), $capacity]);
                        $categoryId = $pdo->lastInsertId();

                        if (!empty($cat['challenges']) && is_array($cat['challenges'])) {
                            foreach ($cat['challenges'] as $chal) {
                                if (empty(trim($chal['title']))) continue;

                                $stmtChal = $pdo->prepare("INSERT INTO challenges (category_id, event_id, title, description) VALUES (?, ?, ?, ?)");
                                $stmtChal->execute([$categoryId, $eventId, trim($chal['title']), trim($chal['description'] ?? '')]);
                                $challengeId = $pdo->lastInsertId();

                                if (!empty($chal['items']) && is_array($chal['items'])) {
                                    foreach ($chal['items'] as $item) {
                                        // Soporta tanto string simple como array asociativo
                                        if (is_array($item)) {
                                            $itemText = $item['description'] ?? '';
                                            $itemId = $item['item_id'] ?? 0;
                                        } else {
                                            $itemText = $item;
                                            $itemId = 0; // En creación no existe id
                                        }

                                        $itemText = trim($itemText);
                                        if ($itemText === '') continue;

                                        $stmtItem = $pdo->prepare("INSERT INTO challenge_items (challenge_id, description) VALUES (?, ?)");
                                        $stmtItem->execute([$challengeId, $itemText]);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // ============ ACTUALIZAR ============
                // Actualizar datos generales del evento
                $stmtEvent = $pdo->prepare("UPDATE events SET name=?, description=?, status=?, event_type=?, registration_start_date=?, registration_end_date=?, start_date=?, end_date=? WHERE id=?");
                $stmtEvent->execute([$name, $description, $status, $event_type, $registration_start_date, $registration_end_date, $start_date, $end_date, $eventId]);

                // Procesar categorías (actualizar capacidades y sincronizar retos/items)
                if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
                    foreach ($_POST['categories'] as $cat) {
                        if (empty(trim($cat['name']))) continue;

                        // Buscar categoría existente por nombre y evento
                        $stmtFindCat = $pdo->prepare("SELECT id FROM event_categories WHERE event_id = ? AND name = ? LIMIT 1");
                        $stmtFindCat->execute([$eventId, trim($cat['name'])]);
                        $categoryId = $stmtFindCat->fetchColumn();

                        if (!$categoryId) {
                            continue; // No debería faltar, pero por seguridad
                        }

                        // Actualizar capacidad y descripción
                        $capacity = null;
                        if (isset($cat['has_capacity']) && $cat['has_capacity'] === '1') {
                            $capacity = !empty($cat['capacity']) ? intval($cat['capacity']) : null;
                        }

                        // Validar capacidad si es limitada
                        if ($capacity !== null && $capacity > 0) {
                            $stmtActive = $pdo->prepare("
                                SELECT COUNT(*) 
                                FROM registrations r
                                LEFT JOIN payments p ON r.id = p.registration_id
                                WHERE r.category_id = ? AND r.event_id = ?
                                AND (p.status IS NULL OR p.status != 'rechazado')
                            ");
                            $stmtActive->execute([$categoryId, $eventId]);
                            $activeCount = $stmtActive->fetchColumn();

                            if ($capacity < $activeCount) {
                                throw new Exception("No se puede reducir la capacidad de '{$cat['name']}' a $capacity porque hay {$activeCount} inscripciones activas.");
                            }
                        }

                        $stmtUpdateCat = $pdo->prepare("UPDATE event_categories SET capacity = ?, description = ? WHERE id = ?");
                        $stmtUpdateCat->execute([$capacity, trim($cat['description'] ?? ''), $categoryId]);

                        // Sincronizar retos
                        if (!empty($cat['challenges']) && is_array($cat['challenges'])) {
                            foreach ($cat['challenges'] as $chal) {
                                if (empty(trim($chal['title']))) continue;

                                $challengeId = isset($chal['challenge_id']) ? intval($chal['challenge_id']) : 0;

                                if ($challengeId > 0) {
                                    // Actualizar reto existente (solo si pertenece a esta categoría y evento)
                                    $stmtUpdateChal = $pdo->prepare("UPDATE challenges SET title = ?, description = ? WHERE id = ? AND category_id = ? AND event_id = ?");
                                    $stmtUpdateChal->execute([trim($chal['title']), trim($chal['description'] ?? ''), $challengeId, $categoryId, $eventId]);
                                } else {
                                    // Insertar nuevo reto
                                    $stmtInsertChal = $pdo->prepare("INSERT INTO challenges (category_id, event_id, title, description) VALUES (?, ?, ?, ?)");
                                    $stmtInsertChal->execute([$categoryId, $eventId, trim($chal['title']), trim($chal['description'] ?? '')]);
                                    $challengeId = $pdo->lastInsertId();
                                }

                                // Sincronizar ítems
                                if (!empty($chal['items']) && is_array($chal['items'])) {
                                    foreach ($chal['items'] as $item) {
                                        $itemText = is_array($item) ? ($item['description'] ?? '') : $item;
                                        $itemId = is_array($item) ? ($item['item_id'] ?? 0) : 0;

                                        if (empty(trim($itemText))) continue;

                                        if ($itemId > 0) {
                                            // Actualizar ítem existente
                                            $stmtUpdateItem = $pdo->prepare("UPDATE challenge_items SET description = ? WHERE id = ? AND challenge_id = ?");
                                            $stmtUpdateItem->execute([trim($itemText), $itemId, $challengeId]);
                                        } else {
                                            // Insertar nuevo ítem
                                            $stmtInsertItem = $pdo->prepare("INSERT INTO challenge_items (challenge_id, description) VALUES (?, ?)");
                                            $stmtInsertItem->execute([$challengeId, trim($itemText)]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // No se eliminan retos/ítems no enviados; se conservan para no afectar evaluaciones
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => empty($_POST['event_id']) ? 'Evento creado exitosamente.' : 'Evento actualizado exitosamente.']);
            exit;
        }

        // NUEVA ACCIÓN: CERRAR / ABRIR EVENTO
        if ($action === 'toggle_status') {
            $eventId = intval($_POST['event_id'] ?? 0);
            $newStatus = intval($_POST['status'] ?? 0); // 1 = activo, 0 = inactivo

            if (!$eventId) {
                throw new Exception('Evento no especificado.');
            }

            // Obtener tipo de evento
            $stmtEventType = $pdo->prepare("SELECT event_type FROM events WHERE id = ?");
            $stmtEventType->execute([$eventId]);
            $eventType = $stmtEventType->fetchColumn();

            if (!$eventType) {
                throw new Exception('Evento no encontrado.');
            }

            // ⚠️ Validación al intentar activar un evento público
            if ($newStatus === 1 && $eventType === 'publico') {
                $stmtCheck = $pdo->prepare("SELECT id, name FROM events WHERE event_type = 'publico' AND status = 1 AND id != ? LIMIT 1");
                $stmtCheck->execute([$eventId]);
                $existingActivePublic = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existingActivePublic) {
                    throw new Exception("Ya existe un evento público activo: '{$existingActivePublic['name']}'. Debe cerrarlo antes de activar otro evento público.");
                }
            }

            $stmtUpdate = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $eventId]);

            if ($newStatus === 0 && isset($_SESSION['current_event_id']) && $_SESSION['current_event_id'] == $eventId) {
                unset($_SESSION['current_event_id']);
            }

            echo json_encode([
                'status' => 'success',
                'message' => ($newStatus === 1) ? 'Evento activado correctamente.' : 'Evento cerrado correctamente.'
            ]);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
// ==========================================

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

    .events-wrapper {
        padding: 1rem;
    }

    .events-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .events-header h2 {
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

    .category-block {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-bottom: 1rem;
    }

    .category-block .card-header {
        background: rgba(108, 99, 255, 0.1);
        color: var(--primary);
        border: none;
        font-weight: 600;
    }

    .challenge-block {
        border-radius: 12px;
        background: #fafafa;
        border: 1px solid #e0e0e0;
        margin-bottom: 0.75rem;
    }

    .list-card {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .list-card .card-header {
        background: var(--dark);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
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
    }

    .table-modern td {
        vertical-align: middle;
    }
</style>

<div class="events-wrapper">
    <div class="events-header">
        <h2><i class="bi bi-trophy"></i> Gestión de Hackathons</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <!-- Contenedor de Alertas JS -->
    <div id="alertContainer"></div>

    <!-- ================= FORMULARIO (Arriba) ================= -->
    <div class="card-modern">
        <form id="eventForm" onsubmit="saveEvent(event)">
            <input type="hidden" name="action" value="save_event">
            <input type="hidden" name="event_id" id="event_id" value="">

            <!-- DATOS DEL EVENTO -->
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="formTitle">1. Información del Nuevo Evento</h5>
                <button type="button" class="btn btn-sm btn-outline-light d-none" id="btnCancelEdit" onclick="resetForm()">Cancelar Edición</button>
            </div>
            <div class="card-body" id="formCard">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre del Hackathon *</label>
                        <input type="text" name="event_name" id="event_name" class="form-control form-control-modern" placeholder="Ej: Hackathon 2026" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo de Evento *</label>
                        <select name="event_type" id="event_type" class="form-select form-select-modern" required>
                            <option value="publico">Público</option>
                            <option value="colegio_interno">Colegio Interno</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado del Evento</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="event_status" name="event_status" value="1" checked>
                            <label class="form-check-label fw-bold text-primary" for="event_status">Activo</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha Inicio *</label>
                        <input type="date" name="start_date" id="start_date" class="form-control form-control-modern" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha Cierre *</label>
                        <input type="date" name="end_date" id="end_date" class="form-control form-control-modern" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Inicio de Registro</label>
                        <input type="datetime-local" name="registration_start_date" id="registration_start_date" class="form-control form-control-modern">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fin de Registro</label>
                        <input type="datetime-local" name="registration_end_date" id="registration_end_date" class="form-control form-control-modern">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descripción / Bases del Evento</label>
                        <textarea name="event_description" id="event_description" class="form-control form-control-modern" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- CATEGORÍAS Y RETOS -->
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">2. Categorías, Retos e Items</h5>
                <span class="small text-light opacity-75">Las categorías se crean automáticamente según el tipo de evento.</span>
            </div>
            <div class="card-body">
                <div id="categoriesContainer"></div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-gradient-success btn-lg" id="btnSave">
                        <i class="bi bi-save"></i> Guardar Hackathon Completamente
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================= LISTA DE EVENTOS (Abajo) ================= -->
    <div class="list-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Eventos Existentes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-modern" id="eventsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre y Fechas</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cargado por AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let catCounter = 0;

    // ==========================================
    // GENERAR CATEGORÍAS PREDEFINIDAS SEGÚN TIPO DE EVENTO
    // ==========================================
    function getCategoryNamesForType(eventType) {
        if (eventType === 'publico') {
            return ['Junior', 'Senior'];
        } else if (eventType === 'colegio_interno') {
            return ['Primer año', 'Segundo año', 'Tercer año', 'Cuarto año', 'Quinto año'];
        }
        return [];
    }

    function loadDefaultCategories() {
        const eventType = document.getElementById('event_type').value;
        const container = document.getElementById('categoriesContainer');
        container.innerHTML = '';
        catCounter = 0;

        const categoryNames = getCategoryNamesForType(eventType);
        categoryNames.forEach(name => {
            addCategory({
                name: name,
                description: '',
                capacity: null
            });
        });
    }

    // ==========================================
    // CONSTRUCCIÓN DE LA INTERFAZ
    // ==========================================
    function addCategory(catData = null) {
        const cIdx = catCounter++;
        const name = catData ? catData.name : '';
        const desc = catData ? (catData.description || '') : '';
        const capacity = catData ? (catData.capacity || '') : '';
        const hasCapacity = catData && catData.capacity !== null && catData.capacity !== undefined && catData.capacity !== '';

        const catHtml = `
        <div class="card border-primary shadow-sm mb-4 category-block">
            <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-white">${name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.category-block').remove()">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </div>
            <div class="card-body">
                <input type="hidden" name="categories[${cIdx}][name]" value="${name}">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <input type="text" name="categories[${cIdx}][description]" value="${desc}" class="form-control" placeholder="Descripción (opcional)">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input capacity-toggle" type="checkbox" role="switch" 
                                   id="cap_toggle_${cIdx}" name="categories[${cIdx}][has_capacity]" value="1"
                                   ${hasCapacity ? 'checked' : ''} 
                                   onchange="toggleCapacityField(${cIdx})">
                            <label class="form-check-label" for="cap_toggle_${cIdx}">
                                <strong>Limitar cupos</strong>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 capacity-field" id="capacity_field_${cIdx}" style="${hasCapacity ? '' : 'display:none;'}">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-people"></i></span>
                            <input type="number" name="categories[${cIdx}][capacity]" value="${capacity}" 
                                   class="form-control capacity-input" placeholder="N° de cupos" min="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info py-2 mb-0" style="font-size: 0.85rem;">
                            <i class="bi bi-info-circle"></i> 
                            <span id="capacity_text_${cIdx}">${hasCapacity ? `Cupos limitados: ${capacity}` : 'Cupos ilimitados'}</span>
                        </div>
                    </div>
                </div>
                <div class="ps-3 border-start border-3 border-primary ms-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold text-secondary text-uppercase">Retos asignados</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addChallenge(${cIdx})">
                            <i class="bi bi-plus"></i> Agregar Reto
                        </button>
                    </div>
                    <div id="chals_${cIdx}"></div>
                </div>
            </div>
        </div>
        `;
        document.getElementById('categoriesContainer').insertAdjacentHTML('beforeend', catHtml);
        return cIdx;
    }

    function toggleCapacityField(cIdx) {
        const toggle = document.getElementById(`cap_toggle_${cIdx}`);
        const field = document.getElementById(`capacity_field_${cIdx}`);
        const text = document.getElementById(`capacity_text_${cIdx}`);
        const input = field.querySelector('.capacity-input');

        if (toggle.checked) {
            field.style.display = '';
            if (input.value) {
                text.textContent = `Cupos limitados: ${input.value}`;
            } else {
                text.textContent = 'Configura los cupos';
            }
        } else {
            field.style.display = 'none';
            text.textContent = 'Cupos ilimitados';
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('capacity-input')) {
            const cIdx = e.target.name.match(/\[(\d+)\]/)[1];
            const text = document.getElementById(`capacity_text_${cIdx}`);
            if (text) {
                text.textContent = e.target.value ? `Cupos limitados: ${e.target.value}` : 'Configura los cupos';
            }
        }
    });

    function addChallenge(cIdx, chalData = null) {
        // ID único para el contenedor del reto
        const chalId = Date.now().toString() + Math.floor(Math.random() * 1000);
        const title = chalData ? chalData.title : '';
        const desc = chalData ? (chalData.description || '') : '';
        const challengeId = chalData && chalData.id ? chalData.id : '';

        const chalHtml = `
        <div class="card border-secondary mb-3 challenge-block bg-light">
            <div class="card-body p-3">
                <input type="hidden" name="categories[${cIdx}][challenges][${chalId}][challenge_id]" value="${challengeId}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-secondary">Reto</span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.challenge-block').remove()">Eliminar Reto</button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" name="categories[${cIdx}][challenges][${chalId}][title]" value="${title}" class="form-control form-control-sm" placeholder="Título *" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="categories[${cIdx}][challenges][${chalId}][description]" value="${desc}" class="form-control form-control-sm" placeholder="Descripción">
                    </div>
                </div>
                <div class="ps-3 border-start border-2 border-secondary ms-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-bold text-muted" style="font-size: 0.75rem;">CRITERIOS / ÍTEMS DE EVALUACIÓN</small>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0" onclick="addItem(${cIdx}, '${chalId}')">+ Añadir Ítem</button>
                    </div>
                    <div id="items_${chalId}"></div>
                </div>
            </div>
        </div>
        `;
        document.getElementById(`chals_${cIdx}`).insertAdjacentHTML('beforeend', chalHtml);

        if (chalData && chalData.items && chalData.items.length > 0) {
            chalData.items.forEach(item => {
                addItem(cIdx, chalId, item.description, item.id);
            });
        } else if (!chalData) {
            addItem(cIdx, chalId);
        }
    }

    function addItem(cIdx, chalId, itemValue = '', itemId = '') {
        // Índice único para el ítem
        const itemIndex = Date.now().toString() + Math.floor(Math.random() * 1000);
        const safeValue = itemValue.replace(/"/g, '&quot;');
        const itemHtml = `
        <div class="input-group input-group-sm mb-1 item-row">
            <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
            <input type="text" name="categories[${cIdx}][challenges][${chalId}][items][${itemIndex}][description]" value="${safeValue}" class="form-control" placeholder="Ej: ¿El robot enciende las luces?" required>
            <input type="hidden" name="categories[${cIdx}][challenges][${chalId}][items][${itemIndex}][item_id]" value="${itemId}">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.item-row').remove()">&times;</button>
        </div>
        `;
        document.getElementById(`items_${chalId}`).insertAdjacentHTML('beforeend', itemHtml);
    }

    function showAlert(msg, type = 'success') {
        const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        document.getElementById('alertContainer').innerHTML = alertHtml;
        window.scrollTo(0, 0);
    }

    // ==========================================
    // LOGICA AJAX
    // ==========================================
    async function loadEventsList() {
        const formData = new FormData();
        formData.append('action', 'list_events');

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            const tbody = document.querySelector('#eventsTable tbody');
            tbody.innerHTML = '';

            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay eventos registrados en el sistema</td></tr>';
                return;
            }

            json.data.forEach(ev => {
                const badge = ev.status == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
                const typeBadge = ev.event_type === 'colegio_interno' ?
                    '<span class="badge bg-warning text-dark">Colegio Interno</span>' :
                    '<span class="badge bg-info">Público</span>';

                const toggleBtn = ev.status == 1 ?
                    `<button class="btn btn-sm btn-warning me-1" onclick="toggleEventStatus(${ev.id}, 1)" title="Cerrar evento"><i class="bi bi-lock-fill"></i> Cerrar</button>` :
                    `<button class="btn btn-sm btn-success me-1" onclick="toggleEventStatus(${ev.id}, 0)" title="Abrir evento"><i class="bi bi-unlock-fill"></i> Abrir</button>`;

                tbody.innerHTML += `
                    <tr>
                        <td><strong>#${ev.id}</strong></td>
                        <td>
                            <strong>${ev.name}</strong><br>
                            <small class="text-muted">Del ${ev.start_date} al ${ev.end_date}</small>
                            ${ev.registration_start_date ? `<br><small class="text-muted">Registro: ${formatDate(ev.registration_start_date)} - ${formatDate(ev.registration_end_date)}</small>` : ''}
                        </td>
                        <td>${typeBadge}</td>
                        <td>${badge}</td>
                        <td class="text-end">
                            <a href="ver_participantes.php?event_id=${ev.id}" class="btn btn-sm btn-info text-white me-1" title="Ver Equipos y Estudiantes">
                                <i class="bi bi-people-fill"></i> Participantes
                            </a>
                            ${toggleBtn}
                            <button class="btn btn-sm btn-primary" onclick="editEvent(${ev.id})" title="Editar Evento">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        </td>
                    </tr>
                `;
            });
        } catch (error) {
            console.error(error);
        }
    }

    function formatDate(dateString) {
        if (!dateString) return 'No definido';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    async function editEvent(id) {
        resetForm();

        const formData = new FormData();
        formData.append('action', 'get_event');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            const ev = json.data;

            document.getElementById('formTitle').innerText = 'Editando Evento: ' + ev.name;
            document.getElementById('btnSave').innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar Cambios del Hackathon';
            document.getElementById('formCard').classList.add('border-warning');
            document.getElementById('btnCancelEdit').classList.remove('d-none');

            document.getElementById('event_id').value = ev.id;
            document.getElementById('event_name').value = ev.name;
            document.getElementById('event_description').value = ev.description;
            document.getElementById('start_date').value = ev.start_date;
            document.getElementById('end_date').value = ev.end_date;
            document.getElementById('event_status').checked = (ev.status == 1);
            document.getElementById('event_type').value = ev.event_type || 'publico';

            if (ev.registration_start_date) {
                document.getElementById('registration_start_date').value = ev.registration_start_date.replace(' ', 'T');
            }
            if (ev.registration_end_date) {
                document.getElementById('registration_end_date').value = ev.registration_end_date.replace(' ', 'T');
            }

            // Cargar categorías predeterminadas según el tipo
            loadDefaultCategories();

            // Ahora rellenar los datos de cada categoría existente
            if (ev.categories && ev.categories.length > 0) {
                ev.categories.forEach(cat => {
                    // Encontrar la categoría correspondiente en el DOM (por nombre)
                    const categoryBlocks = document.querySelectorAll('.category-block');
                    let targetBlock = null;
                    categoryBlocks.forEach(block => {
                        const nameInput = block.querySelector('input[name$="[name]"]');
                        if (nameInput && nameInput.value === cat.name) {
                            targetBlock = block;
                        }
                    });

                    if (targetBlock) {
                        // Obtener el índice de esta categoría
                        const cIdx = targetBlock.querySelector('input[name$="[name]"]').name.match(/\[(\d+)\]/)[1];

                        // Establecer capacidad
                        const hasCap = cat.capacity !== null && cat.capacity !== undefined && cat.capacity !== '';
                        const toggle = targetBlock.querySelector('.capacity-toggle');
                        const capacityInput = targetBlock.querySelector('.capacity-input');
                        const capacityField = targetBlock.querySelector('.capacity-field');
                        const capacityText = document.getElementById(`capacity_text_${cIdx}`);

                        if (toggle) {
                            toggle.checked = hasCap;
                        }
                        if (capacityInput) {
                            capacityInput.value = hasCap ? cat.capacity : '';
                        }
                        if (capacityField) {
                            capacityField.style.display = hasCap ? '' : 'none';
                        }
                        if (capacityText) {
                            capacityText.textContent = hasCap ? `Cupos limitados: ${cat.capacity}` : 'Cupos ilimitados';
                        }

                        // Añadir retos
                        if (cat.challenges && cat.challenges.length > 0) {
                            cat.challenges.forEach(chal => {
                                addChallenge(cIdx, chal);
                            });
                        }
                    }
                });
            }

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } catch (error) {
            showAlert('Error al cargar el evento', 'danger');
        }
    }

    // Función para mostrar mensajes guardados en sessionStorage
    function showFlashMessage() {
        const flash = sessionStorage.getItem('flashMessage');
        if (flash) {
            showAlert(flash, 'success');
            sessionStorage.removeItem('flashMessage');
        }
    }

    async function saveEvent(e) {
        e.preventDefault();
        const form = document.getElementById('eventForm');
        const formData = new FormData(form);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                // Guardar mensaje en sessionStorage y recargar la página
                sessionStorage.setItem('flashMessage', json.message);
                window.location.reload();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (error) {
            showAlert('Error de conexión con el servidor', 'danger');
        }
    }

    async function toggleEventStatus(eventId, currentStatus) {
        const action = currentStatus == 1 ? 'cerrar' : 'abrir';
        if (!confirm(`¿Seguro que desea ${action} este evento?`)) return;

        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('event_id', eventId);
        formData.append('status', currentStatus == 1 ? 0 : 1);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                sessionStorage.setItem('flashMessage', json.message);
                window.location.reload();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (error) {
            showAlert('Error de conexión con el servidor', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        showFlashMessage(); // Mostrar mensaje después de recargar
        loadEventsList();
        resetForm(); // Incluye loadDefaultCategories
    });

    function resetForm() {
        document.getElementById('eventForm').reset();
        document.getElementById('event_id').value = '';
        document.getElementById('formTitle').innerText = '1. Información del Nuevo Evento';
        document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Guardar Hackathon Completamente';
        document.getElementById('formCard').classList.remove('border-warning');
        document.getElementById('btnCancelEdit').classList.add('d-none');

        document.getElementById('categoriesContainer').innerHTML = '';
        catCounter = 0;
        loadDefaultCategories(); // Cargar categorías por defecto según tipo (público por defecto)
    }

    // Evento al cambiar tipo de evento
    document.getElementById('event_type').addEventListener('change', function() {
        loadDefaultCategories();
    });
</script>

<?php require_once 'footer.php'; ?>