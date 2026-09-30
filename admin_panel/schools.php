<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores
checkAccess(['Admin']);

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. LISTAR COLEGIOS CON PAGINACIÓN Y BÚSQUEDA
        if ($action === 'list_schools') {
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $search = trim($_POST['search'] ?? '');

            $whereClause = "";
            $params = [];

            if (!empty($search)) {
                $whereClause = "WHERE name LIKE ? OR city LIKE ? OR state LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            // Contar total
            $countQuery = "SELECT COUNT(*) FROM schools $whereClause";
            $stmtCount = $pdo->prepare($countQuery);
            $stmtCount->execute($params);
            $totalRecords = $stmtCount->fetchColumn();
            $totalPages = ceil($totalRecords / $limit);

            // Obtener registros
            $query = "
                SELECT id, name, address, city, state, phone, email, created_at
                FROM schools
                $whereClause
                ORDER BY name ASC
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

        // 2. OBTENER DETALLE DE UN COLEGIO
        if ($action === 'get_school') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
            $stmt->execute([$id]);
            $school = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$school) {
                echo json_encode(['status' => 'error', 'message' => 'Colegio no encontrado.']);
                exit;
            }

            echo json_encode(['status' => 'success', 'data' => $school]);
            exit;
        }

        // 3. GUARDAR / EDITAR COLEGIO
        if ($action === 'save_school') {
            $id = intval($_POST['school_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio.']);
                exit;
            }

            if ($id > 0) {
                // Actualizar
                $stmt = $pdo->prepare("UPDATE schools SET name = ?, address = ?, city = ?, state = ?, phone = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $address, $city, $state, $phone, $email, $id]);
                $message = 'Colegio actualizado correctamente.';
            } else {
                // Crear
                $stmt = $pdo->prepare("INSERT INTO schools (name, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $city, $state, $phone, $email]);
                $message = 'Colegio creado correctamente.';
            }

            echo json_encode(['status' => 'success', 'message' => $message]);
            exit;
        }

        // 4. ELIMINAR COLEGIO
        if ($action === 'delete_school') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Colegio eliminado correctamente.']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        exit;
    }
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<style>
    :root {
        --primary: #6C63FF;
        --dark: #2C3E50;
        --light-bg: #F5F7FA;
        --card-radius: 20px;
        --shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        --success: #2ECC71;
        --danger: #E74C3C;
    }

    .schools-wrapper {
        padding: 1rem;
    }

    .schools-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .schools-header h2 {
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

    .form-control-modern,
    .form-select-modern {
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }

    .form-control-modern:focus,
    .form-select-modern:focus {
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

    .search-box {
        position: relative;
    }

    .search-box input {
        padding-left: 2.5rem;
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #95a5a6;
    }
</style>

<div class="schools-wrapper">
    <div class="schools-header">
        <h2><i class="bi bi-building"></i> Gestión de Colegios</h2>
        <button class="btn btn-gradient-primary" onclick="openModal()">
            <i class="bi bi-plus-lg"></i> Nuevo Colegio
        </button>
    </div>

    <div id="alertContainer"></div>

    <div class="card-modern">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Colegios</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control form-control-modern" placeholder="Buscar por nombre, ciudad o estado..." oninput="debounceSearch()">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-modern" id="schoolsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small" id="paginationInfo"></span>
                <div class="pagination-modern" id="paginationControls"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar -->
<div class="modal fade" id="schoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0" style="background: var(--primary); color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title" id="modalTitle">Nuevo Colegio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="schoolForm" onsubmit="saveSchool(event)">
                    <input type="hidden" name="action" value="save_school">
                    <input type="hidden" name="school_id" id="school_id" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre *</label>
                        <input type="text" name="name" id="name" class="form-control form-control-modern" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" name="address" id="address" class="form-control form-control-modern">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ciudad</label>
                            <input type="text" name="city" id="city" class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Estado</label>
                            <input type="text" name="state" id="state" class="form-control form-control-modern">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="phone" id="phone" class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="email" class="form-control form-control-modern">
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gradient-primary" id="btnSave">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimer = null;
    let schoolModal;

    document.addEventListener('DOMContentLoaded', () => {
        schoolModal = new bootstrap.Modal(document.getElementById('schoolModal'));
        loadSchoolsList(1);
    });

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadSchoolsList(1), 300);
    }

    async function loadSchoolsList(page = 1) {
        currentPage = page;
        const searchValue = document.getElementById('searchInput').value.trim();

        const formData = new FormData();
        formData.append('action', 'list_schools');
        formData.append('page', page);
        formData.append('search', searchValue);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'error') {
                showAlert(json.message, 'danger');
                return;
            }

            const tbody = document.querySelector('#schoolsTable tbody');
            tbody.innerHTML = '';

            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No se encontraron colegios.</td></tr>';
                document.getElementById('paginationInfo').innerText = 'Sin registros';
                document.getElementById('paginationControls').innerHTML = '';
                return;
            }

            json.data.forEach(s => {
                tbody.innerHTML += `
                    <tr>
                        <td>#${s.id}</td>
                        <td><strong>${s.name}</strong></td>
                        <td>${s.city || '—'}</td>
                        <td>${s.state || '—'}</td>
                        <td>${s.phone || '—'}</td>
                        <td>${s.email || '—'}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="editSchool(${s.id})" title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSchool(${s.id})" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            const pag = json.pagination;
            document.getElementById('paginationInfo').innerText =
                `Página ${pag.current_page} de ${pag.total_pages || 1} (Total: ${pag.total_records} colegios)`;

            renderPagination(pag.current_page, pag.total_pages);

        } catch (e) {
            console.error(e);
            showAlert('Error al cargar colegios.', 'danger');
        }
    }

    function renderPagination(current, total) {
        const container = document.getElementById('paginationControls');
        container.innerHTML = '';

        if (total <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '&laquo;';
        prevBtn.disabled = current === 1;
        prevBtn.onclick = () => loadSchoolsList(current - 1);
        container.appendChild(prevBtn);

        for (let i = 1; i <= total; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = i === current ? 'active' : '';
            btn.onclick = () => loadSchoolsList(i);
            container.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '&raquo;';
        nextBtn.disabled = current === total;
        nextBtn.onclick = () => loadSchoolsList(current + 1);
        container.appendChild(nextBtn);
    }

    function openModal() {
        document.getElementById('schoolForm').reset();
        document.getElementById('school_id').value = '';
        document.getElementById('modalTitle').innerText = 'Nuevo Colegio';
        schoolModal.show();
    }

    async function editSchool(id) {
        const formData = new FormData();
        formData.append('action', 'get_school');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                const s = json.data;
                document.getElementById('school_id').value = s.id;
                document.getElementById('name').value = s.name;
                document.getElementById('address').value = s.address || '';
                document.getElementById('city').value = s.city || '';
                document.getElementById('state').value = s.state || '';
                document.getElementById('phone').value = s.phone || '';
                document.getElementById('email').value = s.email || '';
                document.getElementById('modalTitle').innerText = 'Editar Colegio';
                schoolModal.show();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al cargar datos del colegio.', 'danger');
        }
    }

    async function saveSchool(e) {
        e.preventDefault();
        const form = document.getElementById('schoolForm');
        const formData = new FormData(form);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                schoolModal.hide();
                showAlert(json.message, 'success');
                loadSchoolsList(currentPage);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al guardar colegio.', 'danger');
        }
    }

    async function deleteSchool(id) {
        if (!confirm('¿Seguro que deseas eliminar este colegio?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_school');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                loadSchoolsList(currentPage);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al eliminar colegio.', 'danger');
        }
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
</script>

<?php require_once 'footer.php'; ?>