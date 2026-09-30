<?php
require_once 'database.php';
require_once 'auth.php';

// Solo acceso para jueces
checkAccess(['Juez']);

$judgeUserId = $_SESSION['user_id'];

// ==========================================
// OBTENER DATOS PARA EL DASHBOARD
// ==========================================

// 1. Eventos donde el juez tiene asignaciones
$stmtEvents = $pdo->prepare("
    SELECT DISTINCT e.id, e.name, e.start_date, e.end_date
    FROM judge_student_assignments jsa
    INNER JOIN registrations r ON jsa.registration_id = r.id
    INNER JOIN events e ON r.event_id = e.id
    WHERE jsa.judge_id = ?
    ORDER BY e.id DESC
");
$stmtEvents->execute([$judgeUserId]);
$myEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// 2. Total de estudiantes asignados al juez
$stmtTotalAssigned = $pdo->prepare("
    SELECT COUNT(DISTINCT jsa.registration_id)
    FROM judge_student_assignments jsa
    INNER JOIN registrations r ON jsa.registration_id = r.id
    WHERE jsa.judge_id = ?
");
$stmtTotalAssigned->execute([$judgeUserId]);
$totalAssigned = $stmtTotalAssigned->fetchColumn();

// 3. Total de evaluaciones ya realizadas por el juez
$stmtEvalCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM judge_evaluations
    WHERE judge_user_id = ?
");
$stmtEvalCount->execute([$judgeUserId]);
$totalEvaluations = $stmtEvalCount->fetchColumn();

// 4. Detalle de evaluaciones por evento (para la tabla)
$stmtDetail = $pdo->prepare("
    SELECT 
        e.id AS event_id,
        e.name AS event_name,
        COUNT(DISTINCT jsa.registration_id) AS total_students,
        COUNT(DISTINCT CASE WHEN je.id IS NOT NULL THEN jsa.registration_id END) AS evaluated_students
    FROM judge_student_assignments jsa
    INNER JOIN registrations r ON jsa.registration_id = r.id
    INNER JOIN events e ON r.event_id = e.id
    LEFT JOIN judge_evaluations je ON je.registration_id = r.id AND je.judge_user_id = ?
    WHERE jsa.judge_id = ?
    GROUP BY e.id, e.name
    ORDER BY e.id DESC
");
$stmtDetail->execute([$judgeUserId, $judgeUserId]);
$eventDetails = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

// 5. Obtener nombre del juez
$stmtJudge = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmtJudge->execute([$judgeUserId]);
$judgeName = $stmtJudge->fetchColumn();

require_once 'header.php';
require_once 'sidebar.php';
?>

<div class="container py-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Panel del Juez</h2>
            <p class="text-muted mb-0">Bienvenido, <strong><?= htmlspecialchars($judgeName) ?></strong></p>
        </div>
        <a href="juez_evaluacion.php" class="btn btn-primary btn-lg">
            <i class="bi bi-clipboard-check"></i> Ir a Evaluar
        </a>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-primary"></i>
                    <h3 class="mt-2 mb-0"><?= $totalAssigned ?></h3>
                    <p class="text-muted mb-0">Estudiantes Asignados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-success h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check2-circle fs-1 text-success"></i>
                    <h3 class="mt-2 mb-0"><?= $totalEvaluations ?></h3>
                    <p class="text-muted mb-0">Evaluaciones Realizadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-event fs-1 text-warning"></i>
                    <h3 class="mt-2 mb-0"><?= count($myEvents) ?></h3>
                    <p class="text-muted mb-0">Eventos Asignados</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle por evento -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-list-check"></i> Progreso por Evento</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($eventDetails)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2">No tienes asignaciones en ningún evento.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Evento</th>
                                <th class="text-center">Estudiantes</th>
                                <th class="text-center">Evaluados</th>
                                <th class="text-center">Pendientes</th>
                                <th>Progreso</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventDetails as $ev): 
                                $total = intval($ev['total_students']);
                                $evaluated = intval($ev['evaluated_students']);
                                $pending = $total - $evaluated;
                                $percent = ($total > 0) ? round(($evaluated / $total) * 100) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($ev['event_name']) ?></strong>
                                    </td>
                                    <td class="text-center"><?= $total ?></td>
                                    <td class="text-center text-success"><?= $evaluated ?></td>
                                    <td class="text-center text-warning"><?= $pending ?></td>
                                    <td style="width: 200px;">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?= $percent == 100 ? 'bg-success' : 'bg-primary' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $percent ?>%;" 
                                                 aria-valuenow="<?= $percent ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= $percent ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="juez_evaluacion.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i> Evaluar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>