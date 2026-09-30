<?php
require_once 'database.php';

// Verificación de sesión y seguridad
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validar que se reciba el ID del evento
$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$eventId) {
    header("Location: eventos.php");
    exit();
}

// 1. Obtener la información del evento
$stmtEvent = $pdo->prepare("SELECT name, start_date, end_date FROM events WHERE id = ?");
$stmtEvent->execute([$eventId]);
$event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("<div class='container py-5'><h3>Error: El evento no existe.</h3><a href='eventos.php' class='btn btn-primary'>Volver</a></div>");
}

// 2. Obtener los participantes registrados y sus datos personales
$sql = "
    SELECT 
        r.id,
        r.registration_number,
        r.category_id,
        r.shirt_size,
        r.attendance_status,
        r.attendance_time,
        c.name AS category_name,
        p.full_name,
        p.last_name,
        p.document_type,
        p.document_number,
        p.age,
        p.education_level,
        p.institution,
        p.microbit_experience,
        p.email,
        p.phone,
        p.is_minor
    FROM registrations r
    INNER JOIN participants p ON r.participant_id = p.id
    LEFT JOIN event_categories c ON r.category_id = c.id
    WHERE r.event_id = ?
    ORDER BY r.registration_number ASC, r.id ASC
";
$stmtRegs = $pdo->prepare($sql);
$stmtRegs->execute([$eventId]);
$participants = $stmtRegs->fetchAll(PDO::FETCH_ASSOC);

// 3. Agrupar por registration_number (si es necesario; aquí cada registro es individual)
$teams = [];
foreach ($participants as $p) {
    $teamId = $p['registration_number'];
    
    if (!isset($teams[$teamId])) {
        $teams[$teamId] = [
            'team_name' => $teamId,
            'category' => $p['category_name'] ?? 'Sin asignar',
            'members' => []
        ];
    }
    $teams[$teamId]['members'][] = $p;
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> Participantes Inscritos</h2>
            <h5 class="text-secondary mb-0">
                <?= htmlspecialchars($event['name']) ?> 
                <small class="text-muted">(Del <?= $event['start_date'] ?> al <?= $event['end_date'] ?>)</small>
            </h5>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Eventos
        </a>
    </div>

    <?php if (empty($teams)): ?>
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle-fill"></i> Aún no hay equipos ni estudiantes registrados en este evento.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($teams as $teamId => $teamData): ?>
                <div class="col-12 mb-4">
                    <div class="card shadow-sm border-secondary">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-diagram-3-fill"></i> Equipo: <strong><?= htmlspecialchars($teamData['team_name']) ?></strong>
                            </h5>
                            <span class="badge bg-primary fs-6"><?= htmlspecialchars($teamData['category']) ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre Completo</th>
                                            <th>Documento</th>
                                            <th>Edad / Nivel</th>
                                            <th>Institución</th>
                                            <th>Exp. Micro:bit</th>
                                            <th>Contacto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamData['members'] as $index => $member): ?>
                                            <tr>
                                                <td class="text-muted fw-bold"><?= $index + 1 ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($member['full_name'] . ' ' . $member['last_name']) ?></strong>
                                                    <?php if ($member['is_minor'] == 1): ?>
                                                        <span class="badge bg-warning text-dark ms-1" title="Menor de edad" data-bs-toggle="tooltip">Menor</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-uppercase text-muted"><?= htmlspecialchars($member['document_type']) ?>:</small><br>
                                                    <?= htmlspecialchars($member['document_number']) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($member['age']) ?> años<br>
                                                    <small class="text-muted text-capitalize"><?= htmlspecialchars($member['education_level']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($member['institution']) ?></td>
                                                <td>
                                                    <?php 
                                                        $expClass = 'bg-secondary';
                                                        if ($member['microbit_experience'] == 'avanzada') $expClass = 'bg-success';
                                                        if ($member['microbit_experience'] == 'intermedia') $expClass = 'bg-info text-dark';
                                                        if ($member['microbit_experience'] == 'basica') $expClass = 'bg-warning text-dark';
                                                    ?>
                                                    <span class="badge <?= $expClass ?> text-uppercase">
                                                        <?= htmlspecialchars($member['microbit_experience']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($member['email']) ?><br>
                                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($member['phone']) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Inicializar tooltips de Bootstrap si se utilizan
    document.addEventListener("DOMContentLoaded", function(){
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(element){
            return new bootstrap.Tooltip(element);
        });
    });
</script>

<?php require_once 'footer.php'; ?>