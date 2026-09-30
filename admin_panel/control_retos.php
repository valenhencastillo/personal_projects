<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores
checkAccess(['Admin']);

// Obtener evento actual desde sesión
$currentEventId = $_SESSION['current_event_id'] ?? null;
if (!$currentEventId) {
    require_once 'header.php';
    require_once 'sidebar.php';
    echo '<div class="container py-4"><div class="alert alert-warning">Debe seleccionar un evento en el menú lateral antes de controlar los retos.</div></div>';
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

        if ($action === 'toggle_challenge_status') {
            $challengeId = intval($_POST['challenge_id'] ?? 0);
            if (!$challengeId) {
                throw new Exception('Reto no especificado.');
            }

            // Obtener categoría y evento del reto
            $stmt = $pdo->prepare("SELECT category_id, event_id, is_active FROM challenges WHERE id = ?");
            $stmt->execute([$challengeId]);
            $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$challenge) {
                throw new Exception('Reto no encontrado.');
            }

            // Verificar que el reto pertenezca al evento actual
            if ($challenge['event_id'] != $currentEventId) {
                throw new Exception('El reto no pertenece al evento seleccionado.');
            }

            $newStatus = $challenge['is_active'] ? 0 : 1;

            $pdo->beginTransaction();

            if ($newStatus === 1) {
                // Desactivar todos los retos de la misma categoría y evento
                $stmtDeactivate = $pdo->prepare("UPDATE challenges SET is_active = 0 WHERE category_id = ? AND event_id = ?");
                $stmtDeactivate->execute([$challenge['category_id'], $challenge['event_id']]);
            }

            // Activar/desactivar el reto seleccionado
            $stmtUpdate = $pdo->prepare("UPDATE challenges SET is_active = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $challengeId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'challenge_id' => $challengeId,
                'is_active' => $newStatus,
                'message' => $newStatus ? 'Reto activado correctamente.' : 'Reto desactivado correctamente.'
            ]);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Obtener categorías y retos del evento
$stmtCategories = $pdo->prepare("
    SELECT c.id, c.name, c.description
    FROM event_categories c
    WHERE c.event_id = ?
    ORDER BY c.name
");
$stmtCategories->execute([$currentEventId]);
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

// Para cada categoría, obtener sus retos con estado
foreach ($categories as &$cat) {
    $stmtChallenges = $pdo->prepare("
        SELECT id, title, description, is_active
        FROM challenges
        WHERE category_id = ? AND event_id = ?
        ORDER BY id
    ");
    $stmtChallenges->execute([$cat['id'], $currentEventId]);
    $cat['challenges'] = $stmtChallenges->fetchAll(PDO::FETCH_ASSOC);
}
unset($cat);

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
    }

    .control-wrapper {
        padding: 1rem;
    }

    .control-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .control-header h2 {
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .category-card {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .category-card .card-header {
        background: linear-gradient(135deg, #6C63FF, #8B82FF);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    .category-card .card-body {
        padding: 1.5rem;
    }

    .challenge-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        background: #fafafa;
        transition: background 0.2s;
    }

    .challenge-item.active {
        background: #e8f8f0;
        border-color: #2ECC71;
    }

    .challenge-title {
        font-weight: 600;
        color: var(--dark);
    }

    .challenge-desc {
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    .toggle-btn {
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .toggle-btn.activate {
        background: #2ECC71;
        color: white;
    }

    .toggle-btn.deactivate {
        background: #e74c3c;
        color: white;
    }

    .toggle-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
</style>

<div class="control-wrapper">
    <div class="control-header">
        <h2><i class="bi bi-toggles"></i> Control de Retos por Categoría</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-round">Volver al Panel</a>
    </div>

    <div id="alertContainer"></div>

    <?php if (empty($categories)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Este evento no tiene categorías configuradas.
        </div>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <div class="category-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-folder"></i> <?= htmlspecialchars($cat['name']) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($cat['challenges'])): ?>
                        <p class="text-muted">No hay retos definidos para esta categoría.</p>
                    <?php else: ?>
                        <?php foreach ($cat['challenges'] as $challenge): ?>
                            <div class="challenge-item <?= $challenge['is_active'] ? 'active' : '' ?>" id="challenge-<?= $challenge['id'] ?>">
                                <div>
                                    <div class="challenge-title"><?= htmlspecialchars($challenge['title']) ?></div>
                                    <?php if ($challenge['description']): ?>
                                        <div class="challenge-desc"><?= htmlspecialchars($challenge['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <button
                                    class="toggle-btn <?= $challenge['is_active'] ? 'deactivate' : 'activate' ?>"
                                    onclick="toggleChallengeStatus(<?= $challenge['id'] ?>, <?= $challenge['is_active'] ?>)"
                                    data-challenge-id="<?= $challenge['id'] ?>">
                                    <?= $challenge['is_active'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    async function toggleChallengeStatus(challengeId, currentStatus) {
        const newStatus = currentStatus ? 0 : 1;
        const action = newStatus ? 'activar' : 'desactivar';

        if (!confirm(`¿Seguro que deseas ${action} este reto?`)) return;

        const formData = new FormData();
        formData.append('action', 'toggle_challenge_status');
        formData.append('challenge_id', challengeId);

        try {
            const res = await fetch('', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                showAlert(json.message, 'success');
                // Recargar la página para reflejar cambios
                setTimeout(() => location.reload(), 500);
            } else {
                showAlert(json.message, 'danger');
            }
        } catch (e) {
            showAlert('Error al procesar la solicitud.', 'danger');
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