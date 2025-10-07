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

// Obtener registros
$stmt = $pdo->query("SELECT * FROM registrations WHERE status = 0");
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
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
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




<?php require_once 'footer.php'; ?>
<script>
    var csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
    $(document).ready(function() {
        var table = $('#registrationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'excel',
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/es-ES.json'
            }
        });

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
                        // Remover fila de la tabla DataTables usando API
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