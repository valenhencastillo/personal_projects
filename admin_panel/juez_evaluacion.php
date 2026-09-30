<?php
require_once 'database.php';
require_once 'auth.php';

// Permitir acceso a Admin y Juez
checkAccess(['Admin', 'Juez']);

$judgeUserId = $_SESSION['user_id'];
$isAdmin = (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Admin');

// Obtener evento global desde sesión
$currentEventId = $_SESSION['current_event_id'] ?? null;
if (!$currentEventId) {
    require_once 'header.php';
    require_once 'sidebar.php';
    echo '<div class="container py-4"><div class="alert alert-warning">Debe seleccionar un evento en el menú lateral antes de evaluar.</div></div>';
    require_once 'footer.php';
    exit;
}

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // 1. OBTENER JUECES (solo admin)
        if ($action === 'get_judges' && $isAdmin) {
            $stmt = $pdo->query("
                SELECT u.id, u.username
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE r.name = 'Juez'
                ORDER BY u.username ASC
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 2. OBTENER ESTUDIANTES DEL EVENTO GLOBAL
        if ($action === 'get_my_students') {
            $eventId = $_SESSION['current_event_id'] ?? 0;
            $judgeId = $_POST['judge_id'] ?? 0;

            if (!$eventId) {
                throw new Exception("No hay evento seleccionado.");
            }

            if ($isAdmin) {
                if ($judgeId > 0) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            r.id AS registration_id,
                            p.full_name,
                            p.last_name,
                            p.institution,
                            c.name AS category_name,
                            c.id AS category_id
                        FROM registrations r
                        INNER JOIN participants p ON r.participant_id = p.id
                        INNER JOIN event_categories c ON r.category_id = c.id
                        INNER JOIN judge_student_assignments jsa ON r.id = jsa.registration_id
                        WHERE r.event_id = ? AND jsa.judge_id = ?
                        ORDER BY p.full_name ASC
                    ");
                    $stmt->execute([$eventId, $judgeId]);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT 
                            r.id AS registration_id,
                            p.full_name,
                            p.last_name,
                            p.institution,
                            c.name AS category_name,
                            c.id AS category_id
                        FROM registrations r
                        INNER JOIN participants p ON r.participant_id = p.id
                        INNER JOIN event_categories c ON r.category_id = c.id
                        WHERE r.event_id = ?
                        ORDER BY p.full_name ASC
                    ");
                    $stmt->execute([$eventId]);
                }
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        r.id AS registration_id,
                        p.full_name,
                        p.last_name,
                        p.institution,
                        c.name AS category_name,
                        c.id AS category_id
                    FROM judge_student_assignments jsa
                    INNER JOIN registrations r ON jsa.registration_id = r.id
                    INNER JOIN participants p ON r.participant_id = p.id
                    INNER JOIN event_categories c ON r.category_id = c.id
                    WHERE jsa.judge_id = ? AND r.event_id = ?
                    ORDER BY p.full_name ASC
                ");
                $stmt->execute([$judgeUserId, $eventId]);
            }
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // 3. OBTENER RETOS CON ÍTEMS Y EVALUACIONES POR ÍTEM
        if ($action === 'get_student_challenges') {
            $registrationId = $_POST['registration_id'] ?? 0;
            $eventId = $_SESSION['current_event_id'] ?? 0;
            $judgeId = $_POST['judge_id'] ?? 0;

            $evaluatorId = $isAdmin && $judgeId > 0 ? $judgeId : $judgeUserId;

            $stmt = $pdo->prepare("SELECT category_id FROM registrations WHERE id = ? AND event_id = ?");
            $stmt->execute([$registrationId, $eventId]);
            $categoryId = $stmt->fetchColumn();
            if (!$categoryId) throw new Exception("Registro no encontrado.");

            // Obtener retos de la categoría
            $stmtChallenges = $pdo->prepare("
    SELECT id, title, description
    FROM challenges
    WHERE category_id = ? AND event_id = ? AND is_active = 1
    ORDER BY id ASC
");
            $stmtChallenges->execute([$categoryId, $eventId]);
            $challenges = $stmtChallenges->fetchAll(PDO::FETCH_ASSOC);

            // Obtener tiempo global guardado en judge_evaluations
            $stmtTime = $pdo->prepare("
                SELECT challenge_id, total_time_seconds
                FROM judge_evaluations
                WHERE registration_id = ? AND judge_user_id = ?
            ");
            $stmtTime->execute([$registrationId, $evaluatorId]);
            $times = [];
            while ($row = $stmtTime->fetch(PDO::FETCH_ASSOC)) {
                $times[$row['challenge_id']] = $row['total_time_seconds'];
            }

            // Obtener evaluaciones de ítems
            $stmtItemEval = $pdo->prepare("
                SELECT challenge_item_id, status
                FROM judge_item_evaluations
                WHERE registration_id = ? AND judge_user_id = ?
            ");
            $stmtItemEval->execute([$registrationId, $evaluatorId]);
            $itemEvals = [];
            while ($row = $stmtItemEval->fetch(PDO::FETCH_ASSOC)) {
                $itemEvals[$row['challenge_item_id']] = $row['status'];
            }

            // Para cada reto, obtener sus ítems con evaluación
            foreach ($challenges as &$chal) {
                $stmtItems = $pdo->prepare("
                    SELECT id, description
                    FROM challenge_items
                    WHERE challenge_id = ?
                    ORDER BY id ASC
                ");
                $stmtItems->execute([$chal['id']]);
                $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                foreach ($items as &$item) {
                    $item['existing_status'] = $itemEvals[$item['id']] ?? null;
                }
                $chal['items'] = $items;
                $chal['existing_time'] = $times[$chal['id']] ?? '';
            }

            echo json_encode(['status' => 'success', 'data' => $challenges]);
            exit;
        }

        // 4. GUARDAR EVALUACIÓN (por ítems y tiempo)
        if ($action === 'save_evaluation') {
            $registrationId = $_POST['registration_id'] ?? 0;
            $challengeId = $_POST['challenge_id'] ?? 0;
            $timeSeconds = $_POST['total_time_seconds'] ?? 0;
            $judgeId = $_POST['judge_id'] ?? 0;

            $itemStatuses = [];
            if (isset($_POST['items']) && is_string($_POST['items'])) {
                $itemStatuses = json_decode($_POST['items'], true);
                if (!is_array($itemStatuses)) $itemStatuses = [];
            } elseif (isset($_POST['items']) && is_array($_POST['items'])) {
                $itemStatuses = $_POST['items'];
            }

            $timeSeconds = intval($timeSeconds);
            if ($timeSeconds < 0) throw new Exception("El tiempo no puede ser negativo.");
            if (empty($itemStatuses)) {
                throw new Exception("Debe evaluar al menos un ítem.");
            }

            $evaluatorId = $isAdmin && $judgeId > 0 ? $judgeId : $judgeUserId;

            // Verificar que el reto pertenece al estudiante
            $stmtCheck = $pdo->prepare("
                SELECT 1
                FROM registrations r
                INNER JOIN challenges ch ON ch.category_id = r.category_id
                WHERE r.id = ? AND ch.id = ?
            ");
            $stmtCheck->execute([$registrationId, $challengeId]);
            if (!$stmtCheck->fetchColumn()) throw new Exception("Reto no válido para este estudiante.");

            $pdo->beginTransaction();

            // 1. Guardar/actualizar cada ítem
            $stmtItemCheck = $pdo->prepare("
                SELECT id FROM judge_item_evaluations
                WHERE registration_id = ? AND challenge_item_id = ? AND judge_user_id = ?
            ");
            $stmtItemInsert = $pdo->prepare("
                INSERT INTO judge_item_evaluations (registration_id, challenge_item_id, status, judge_user_id, evaluated_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtItemUpdate = $pdo->prepare("
                UPDATE judge_item_evaluations SET status = ?, evaluated_at = NOW()
                WHERE id = ?
            ");

            foreach ($itemStatuses as $itemId => $status) {
                if (!in_array($status, ['superado', 'no_superado'])) {
                    throw new Exception("Estado de ítem inválido.");
                }
                $stmtItemCheck->execute([$registrationId, $itemId, $evaluatorId]);
                $existingItemId = $stmtItemCheck->fetchColumn();
                if ($existingItemId) {
                    $stmtItemUpdate->execute([$status, $existingItemId]);
                } else {
                    $stmtItemInsert->execute([$registrationId, $itemId, $status, $evaluatorId]);
                }
            }

            // 2. Determinar estado global del reto
            $stmtAllItems = $pdo->prepare("SELECT id FROM challenge_items WHERE challenge_id = ?");
            $stmtAllItems->execute([$challengeId]);
            $allItemIds = $stmtAllItems->fetchAll(PDO::FETCH_COLUMN);

            $stmtAllEvals = $pdo->prepare("
                SELECT status FROM judge_item_evaluations
                WHERE registration_id = ? AND challenge_item_id IN (
                    SELECT id FROM challenge_items WHERE challenge_id = ?
                ) AND judge_user_id = ?
            ");
            $stmtAllEvals->execute([$registrationId, $challengeId, $evaluatorId]);
            $evalStatuses = $stmtAllEvals->fetchAll(PDO::FETCH_COLUMN);

            $allSuperado = (count($evalStatuses) === count($allItemIds) && !in_array('no_superado', $evalStatuses));
            $globalStatus = $allSuperado ? 'superado' : 'no_superado';

            // 3. Guardar/actualizar tiempo global y estado en judge_evaluations
            $stmtGlobalCheck = $pdo->prepare("
                SELECT id FROM judge_evaluations
                WHERE registration_id = ? AND challenge_id = ? AND judge_user_id = ?
            ");
            $stmtGlobalCheck->execute([$registrationId, $challengeId, $evaluatorId]);
            $globalId = $stmtGlobalCheck->fetchColumn();

            if ($globalId) {
                $stmtUpdateGlobal = $pdo->prepare("
                    UPDATE judge_evaluations
                    SET total_time_seconds = ?, status = ?, evaluated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdateGlobal->execute([$timeSeconds, $globalStatus, $globalId]);
            } else {
                $stmtInsertGlobal = $pdo->prepare("
                    INSERT INTO judge_evaluations (registration_id, challenge_id, status, total_time_seconds, judge_user_id, evaluated_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsertGlobal->execute([$registrationId, $challengeId, $globalStatus, $timeSeconds, $evaluatorId]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Evaluación guardada correctamente.']);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Obtener lista de jueces si admin
$judgesList = [];
if ($isAdmin) {
    $judgesList = $pdo->query("
        SELECT u.id, u.username
        FROM users u
        INNER JOIN roles r ON u.role_id = r.id
        WHERE r.name = 'Juez'
        ORDER BY u.username ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<!-- Estilos personalizados para móvil -->
<style>
    :root {
        --primary: #6C63FF;
        --success: #2ECC71;
        --danger: #E74C3C;
        --dark: #2C3E50;
        --light-bg: #F5F7FA;
        --card-radius: 16px;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-tap-highlight-color: transparent;
    }

    .evaluation-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem;
    }

    /* Encabezado */
    .eval-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .eval-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .back-btn {
        background: white;
        border: 1px solid #ddd;
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        color: var(--dark);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        box-shadow: var(--shadow);
    }

    .back-btn:active {
        transform: scale(0.97);
    }

    /* Selector de juez */
    .judge-selector {
        background: white;
        border-radius: var(--card-radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .judge-selector label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
        display: block;
        font-size: 0.9rem;
    }

    .judge-selector select {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border: 2px solid #E0E0E0;
        font-size: 1rem;
        background-color: white;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236C63FF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
    }

    /* Lista de estudiantes */
    .student-list-card {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .student-list-header {
        padding: 1rem;
        background: var(--dark);
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .student-list {
        max-height: 350px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .student-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #F0F0F0;
        transition: background 0.2s;
        cursor: pointer;
    }

    .student-item:active {
        background: #F0F0FF;
    }

    .student-item .info {
        flex: 1;
        min-width: 0;
    }

    .student-item .name {
        font-weight: 600;
        color: var(--dark);
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .student-item .institution {
        font-size: 0.8rem;
        color: #7F8C8D;
    }

    .category-badge {
        background: #E8E8FF;
        color: var(--primary);
        border-radius: 20px;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        margin-left: 0.5rem;
    }

    .evaluate-btn {
        background: var(--primary);
        border: none;
        color: white;
        border-radius: 50px;
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .evaluate-btn:active {
        transform: scale(0.95);
        background: #5A52D5;
    }

    /* Panel de evaluación */
    .evaluation-panel {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .evaluation-panel-header {
        background: var(--primary);
        color: white;
        padding: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .student-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.85rem;
    }

    .challenge-card {
        margin: 1rem;
        border-radius: 12px;
        border: 1px solid #E0E0E0;
        overflow: hidden;
        background: #FAFAFA;
    }

    .challenge-header {
        background: #F0F0F0;
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: var(--dark);
    }

    .challenge-body {
        padding: 1rem;
    }

    .item-row {
        display: flex;
        flex-direction: column;
        margin-bottom: 0.75rem;
    }

    .item-label {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
        color: #555;
    }

    .item-select {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border-radius: 10px;
        border: 2px solid #E0E0E0;
        font-size: 1rem;
        background-color: white;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236C63FF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2rem;
    }

    .time-input {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border-radius: 10px;
        border: 2px solid #E0E0E0;
        font-size: 1rem;
    }

    .save-btn {
        background: var(--success);
        border: none;
        color: white;
        border-radius: 50px;
        padding: 0.6rem 1.2rem;
        font-size: 1rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s;
        width: 100%;
        margin-top: 0.5rem;
    }

    .save-btn:active {
        transform: scale(0.98);
        background: #27AE60;
    }

    .save-btn:disabled {
        background: #95A5A6;
    }

    /* Alerta */
    .alert-container {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        width: 90%;
        max-width: 400px;
    }

    .alert {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 1rem;
        font-weight: 500;
    }

    /* Utilidades para móvil */
    @media (min-width: 768px) {
        .evaluation-wrapper {
            padding: 2rem;
        }

        .student-list {
            max-height: 500px;
        }

        .item-row {
            flex-direction: row;
            align-items: center;
            gap: 1rem;
        }

        .item-label {
            flex: 1;
            margin-bottom: 0;
        }

        .item-select {
            flex: 2;
        }
    }
</style>

<div class="evaluation-wrapper">
    <!-- Encabezado -->
    <div class="eval-header">
        <h2><i class="bi bi-clipboard-check"></i> Evaluación</h2>
        <a href="<?= $isAdmin ? 'dashboard.php' : 'judge_dashboard.php' ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Contenedor de alertas -->
    <div id="alertContainer" class="alert-container"></div>

    <!-- Selector de juez (solo admin) -->
    <?php if ($isAdmin): ?>
        <div class="judge-selector">
            <label><i class="bi bi-person-badge"></i> Seleccionar Juez</label>
            <select id="judge_select" onchange="loadMyStudents()">
                <option value="0">-- Todos los jueces --</option>
                <?php foreach ($judgesList as $juez): ?>
                    <option value="<?= $juez['id'] ?>"><?= htmlspecialchars($juez['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <!-- Lista de estudiantes -->
    <div class="student-list-card">
        <div class="student-list-header">
            <i class="bi bi-people-fill"></i> <?= $isAdmin ? 'Estudiantes' : 'Mis estudiantes' ?>
        </div>
        <div id="studentsContainer" class="student-list">
            <div class="p-3 text-center text-muted">Cargando...</div>
        </div>
    </div>

    <!-- Panel de evaluación -->
    <div class="evaluation-panel">
        <div class="evaluation-panel-header">
            <span><i class="bi bi-ui-checks"></i> Evaluación de retos</span>
            <span id="currentStudentName" class="student-badge">Sin seleccionar</span>
        </div>
        <div id="evaluationPanel" class="p-3">
            <div class="text-center text-muted py-5">
                <i class="bi bi-arrow-left-circle fs-1"></i>
                <p>Selecciona un estudiante para evaluar.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
    const currentEventId = <?= json_encode($currentEventId) ?>;
    let currentRegistrationId = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadMyStudents();
        if (isAdmin) {
            // El select de juez ya está cargado desde PHP
        }
    });

    async function loadMyStudents() {
        if (!currentEventId) {
            document.getElementById('studentsContainer').innerHTML =
                '<div class="p-3 text-center text-muted">No hay evento seleccionado.</div>';
            resetEvaluationPanel();
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get_my_students');
        if (isAdmin) {
            const judgeId = document.getElementById('judge_select').value || 0;
            formData.append('judge_id', judgeId);
        }

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.status !== 'success') {
                showAlert(json.message, 'danger');
                return;
            }
            const container = document.getElementById('studentsContainer');
            container.innerHTML = '';

            if (json.data.length === 0) {
                container.innerHTML = '<div class="p-3 text-center text-muted">No hay estudiantes en esta selección.</div>';
                resetEvaluationPanel();
                return;
            }

            json.data.forEach(s => {
                const fullName = `${s.full_name} ${s.last_name || ''}`.trim();
                const item = document.createElement('div');
                item.className = 'student-item';
                item.innerHTML = `
                    <div class="info">
                        <div class="name">${fullName}</div>
                        ${s.institution ? `<div class="institution">${s.institution}</div>` : ''}
                    </div>
                    <span class="category-badge">${s.category_name}</span>
                    <button class="evaluate-btn" onclick="loadStudentEvaluation(${s.registration_id}, '${fullName.replace(/'/g, "\\'")}')">
                        <i class="bi bi-pencil-square"></i> Evaluar
                    </button>
                `;
                container.appendChild(item);
            });
        } catch (e) {
            console.error(e);
            showAlert('Error al cargar estudiantes.', 'danger');
        }
    }

    async function loadStudentEvaluation(registrationId, studentName) {
        currentRegistrationId = registrationId;
        document.getElementById('currentStudentName').textContent = studentName;

        const formData = new FormData();
        formData.append('action', 'get_student_challenges');
        formData.append('registration_id', registrationId);
        if (isAdmin) {
            const judgeId = document.getElementById('judge_select').value || 0;
            formData.append('judge_id', judgeId);
        }

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            renderEvaluationPanel(json.data, studentName);
        } catch (e) {
            showAlert('Error al cargar retos: ' + e.message, 'danger');
        }
    }

    function renderEvaluationPanel(challenges, studentName) {
        const panel = document.getElementById('evaluationPanel');
        panel.innerHTML = '';

        if (!challenges || challenges.length === 0) {
            panel.innerHTML = '<div class="text-center text-muted py-4">No hay retos definidos para esta categoría.</div>';
            return;
        }

        challenges.forEach(chal => {
            const card = document.createElement('div');
            card.className = 'challenge-card';

            let itemsHtml = '';
            chal.items.forEach(item => {
                const status = item.existing_status || '';
                itemsHtml += `
                    <div class="item-row">
                        <div class="item-label">${item.description}</div>
                        <select name="item_status_${item.id}" class="item-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="superado" ${status === 'superado' ? 'selected' : ''}>✅ Superado</option>
                            <option value="no_superado" ${status === 'no_superado' ? 'selected' : ''}>❌ No superado</option>
                        </select>
                        <input type="hidden" name="item_id_${item.id}" value="${item.id}">
                    </div>
                `;
            });

            card.innerHTML = `
                <div class="challenge-header">${chal.title}</div>
                <div class="challenge-body">
                    ${chal.description ? `<p>${chal.description}</p>` : ''}
                    <form class="evaluation-form" onsubmit="saveEvaluation(event, ${chal.id})">
                        <input type="hidden" name="challenge_id" value="${chal.id}">
                        <input type="hidden" name="registration_id" value="${currentRegistrationId}">
                        ${isAdmin ? `<input type="hidden" name="judge_id" value="${document.getElementById('judge_select').value}">` : ''}
                        ${itemsHtml}
                        <div class="mt-3">
                            <label class="item-label">Tiempo total (segundos)</label>
                            <input type="number" name="total_time_seconds" class="time-input" min="0" value="${chal.existing_time}" required>
                        </div>
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Guardar evaluación
                        </button>
                    </form>
                </div>
            `;
            panel.appendChild(card);
        });
    }

    async function saveEvaluation(e, challengeId) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'save_evaluation');

        const itemStatuses = {};
        form.querySelectorAll('select[name^="item_status_"]').forEach(select => {
            const itemId = select.name.replace('item_status_', '');
            if (select.value) {
                itemStatuses[itemId] = select.value;
            }
        });
        formData.append('items', JSON.stringify(itemStatuses));

        if (isAdmin) {
            const judgeId = document.getElementById('judge_select').value;
            formData.append('judge_id', judgeId);
        }

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.status === 'success') {
                showAlert(json.message, 'success');
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Guardado';
                    btn.disabled = true;
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-save"></i> Guardar evaluación';
                        btn.disabled = false;
                    }, 2000);
                }
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (error) {
            showAlert('Error al guardar la evaluación.', 'danger');
        }
    }

    function resetEvaluationPanel() {
        document.getElementById('currentStudentName').textContent = 'Sin seleccionar';
        document.getElementById('evaluationPanel').innerHTML =
            '<div class="text-center text-muted py-5"><i class="bi bi-arrow-left-circle fs-1"></i><p>Selecciona un estudiante para evaluar.</p></div>';
    }

    function showAlert(msg, type = 'success') {
        const container = document.getElementById('alertContainer');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        setTimeout(() => {
            container.innerHTML = '';
        }, 3000);
    }
</script>

<?php require_once 'footer.php'; ?>