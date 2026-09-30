<?php
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Crear token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. AJAX: Cambiar estado de la inscripción (Activar/Cancelar/Rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'], $_POST['status'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'msg' => 'Token CSRF inválido']);
        exit;
    }

    $id = intval($_POST['id']);
    $nuevo_status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE registrations SET status = :status, updated_at = NOW() WHERE id = :id");
    if ($stmt->execute([':status' => $nuevo_status, ':id' => $id])) {
        echo json_encode(['success' => true, 'nuevo_status' => $nuevo_status]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error en base de datos']);
    }
    exit;
}

// 2. AJAX: Validar pago e inscripción simultáneamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate_payment' && isset($_POST['id'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'msg' => 'Token CSRF inválido']);
        exit;
    }

    $registration_id = intval($_POST['id']);
    $username = $_SESSION['username'] ?? 'admin';

    try {
        $pdo->beginTransaction();

        // Actualizar Pago
        $stmtPay = $pdo->prepare("UPDATE payments SET status = 'verificado', verified_by = :username, verified_at = NOW() WHERE registration_id = :reg_id");
        $stmtPay->execute([':username' => $username, ':reg_id' => $registration_id]);

        // Actualizar Inscripción a Confirmado
        $stmtReg = $pdo->prepare("UPDATE registrations SET status = 'confirmado', verified_by = :username WHERE id = :reg_id");
        $stmtReg->execute([':username' => $username, ':reg_id' => $registration_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'Error al procesar la validación: ' . $e->getMessage()]);
    }
    exit;
}

// 3. AJAX: Rechazar pago y liberar cupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_payment' && isset($_POST['id'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'msg' => 'Token CSRF inválido']);
        exit;
    }

    $registration_id = intval($_POST['id']);
    $username = $_SESSION['username'] ?? 'admin';

    try {
        $pdo->beginTransaction();

        // Obtener category_id para recalcular cupo
        $stmtCat = $pdo->prepare("SELECT category_id, event_id FROM registrations WHERE id = ?");
        $stmtCat->execute([$registration_id]);
        $regInfo = $stmtCat->fetch(PDO::FETCH_ASSOC);
        if (!$regInfo) {
            throw new Exception("Registro no encontrado.");
        }

        // Actualizar pago a rechazado
        $stmtPay = $pdo->prepare("UPDATE payments SET status = 'rechazado', verified_by = :username, verified_at = NOW() WHERE registration_id = :reg_id");
        $stmtPay->execute([':username' => $username, ':reg_id' => $registration_id]);

        // Actualizar inscripción a cancelado
        $stmtReg = $pdo->prepare("UPDATE registrations SET status = 'cancelado', verified_by = :username WHERE id = :reg_id");
        $stmtReg->execute([':username' => $username, ':reg_id' => $registration_id]);

        // Recalcular registered_count de la categoría (excluyendo rechazados)
        $stmtUpdateCount = $pdo->prepare("
            UPDATE event_categories ec
            SET ec.registered_count = (
                SELECT COUNT(*) FROM registrations r
                LEFT JOIN payments p ON r.id = p.registration_id
                WHERE r.category_id = ec.id AND r.event_id = ?
                AND (p.status IS NULL OR p.status != 'rechazado')
            )
            WHERE ec.id = ?
        ");
        $stmtUpdateCount->execute([$regInfo['event_id'], $regInfo['category_id']]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => 'Error al procesar el rechazo: ' . $e->getMessage()]);
    }
    exit;
}

// Obtener la lista de eventos/hackathons para el desplegable de filtro
$stmtEvents = $pdo->query("SELECT id, name FROM events ORDER BY id DESC");
$eventsList = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// Capturar el filtro de evento seleccionado (0 = Todos)
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Si no se especificó un evento (0), seleccionar el último evento disponible
if ($selected_event_id === 0 && !empty($eventsList)) {
    $selected_event_id = $eventsList[0]['id']; // El primer elemento es el más reciente
}

// Construir la consulta con o sin filtro de evento
$whereClause = "";
$params = [];

