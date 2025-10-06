
<?php
// admin_panel.php - Panel básico de administración
session_start();
require_once 'config.php';

// Verificar autenticación (implementar según necesidades)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_registrations,
    COUNT(CASE WHEN category = 'Junior' THEN 1 END) as junior_count,
    COUNT(CASE WHEN category = 'Senior' THEN 1 END) as senior_count,
    COUNT(CASE WHEN is_minor = 1 THEN 1 END) as minors_count
    FROM registrations");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener registros recientes
$stmt = $pdo->query("SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10");
$recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Hackathon</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #4f46e5; }
        .table-container { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #4f46e5; color: white; }
        .btn-success { background: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Panel de Administración - Hackathon 2024</h1>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div>Total Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['junior_count']; ?></div>
                <div>Categoría Junior</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['senior_count']; ?></div>
                <div>Categoría Senior</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['minors_count']; ?></div>
                <div>Menores de Edad</div>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Registro</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Edad</th>
                        <th>Categoría</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_registrations as $reg): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reg['registration_number']); ?></td>
                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo $reg['age']; ?></td>
                        <td><?php echo $reg['category']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($reg['created_at'])); ?></td>
                        <td>
                            <a href="view_registration.php?id=<?php echo $reg['id']; ?>" class="btn btn-primary">Ver</a>
                            <a href="export_qr.php?id=<?php echo $reg['id']; ?>" class="btn btn-success">QR</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <br>
        <a href="export_excel.php" class="btn btn-success">Exportar a Excel</a>
        <a href="bulk_email.php" class="btn btn-primary">Enviar Email Masivo</a>
    </div>
</body>
</html>