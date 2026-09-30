<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores pueden gestionar usuarios
checkAccess(['Admin']);

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. LISTAR JUECES
        if ($action === 'list_judges') {
            $query = "
                SELECT u.id, u.username, u.email, u.created_at
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE r.name = 'Juez'
                ORDER BY u.id DESC
            ";
            $stmt = $pdo->query($query);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 2. OBTENER DETALLE DE UN JUEZ
        if ($action === 'get_judge') {
            $judgeId = $_POST['id'];
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$judgeId]);
            $judge = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $judge]);
            exit;
        }

        // 3. GUARDAR / EDITAR JUEZ
        if ($action === 'save_judge') {
            $judgeId = $_POST['judge_id'] ?? '';
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?? '';

            // Obtener ID del rol 'Juez'
            $stmtRole = $pdo->query("SELECT id FROM roles WHERE name = 'Juez'");
            $roleId = $stmtRole->fetchColumn();

            if (!$roleId) {
                throw new Exception("El rol 'Juez' no está registrado en la tabla roles.");
            }

            // Validar que el username no esté repetido
            $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmtCheckUser->execute([$username, $judgeId ?: 0]);
            if ($stmtCheckUser->fetch()) {
                throw new Exception("El nombre de usuario ya está en uso.");
            }

            if (empty($judgeId)) {
                // Nuevo Registro
                if (empty($password)) {
                    throw new Exception("La contraseña es requerida para registrar un juez.");
                }

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtUser = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmtUser->execute([$username, $email, $hash, $roleId]);
            } else {
                // Edición de Registro
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtUser = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmtUser->execute([$username, $email, $hash, $judgeId]);
                } else {
                    $stmtUser = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmtUser->execute([$username, $email, $judgeId]);
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => empty($judgeId) ? 'Juez registrado con éxito.' : 'Datos del juez actualizados.'
            ]);
            exit;
        }

        // 4. ELIMINAR JUEZ
        if ($action === 'delete_judge') {
            $judgeId = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$judgeId]);
            echo json_encode(['status' => 'success', 'message' => 'Juez eliminado correctamente.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
        --shadow: 0 8px 24px rgba(0,0,0,0.08);
        --success: #2ECC71;
        --danger: #E74C3C;
    }

    .judges-wrapper {
        padding: 1rem;
    }

    .judges-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .judges-header h2 {
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
</style>

<div class="judges-wrapper">
    <div class="judges-header">
        <h2><i class="bi bi-person-badge"></i> Registro de Jueces</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <div id="alertContainer"></div>

    <!-- FORMULARIO DE REGISTRO / EDICIÓN -->
    <div class="card-modern" id="formCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0" id="formTitle">Registrar Nuevo Juez</h5>
            <button type="button" class="btn btn-sm btn-outline-light d-none" id="btnCancelEdit" onclick="resetForm()">Cancelar Edición</button>
        </div>
        <div class="card-body">
            <form id="judgeForm" onsubmit="saveJudge(event)">
                <input type="hidden" name="action" value="save_judge">
                <input type="hidden" name="judge_id" id="judge_id" value="">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Usuario *</label>
                        <input type="text" name="username" id="username" class="form-control form-control-modern" required placeholder="Ej: juez_pedro">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Correo Electrónico (Opcional)</label>
                        <input type="email" name="email" id="email" class="form-control form-control-modern" placeholder="juez@correo.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contraseña <small class="text-muted" id="passHelp">(Requerida si es nuevo)</small></label>
                        <input type="password" name="password" id="password" class="form-control form-control-modern" placeholder="******">
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-gradient-success" id="btnSave">
                        <i class="bi bi-save"></i> Guardar Juez
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTA DE JUECES REGISTRADOS -->
    <div class="card-modern">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-people"></i> Jueces Registrados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-modern" id="judgesTable">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Fecha de Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carga dinámica por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadJudgesList() {
        const formData = new FormData();
        formData.append('action', 'list_judges');

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            const tbody = document.querySelector('#judgesTable tbody');
            tbody.innerHTML = '';

            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay jueces registrados.</td></tr>';
                return;
            }

            json.data.forEach(j => {
                const date = new Date(j.created_at).toLocaleDateString();

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${j.username}</strong></td>
                        <td>${j.email || '<span class="text-muted">Sin correo</span>'}</td>
                        <td>${date}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary" onclick="editJudge(${j.id})"><i class="bi bi-pencil"></i> Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteJudge(${j.id})"><i class="bi bi-trash"></i> Borrar</button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error(e);
        }
    }

    async function saveJudge(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('judgeForm'));

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                resetForm();
                loadJudgesList();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al procesar la solicitud.', 'danger');
        }
    }

    async function editJudge(id) {
        resetForm();
        const formData = new FormData();
        formData.append('action', 'get_judge');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            const j = json.data;

            document.getElementById('judge_id').value = j.id;
            document.getElementById('username').value = j.username;
            document.getElementById('email').value = j.email || '';

            document.getElementById('formTitle').innerText = 'Editando Juez: ' + j.username;
            document.getElementById('btnSave').innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar Juez';
            document.getElementById('btnCancelEdit').classList.remove('d-none');
            document.getElementById('formCard').classList.add('border-warning');
            document.getElementById('passHelp').innerText = '(Dejar en blanco para conservar la actual)';

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } catch (e) {
            showAlert('Error al obtener los datos del juez.', 'danger');
        }
    }

    async function deleteJudge(id) {
        if (!confirm('¿Seguro que deseas eliminar la cuenta de este juez?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_judge');
        formData.append('id', id);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success') {
                showAlert(json.message, 'success');
                loadJudgesList();
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al intentar eliminar.', 'danger');
        }
    }

    function resetForm() {
        document.getElementById('judgeForm').reset();
        document.getElementById('judge_id').value = '';
        document.getElementById('formTitle').innerText = 'Registrar Nuevo Juez';
        document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Guardar Juez';
        document.getElementById('btnCancelEdit').classList.add('d-none');
        document.getElementById('formCard').classList.remove('border-warning');
        document.getElementById('passHelp').innerText = '(Requerida si es nuevo)';
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
        loadJudgesList();
    });
</script>

<?php require_once 'footer.php'; ?>