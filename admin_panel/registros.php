<?php
require_once 'database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener registros
$stmt = $pdo->query("SELECT * FROM registrations");
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
    $(document).ready(function() {
        $('#registrationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'excel',
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/es-ES.json'
            }
        });
    });
</script>