if ($selected_event_id > 0) {
    $whereClause = " WHERE r.event_id = :event_id ";
    $params[':event_id'] = $selected_event_id;
}

$query = "
    SELECT 
        r.id AS registration_id,
        r.registration_number,
        r.status AS registration_status,
        r.shirt_size,
        r.expectations,
        
        -- Evento y Categoría
        e.name AS event_title,
        ec.name AS category_name,
        
        -- Participante
        p.full_name,
        p.last_name,
        p.document_type,
        p.document_number,
        p.email,
        p.phone,
        p.state,
        p.city,
        p.institution,
        p.education_level,
        p.grade,
        p.birth_date,
        p.age,
        p.gender,
        p.address,
        p.microbit_experience,
        p.is_minor,
        p.guardian_name,
        p.guardian_document,
        p.guardian_email,
        p.guardian_phone,
        
        -- Pago
        pay.payment_method,
        pay.payment_phone,
        pay.payment_bank,
        pay.payment_reference,
        pay.payment_amount_bs,
        pay.bcv_rate,
        pay.payment_proof_path,
        pay.status AS payment_status
    FROM registrations r
    INNER JOIN participants p ON r.participant_id = p.id
    INNER JOIN events e ON r.event_id = e.id
    INNER JOIN event_categories ec ON r.category_id = ec.id
    LEFT JOIN payments pay ON pay.registration_id = r.id
    {$whereClause}
    ORDER BY r.id DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'sidebar.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 gap-3">
                <h5 class="card-title fw-bold mb-0">Gestión de Inscripciones y Pagos</h5>

                <!-- Formulario Filtro por Evento -->
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <label for="event_id" class="form-label mb-0 fw-semibold text-nowrap"><i class="bi bi-funnel-fill text-primary me-1"></i> Filtrar Evento:</label>
                    <select name="event_id" id="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0" <?= $selected_event_id === 0 ? 'selected' : '' ?>>-- Todos los Eventos --</option>
                        <?php foreach ($eventsList as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= $selected_event_id === $ev['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ev['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_event_id > 0): ?>
                        <a href="registros.php" class="btn btn-outline-secondary btn-sm" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mt-2" id="registrationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Validación</th>
                                <th>N° Registro</th>
                                <th>Evento</th>
                                <th>Participante</th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Ciudad</th>
                                <th>Institución</th>
                                <th>Nivel</th>
                                <th>Categoría</th>
                                <th>F. Nacimiento</th>
                                <th>Edad</th>
                                <th>Género</th>
                                <th>Dirección</th>
                                <th>Grado</th>
                                <th>Experiencia</th>
                                <th>Expectativa</th>
                                <th>Talla</th>
                                <th>¿Menor?</th>
                                <th>Representante</th>
                                <th>Doc. Rep.</th>
                                <th>Email Rep.</th>
                                <th>Teléfono Rep.</th>
                                <th>Método Pago</th>
                                <th>Tel. Pago</th>
                                <th>Banco</th>
                                <th>Referencia</th>
                                <th>Monto (Bs)</th>
                                <th>Tasa BCV</th>
                                <!-- <th>Estado Reg.</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
                                    <td>
                                        <?php if ($r['payment_status'] === 'verificado'): ?>
                                            <button class="btn btn-success btn-sm validate-payment-btn"
                                                data-id="<?= $r['registration_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?>"
                                                data-payment-method="<?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?>"
                                                data-payment-phone="<?= htmlspecialchars($r['payment_phone'] ?? 'N/A') ?>"
                                                data-payment-bank="<?= htmlspecialchars($r['payment_bank'] ?? 'N/A') ?>"
                                                data-payment-reference="<?= htmlspecialchars($r['payment_reference'] ?? 'N/A') ?>"
                                                data-payment-amount="<?= htmlspecialchars($r['payment_amount_bs'] ?? '0.00') ?>"
                                                data-bcv-rate="<?= htmlspecialchars($r['bcv_rate'] ?? '0.00') ?>"
                                                data-payment-proof="<?= htmlspecialchars($r['payment_proof_path'] ?? '') ?>"
                                                data-payment-verified="1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Pago Verificado
                                            </button>
                                        <?php elseif ($r['payment_status'] === 'rechazado'): ?>
                                            <span class="badge bg-danger">Rechazado</span>
                                        <?php else: ?>
                                            <button class="btn btn-warning btn-sm validate-payment-btn"
                                                data-id="<?= $r['registration_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?>"
                                                data-payment-method="<?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?>"
                                                data-payment-phone="<?= htmlspecialchars($r['payment_phone'] ?? 'N/A') ?>"
                                                data-payment-bank="<?= htmlspecialchars($r['payment_bank'] ?? 'N/A') ?>"
                                                data-payment-reference="<?= htmlspecialchars($r['payment_reference'] ?? 'N/A') ?>"
                                                data-payment-amount="<?= htmlspecialchars($r['payment_amount_bs'] ?? '0.00') ?>"
                                                data-bcv-rate="<?= htmlspecialchars($r['bcv_rate'] ?? '0.00') ?>"
                                                data-payment-proof="<?= htmlspecialchars($r['payment_proof_path'] ?? '') ?>"
                                                data-payment-verified="0">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Validar Pago
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm reject-payment-btn ms-1"
                                                data-id="<?= $r['registration_id'] ?>"
                                                title="Rechazar pago y liberar cupo">
                                                <i class="bi bi-x-circle"></i> Rechazar
                                            </button>
                                        <?php endif; ?>
                                    </td>

                                    <td><strong><?= htmlspecialchars($r['registration_number']) ?></strong></td>
                                    <td><span class="badge bg-primary text-wrap"><?= htmlspecialchars($r['event_title']) ?></span></td>
                                    <td><?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($r['document_type']) . '-' . $r['document_number']) ?></td>
                                    <td><?= htmlspecialchars($r['email']) ?></td>
                                    <td><?= htmlspecialchars($r['phone']) ?></td>
                                    <td><?= htmlspecialchars($r['state']) ?></td>
                                    <td><?= htmlspecialchars($r['city']) ?></td>
                                    <td><?= htmlspecialchars($r['institution']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($r['education_level'])) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['category_name']) ?></span></td>
                                    <td><?= htmlspecialchars($r['birth_date']) ?></td>
                                    <td><?= htmlspecialchars($r['age']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($r['gender'])) ?></td>
                                    <td><?= htmlspecialchars($r['address']) ?></td>
                                    <td><?= htmlspecialchars($r['grade'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($r['microbit_experience'])) ?></td>
                                    <td><?= htmlspecialchars($r['expectations'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['shirt_size']) ?></td>
                                    <td><?= $r['is_minor'] ? "✅" : "❌"; ?></td>
                                    <td><?= htmlspecialchars($r['guardian_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['guardian_document'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['guardian_email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['guardian_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['payment_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['payment_bank'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['payment_reference'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(number_format((float)$r['payment_amount_bs'], 2, ',', '.')) ?></td>
                                    <td><?= htmlspecialchars(number_format((float)$r['bcv_rate'], 2, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Validación de Pago -->
<div class="modal fade" id="paymentValidationModal" tabindex="-1" aria-labelledby="paymentValidationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="paymentValidationModalLabel">Detalle de Comprobante de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <h5 id="modalFullName" class="fw-bold mb-3 text-primary"></h5>
                <div class="row gy-3">
                    <div class="col-md-6 fs-6">
                        <p class="mb-1"><strong>Método:</strong> <span id="modalPaymentMethod"></span></p>
                        <p class="mb-1"><strong>Banco emisor:</strong> <span id="modalPaymentBank"></span></p>
                        <p class="mb-1"><strong>Teléfono Origen:</strong> <span id="modalPaymentPhone"></span></p>
                        <p class="mb-1"><strong>N° Referencia:</strong> <span id="modalPaymentReference" class="badge bg-secondary fs-6"></span></p>
                        <p class="mb-1"><strong>Monto Depositado:</strong> <span id="modalPaymentAmount" class="fw-bold text-success"></span> Bs.</p>
                        <p class="mb-1"><strong>Tasa BCV Aplicada:</strong> <span id="modalBcvRate"></span> Bs.</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <label class="form-label d-block fw-bold text-muted">Comprobante Adjunto</label>
                        <a id="modalPaymentProofLink" href="#" target="_blank">
                            <img id="modalPaymentProof" src="" alt="Comprobante de Pago" class="img-fluid rounded border shadow-sm" style="max-height: 250px; object-fit: contain;">
                        </a>
                    </div>
                </div>
                <div id="validationSection" class="mt-4 text-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
    var csrfToken = '<?= $_SESSION['csrf_token']; ?>';

    $(document).ready(function() {
        var table = $('#registrationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['excel'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/es-ES.json'
            }
        });

        // Abrir Modal de Validación
        $(document).on('click', '.validate-payment-btn', function() {
            var btn = $(this);
            $('#modalFullName').text(btn.data('name'));
            $('#modalPaymentMethod').text(btn.data('payment-method'));
            $('#modalPaymentBank').text(btn.data('payment-bank'));
            $('#modalPaymentPhone').text(btn.data('payment-phone'));
            $('#modalPaymentReference').text(btn.data('payment-reference'));
            $('#modalPaymentAmount').text(btn.data('payment-amount'));
            $('#modalBcvRate').text(btn.data('bcv-rate'));

            var proofPath = btn.data('payment-proof');
            if (proofPath) {
                if (
                    !proofPath.startsWith('http') &&
                    !proofPath.startsWith('//') &&
                    !proofPath.startsWith('/') &&
                    !proofPath.startsWith('../')
                ) {
                    proofPath = '../' + proofPath;
                }
                $('#modalPaymentProof').attr('src', proofPath).show();
                $('#modalPaymentProofLink').attr('href', proofPath);
            } else {
                $('#modalPaymentProof').hide();
            }

            var validated = btn.data('payment-verified');
            var html = '';
            if (validated == 0) {
                html = '<div class="alert alert-warning mb-3 d-flex align-items-center justify-content-center">' +
                    '<i class="bi bi-exclamation-circle me-2 fs-4"></i> Pago Pendiente por Verificación' +
                    '</div>' +
                    '<button id="btnConfirmValidate" class="btn btn-success btn-lg px-4" data-id="' + btn.data('id') + '"><i class="bi bi-check2-circle me-1"></i> Aprobar Pago e Inscripción</button>';
            } else {
                html = '<div class="alert alert-success mb-3 d-flex align-items-center justify-content-center">' +
                    '<i class="bi bi-check-circle me-2 fs-4"></i> Pago Verificado Correctamente' +
                    '</div>';
            }
            $('#validationSection').html(html);

            var modal = new bootstrap.Modal(document.getElementById('paymentValidationModal'));
            modal.show();
        });

        // Confirmar Validación de Pago AJAX
        $(document).on('click', '#btnConfirmValidate', function() {
            var id = $(this).data('id');

            if (!confirm('¿Confirma que ha verificado los fondos en la cuenta bancaria?')) return;

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'validate_payment',
                    id: id,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.msg || 'No se pudo actualizar'));
                    }
                },
                error: function() {
                    alert('Error en la petición AJAX');
                }
            });
        });

        // Rechazar Pago AJAX
        $(document).on('click', '.reject-payment-btn', function() {
            var id = $(this).data('id');

            if (!confirm('¿Está seguro de rechazar este pago? Se liberará el cupo y la inscripción será cancelada.')) return;

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'reject_payment',
                    id: id,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.msg || 'No se pudo actualizar'));
                    }
                },
                error: function() {
                    alert('Error en la petición AJAX');
                }
            });
        });

        // Toggle Status (Confirmar / Cancelar Registro)
        $(document).on('click', '.toggle-status', function() {
            var boton = $(this);
            var id = boton.data('id');
            var status = boton.data('status');

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'toggle_status',
                    id: id,
                    status: status,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.msg || 'No se pudo actualizar'));
                    }
                },
                error: function() {
                    alert('Error en la petición AJAX');
                }
            });
        });
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />