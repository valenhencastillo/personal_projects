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

// Verificar si es petición AJAX para actualizar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'], $_POST['csrf_token'])) {
    // Validar token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'msg' => 'Token CSRF inválido']);
        exit;
    }

    // Verificar usuario autenticado (ejemplo simple)
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'No autorizado']);
        exit;
    }

    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    $nuevo_status = ($status === 1) ? 0 : 1;

    // Consulta preparada para evitar inyección SQL
    $stmt = $pdo->prepare("UPDATE registrations SET status = :nuevo_status WHERE id = :id");
    $stmt->bindParam(':nuevo_status', $nuevo_status, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'nuevo_status' => $nuevo_status]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error en base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate_payment' && isset($_POST['id'], $_POST['csrf_token'])) {
    // Validar token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'msg' => 'Token CSRF inválido']);
        exit;
    }

    // Verificar usuario autenticado
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'No autorizado']);
        exit;
    }

    $id = intval($_POST['id']);

    // Actualizar el campo payment_verified a 1 (validado)
    $stmt = $pdo->prepare("UPDATE registrations SET payment_verified = 1 WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error en base de datos']);
    }
    exit;
}

// Obtener registros
$stmt = $pdo->query("SELECT * FROM registrations WHERE status = 1");
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'sidebar.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Registros</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table mt-3" id="registrationsTable">
                        <thead>
                            <tr>
                                <th>Acción</th>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Ciudad</th>
                                <th>Institución</th>
                                <th>Nivel Educativo</th>
                                <th>Categoría</th>
                                <th>Fecha Nacimiento</th>
                                <th>Edad</th>
                                <th>Género</th>
                                <th>Dirección</th>
                                <th>Grado</th>
                                <th>Experiencia</th>
                                <th>Expectativa</th>
                                <th>Talla</th>
                                <th>¿Menor?</th>
                                <th>Nombre Representante</th>
                                <th>Documento Representante</th>
                                <th>Email Representante</th>
                                <th>Teléfono Representante</th>
                                <th>Método Pago</th>
                                <th>Teléfono Pago</th>
                                <th>Referencia</th>
                                <th>Monto</th>
                                <th>Tasa BCV</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
                                    <td>
                                        <?php if ($r['payment_verified'] == 0): ?>
                                            <button
                                                class="btn btn-warning btn-sm validate-payment-btn"
                                                data-id="<?= $r['id'] ?>"
                                                data-name="<?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?>"
                                                data-payment-method="<?= htmlspecialchars($r['payment_method']) ?>"
                                                data-payment-phone="<?= htmlspecialchars($r['payment_phone']) ?>"
                                                data-payment-reference="<?= htmlspecialchars($r['payment_reference']) ?>"
                                                data-payment-amount="<?= htmlspecialchars($r['payment_amount_bs']) ?>"
                                                data-bcv-rate="<?= htmlspecialchars($r['bcv_rate']) ?>"
                                                data-payment-proof="<?= htmlspecialchars($r['payment_proof_path']) ?>"
                                                data-payment-verified="<?= $r['payment_verified'] ?>">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Validar Pago
                                            </button>
                                        <?php else: ?>
                                            <button
                                                class="btn btn-success btn-sm validate-payment-btn"
                                                data-id="<?= $r['id'] ?>"
                                                data-name="<?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?>"
                                                data-payment-method="<?= htmlspecialchars($r['payment_method']) ?>"
                                                data-payment-phone="<?= htmlspecialchars($r['payment_phone']) ?>"
                                                data-payment-reference="<?= htmlspecialchars($r['payment_reference']) ?>"
                                                data-payment-amount="<?= htmlspecialchars($r['payment_amount_bs']) ?>"
                                                data-bcv-rate="<?= htmlspecialchars($r['bcv_rate']) ?>"
                                                data-payment-proof="<?= htmlspecialchars($r['payment_proof_path']) ?>"
                                                data-payment-verified="<?= $r['payment_verified'] ?>">
                                                <i class="bi bi-check-circle-fill me-1"></i> Pago Verificado
                                            </button>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['full_name'] . ' ' . $r['last_name']) ?></td>
                                    <td><?= htmlspecialchars($r['document_type'] . ' ' . $r['document_number']) ?></td>
                                    <td><?= htmlspecialchars($r['email']) ?></td>
                                    <td><?= htmlspecialchars($r['phone']) ?></td>
                                    <td><?= htmlspecialchars($r['state']) ?></td>
                                    <td><?= htmlspecialchars($r['city']) ?></td>
                                    <td><?= htmlspecialchars($r['institution']) ?></td>
                                    <td><?= htmlspecialchars($r['education_level']) ?></td>
                                    <td><?= htmlspecialchars($r['category']) ?></td>
                                    <td><?= htmlspecialchars($r['birth_date']) ?></td>
                                    <td><?= htmlspecialchars($r['age']) ?></td>
                                    <td><?= htmlspecialchars($r['gender']) ?></td>
                                    <td><?= htmlspecialchars($r['address']) ?></td>
                                    <td><?= htmlspecialchars($r['grade']) ?></td>
                                    <td><?= htmlspecialchars($r['microbit_experience']) ?></td>
                                    <td><?= htmlspecialchars($r['expectations']) ?></td>
                                    <td><?= htmlspecialchars($r['shirt_size']) ?></td>
                                    <td><?= $r['is_minor'] ? "✅" : "❌"; ?></td>
                                    <td><?= htmlspecialchars($r['guardian_name']) ?></td>
                                    <td><?= htmlspecialchars($r['guardian_document']) ?></td>
                                    <td><?= htmlspecialchars($r['guardian_email']) ?></td>
                                    <td><?= htmlspecialchars($r['guardian_phone']) ?></td>
                                    <td><?= htmlspecialchars($r['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($r['payment_phone']) ?></td>
                                    <td><?= htmlspecialchars($r['payment_reference']) ?></td>
                                    <td><?= htmlspecialchars($r['payment_amount_bs']) ?></td>
                                    <td><?= htmlspecialchars($r['bcv_rate']) ?></td>
                                    <td>
                                        <?php if ($r['status'] == 1) { ?>
                                            <button class="btn btn-danger btn-sm toggle-status" data-id="<?= $r['id'] ?>" data-status="1">Desactivar</button>
                                        <?php } else { ?>
                                            <button class="btn btn-success btn-sm toggle-status" data-id="<?= $r['id'] ?>" data-status="0">Activar</button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentValidationModal" tabindex="-1" aria-labelledby="paymentValidationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="paymentValidationModalLabel">Validar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <h5 id="modalFullName" class="fw-bold mb-3"></h5>
                <div class="row gy-2">
                    <div class="col-md-6">
                        <p><strong>Método de Pago:</strong> <span id="modalPaymentMethod"></span></p>
                        <p><strong>Teléfono Pago:</strong> <span id="modalPaymentPhone"></span></p>
                        <p><strong>Referencia:</strong> <span id="modalPaymentReference"></span></p>
                        <p><strong>Monto (Bs):</strong> <span id="modalPaymentAmount"></span></p>
                        <p><strong>Tasa BCV:</strong> <span id="modalBcvRate"></span></p>
                    </div>
                    <div class="col-md-6 text-center">
                        <img id="modalPaymentProof" src="" alt="Comprobante de Pago" class="img-fluid rounded border" style="max-height: 250px;">
                    </div>
                </div>
                <div id="validationSection" class="mt-3 text-center">
                    <!-- Aquí se mostrará el estado y botón para validar -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'footer.php'; ?>

<script>
    var csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
    $(document).ready(function() {
        var table = $('#registrationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['excel'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/es-ES.json'
            }
        });

        // Abrir modal con datos del pago al presionar "Validar Pago"
        $('.validate-payment-btn').click(function() {
            var btn = $(this);
            $('#modalFullName').text(btn.data('name'));
            $('#modalPaymentMethod').text(btn.data('payment-method'));
            $('#modalPaymentPhone').text(btn.data('payment-phone'));
            $('#modalPaymentReference').text(btn.data('payment-reference'));
            $('#modalPaymentAmount').text(btn.data('payment-amount'));
            $('#modalBcvRate').text(btn.data('bcv-rate'));
            $('#modalPaymentProof').attr('src', btn.data('payment-proof'));

            // Estado validación y botón
            var validated = btn.data('payment-verified');
            var html = '';
            if (validated == 0) {
                html = '<div class="alert alert-warning mb-3 d-flex align-items-center justify-content-center">' +
                    '<i class="bi bi-exclamation-circle me-2 fs-4"></i> Falta Validar Pago' +
                    '</div>' +
                    '<button id="btnConfirmValidate" class="btn btn-success">Marcar como Validado</button>';
            } else {
                html = '<div class="alert alert-success mb-3 d-flex align-items-center justify-content-center">' +
                    '<i class="bi bi-check-circle me-2 fs-4"></i> Pago Validado' +
                    '</div>';
            }
            $('#validationSection').html(html);

            // Guardar id para actualizar
            $('#btnConfirmValidate').data('id', btn.data('id'));

            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('paymentValidationModal'));
            modal.show();
        });

        // Evento para confirmar validación del pago
        $(document).on('click', '#btnConfirmValidate', function() {
            var btn = $(this);
            var id = btn.data('id');

            // Mostrar confirmación antes de proceder
            if (!confirm('¿Está seguro de que desea marcar este pago como validado?')) {
                return; // si usuario cancela, no se hace nada
            }

            $.ajax({
                url: '', // mismo archivo PHP que maneja actualización
                type: 'POST',
                data: {
                    action: 'validate_payment',
                    id: id,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Actualizar interfaz modal
                        $('#validationSection').html('<div class="alert alert-success mb-3 d-flex align-items-center justify-content-center"><i class="bi bi-check-circle me-2 fs-4"></i> Pago Validado</div>');

                        // Actualizar botón en la fila
                        var btn = $('button.validate-payment-btn[data-id="' + id + '"]');
                        btn.data('payment-verified', 1); // Cambiar data attribute a 1
                        btn.removeClass('btn-warning').addClass('btn-success'); // Cambiar color al verde
                        btn.html('<i class="bi bi-check-circle-fill me-1"></i> Pago Verificado'); // Cambiar texto e ícono
                    } else {
                        alert('Error: ' + (response.msg || 'No se pudo actualizar'));
                    }
                },
                error: function() {
                    alert('Error en la petición Ajax');
                }
            });
        });

        // Función toggle-status para mantener (como tienes ya)
        $('.toggle-status').click(function() {
            var boton = $(this);
            var id = boton.data('id');
            var status = boton.data('status');

            $.ajax({
                url: '', // mismo archivo
                type: 'POST',
                data: {
                    id: id,
                    status: status,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var fila = boton.closest('tr');
                        table.row(fila).remove().draw(false);
                    } else {
                        alert('Error: ' + (response.msg || 'No se pudo actualizar'));
                    }
                },
                error: function() {
                    alert('Error en la petición Ajax');
                }
            });
        });
    });
</script>

<!-- Importante para íconos -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />