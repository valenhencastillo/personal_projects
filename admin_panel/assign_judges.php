<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores pueden asignar jueces
checkAccess(['Admin']);

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. OBTENER ESTUDIANTES SIN JUEZ ASIGNADO (Filtrados por evento global)
        if ($action === 'get_unassigned_students') {
            $eventId = $_SESSION['current_event_id'] ?? 0;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }

            $query = "
                SELECT 
                    r.id AS registration_id,
                    p.full_name AS student_name,
                    p.last_name AS student_lastname,
                    p.institution,
                    c.name AS category_name
                FROM registrations r
                INNER JOIN participants p ON r.participant_id = p.id
                INNER JOIN event_categories c ON r.category_id = c.id
                LEFT JOIN judge_student_assignments jsa ON r.id = jsa.registration_id
                WHERE r.event_id = ? AND jsa.id IS NULL
                ORDER BY p.full_name ASC, p.last_name ASC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$eventId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 2. OBTENER ESTUDIANTES YA ASIGNADOS (con filtro opcional por juez)
        if ($action === 'get_assigned_students') {
            $eventId = $_SESSION['current_event_id'] ?? 0;
            if (!$eventId) {
                echo json_encode(['status' => 'error', 'message' => 'No hay evento seleccionado.']);
                exit;
            }
            $judgeId = $_POST['judge_id'] ?? 0; // 0 = todos

            $query = "
                SELECT 
                    jsa.id AS assignment_id,
                    p.full_name AS student_name,
                    p.last_name AS student_lastname,
                    c.name AS category_name,
                    u.username AS judge_name,
                    u.id AS judge_id
                FROM judge_student_assignments jsa
                INNER JOIN registrations r ON jsa.registration_id = r.id
                INNER JOIN participants p ON r.participant_id = p.id
                INNER JOIN event_categories c ON r.category_id = c.id
                INNER JOIN users u ON jsa.judge_id = u.id
                WHERE r.event_id = :event_id
            ";

            $params = [':event_id' => $eventId];

            if (!empty($judgeId) && $judgeId > 0) {
                $query .= " AND jsa.judge_id = :judge_id";
                $params[':judge_id'] = $judgeId;
            }

            $query .= " ORDER BY u.username ASC, p.full_name ASC, p.last_name ASC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 3. PROCESAR LA ASIGNACIÓN DE ESTUDIANTES A UN JUEZ
        if ($action === 'assign_judge') {
            $judgeId = $_POST['judge_id'] ?? '';
            $selectedStudents = $_POST['students'] ?? []; // Array de registration_id

            if (empty($judgeId)) {
                throw new Exception("Debes seleccionar un juez.");
            }
            if (empty($selectedStudents) || !is_array($selectedStudents)) {
                throw new Exception("Debes seleccionar al menos un estudiante.");
            }

            $pdo->beginTransaction();

            $stmtAssig = $pdo->prepare("INSERT INTO judge_student_assignments (judge_id, registration_id) VALUES (?, ?)");
            foreach ($selectedStudents as $regId) {
                $stmtAssig->execute([$judgeId, $regId]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Estudiantes asignados correctamente al juez.']);
            exit;
        }

        // 4. DESASIGNAR / REMOVER JUEZ DE UN ESTUDIANTE
        if ($action === 'unassign_student') {
            $assignmentId = $_POST['assignment_id'];
            $stmt = $pdo->prepare("DELETE FROM judge_student_assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            echo json_encode(['status' => 'success', 'message' => 'Asignación removida con éxito.']);
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

// Obtener evento actual desde sesión
$currentEventId = $_SESSION['current_event_id'] ?? null;

// Si no hay evento, mostrar mensaje y salir
if (!$currentEventId) {
    require_once 'header.php';
    require_once 'sidebar.php';
    echo '<div class="container py-4"><div class="alert alert-warning">Debe seleccionar un evento en el menú lateral antes de gestionar asignaciones de jueces.</div></div>';
    require_once 'footer.php';
    exit;
}

// Obtener lista de jueces
$judgesList = $pdo->query("
    SELECT u.id, u.username 
    FROM users u 
    INNER JOIN roles r ON u.role_id = r.id 
    WHERE r.name = 'Juez' 
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

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

    .assign-wrapper {
        padding: 1rem;
    }

    .assign-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .assign-header h2 {
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
        height: 100%;
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
        width: 100%;
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

    .btn-gradient-danger {
        background: linear-gradient(135deg, #E74C3C, #F1948A);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.4rem 1rem;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
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
        position: sticky;
        top: 0;
        background: white;
        z-index: 1;
    }

    .table-modern td {
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }

    .pagination-modern {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.25rem;
        margin-top: 1rem;
    }

    .pagination-modern button {
        border: none;
        border-radius: 8px;
        padding: 0.4rem 0.75rem;
        background: #f0f0f0;
        color: var(--dark);
        cursor: pointer;
        transition: background 0.2s;
    }

    .pagination-modern button.active {
        background: var(--primary);
        color: white;
    }

    .pagination-modern button:hover:not(.active) {
        background: #e0e0e0;
    }
</style>

<div class="assign-wrapper">
    <div class="assign-header">
        <h2><i class="bi bi-diagram-3"></i> Asignación de Jueces a Estudiantes</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <div id="alertContainer"></div>

    <!-- Nota del evento actual -->
    <div class="alert alert-info">
        <i class="bi bi-calendar-event"></i> Trabajando en el evento seleccionado globalmente.
    </div>

    <div class="row g-4">
        <!-- SECCIÓN 1: ESTUDIANTES SIN JUEZ (ASIGNACIÓN) -->
        <div class="col-md-7">
            <div class="card-modern">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> Estudiantes Sin Juez</h5>
                    <span class="badge bg-warning text-dark" id="unassignedCount">0 pendientes</span>
                </div>
                <div class="card-body">
                    <form id="assignForm" onsubmit="processAssignment(event)">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Asignar seleccionados al Juez:</label>
                            <select name="judge_id" id="judge_id" class="form-select form-select-modern" required>
                                <option value="">-- Selecciona el Juez --</option>
                                <?php foreach ($judgesList as $juez): ?>
                                    <option value="<?= $juez['id'] ?>"><?= htmlspecialchars($juez['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Buscador -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Buscar estudiante:</label>
                            <input type="text" id="searchInput" class="form-control form-control-modern" placeholder="Nombre, institución, categoría..." oninput="applyFilters()">
                        </div>

                        <div class="d-flex justify-content-between align-items-center my-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                <label class="form-check-label fw-bold" for="selectAll">Seleccionar Todos</label>
                            </div>
                            <button type="submit" class="btn btn-gradient-success" id="btnAssign" disabled>
                                <i class="bi bi-check-circle"></i> Asignar Juez
                            </button>
                        </div>

                        <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle table-modern" id="unassignedTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Estudiante / Equipo</th>
                                        <th>Categoría</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div id="paginationContainer" class="pagination-modern"></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: ESTUDIANTES YA ASIGNADOS (LISTADO) -->
        <div class="col-md-5">
            <div class="card-modern">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-card-checklist"></i> Asignaciones Activas</h5>
                </div>
                <div class="card-body p-3">
                    <!-- Filtro por juez -->
                    <div class="mb-3">
                        <label for="filter_judge" class="form-label fw-bold">Filtrar por Juez:</label>
                        <select id="filter_judge" class="form-select form-select-modern" onchange="loadAssignedStudents()">
                            <option value="0">-- Todos los Jueces --</option>
                            <?php foreach ($judgesList as $juez): ?>
                                <option value="<?= $juez['id'] ?>"><?= htmlspecialchars($juez['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle table-modern" id="assignedTable">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Juez Asignado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    let unassignedStudents = [];
    let currentPage = 1;
    const pageSize = 10;

    // Cargar datos al inicio
    document.addEventListener('DOMContentLoaded', () => {
        loadUnassignedStudents();
        loadAssignedStudents();
    });

    async function loadUnassignedStudents() {
        const formData = new FormData();
        formData.append('action', 'get_unassigned_students');

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
            unassignedStudents = json.data;
            currentPage = 1;
            applyFilters();
        } catch (e) {
            console.error(e);
        }
    }

    function applyFilters() {
        const searchText = document.getElementById('searchInput').value.trim().toLowerCase();

        const filtered = unassignedStudents.filter(s => {
            const fullName = `${s.student_name} ${s.student_lastname || ''}`.toLowerCase();
            const matchName = fullName.includes(searchText);
            const matchInstitution = (s.institution || '').toLowerCase().includes(searchText);
            const matchCategory = s.category_name.toLowerCase().includes(searchText);
            return matchName || matchInstitution || matchCategory;
        });

        document.getElementById('unassignedCount').innerText = `${filtered.length} pendientes`;
        document.getElementById('btnAssign').disabled = filtered.length === 0;

        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        const pageStudents = filtered.slice(start, end);

        renderUnassignedTable(pageStudents);
        updatePagination(totalPages, filtered.length);
    }

    function renderUnassignedTable(students) {
        const tbody = document.querySelector('#unassignedTable tbody');
        tbody.innerHTML = '';

        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No hay estudiantes sin asignar.</td></tr>';
            return;
        }

        students.forEach(s => {
            const fullName = `${s.student_name} ${s.student_lastname || ''}`.trim();
            tbody.innerHTML += `
                <tr>
                    <td>
                        <input class="form-check-input student-checkbox" type="checkbox" name="students[]" value="${s.registration_id}">
                    </td>
                    <td>
                        <strong>${fullName}</strong>
                        ${s.institution ? `<br><small class="text-muted">${s.institution}</small>` : ''}
                    </td>
                    <td><span class="badge bg-info text-dark">${s.category_name}</span></td>
                </tr>
            `;
        });
    }

    function updatePagination(totalPages, totalFiltered) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        // Botón anterior
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = `btn btn-sm btn-outline-secondary ${currentPage === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
        prevBtn.onclick = () => changePage(currentPage - 1);
        container.appendChild(prevBtn);

        // Números de página
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        if (endPage - startPage < 4) {
            if (startPage === 1) endPage = Math.min(totalPages, startPage + 4);
            else startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => changePage(i);
            container.appendChild(pageBtn);
        }

        // Botón siguiente
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = `btn btn-sm btn-outline-secondary ${currentPage === totalPages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        nextBtn.onclick = () => changePage(currentPage + 1);
        container.appendChild(nextBtn);
    }

    function changePage(page) {
        currentPage = page;
        applyFilters();
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }

    async function loadAssignedStudents() {
        const judgeId = document.getElementById('filter_judge').value || 0;

        const formData = new FormData();
        formData.append('action', 'get_assigned_students');
        formData.append('judge_id', judgeId);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            const tbody = document.querySelector('#assignedTable tbody');
            tbody.innerHTML = '';

            if (json.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4">${json.message}</td></tr>`;
                return;
            }

            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Sin asignaciones registradas.</td></tr>';
                return;
            }

            json.data.forEach(a => {
                const fullName = `${a.student_name} ${a.student_lastname || ''}`.trim();
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <strong>${fullName}</strong>
                            <br><small class="text-muted">${a.category_name}</small>
                        </td>
                        <td><span class="badge bg-success">${a.judge_name}</span></td>
                        <td class="text-end">
                            <button title="Quitar asignación" class="btn btn-gradient-danger" onclick="unassignStudent(${a.assignment_id})">
                                <i class="bi bi-x-lg"></i> Quitar
                            </button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error(e);
        }
    }

    async function processAssignment(e) {
        e.preventDefault();
        const form = document.getElementById('assignForm');
        const selected = form.querySelectorAll('.student-checkbox:checked');
        if (selected.length === 0) {
            showAlert('Selecciona al menos un estudiante para asignar.', 'warning');
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'assign_judge');

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                document.getElementById('selectAll').checked = false;
                loadUnassignedStudents();
                loadAssignedStudents();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al procesar la asignación.', 'danger');
        }
    }

    async function unassignStudent(assignmentId) {
        if (!confirm('¿Deseas retirar a este juez del estudiante? El estudiante volverá a la lista de pendientes.')) return;

        const formData = new FormData();
        formData.append('action', 'unassign_student');
        formData.append('assignment_id', assignmentId);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'info');
                loadUnassignedStudents();
                loadAssignedStudents();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al remover la asignación.', 'danger');
        }
    }

    function showAlert(msg, type = 'success') {
        document.getElementById('alertContainer').innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
</script>

<?php require_once 'footer.php'; ?>