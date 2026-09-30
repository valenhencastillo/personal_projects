<?php
require_once 'database.php';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener evento seleccionado globalmente
$eventId = $_SESSION['current_event_id'] ?? null;

// Si no hay evento seleccionado, mostrar mensaje y detener
if (!$eventId) {
    require_once 'header.php';
    require_once 'sidebar.php';
    echo '<div class="container py-4"><div class="alert alert-warning">Debe seleccionar un evento en el menú lateral para ver el dashboard.</div></div>';
    require_once 'footer.php';
    exit;
}

// ==========================================
// 1. OBTENER ESTADÍSTICAS DEL HACKATHON (FILTRADAS POR EVENTO)
// ==========================================

// Total de participantes registrados en el evento
$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
$stmt->execute([$eventId]);
$total = $stmt->fetchColumn();

// Total de equipos únicos (agrupando por registration_number) en el evento
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT registration_number) FROM registrations WHERE event_id = ?");
$stmt->execute([$eventId]);
$totalEquipos = $stmt->fetchColumn();

// Participantes por Categoría en el evento
$stmt = $pdo->prepare("
    SELECT COUNT(r.id) 
    FROM registrations r 
    LEFT JOIN event_categories c ON r.category_id = c.id 
    WHERE r.event_id = ? AND c.name LIKE '%Junior%'
");
$stmt->execute([$eventId]);
$totalJunior = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(r.id) 
    FROM registrations r 
    LEFT JOIN event_categories c ON r.category_id = c.id 
    WHERE r.event_id = ? AND c.name LIKE '%Senior%'
");
$stmt->execute([$eventId]);
$totalSenior = $stmt->fetchColumn();

// Total de asistencia registrada (attendance_status = 1) en el evento
$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND attendance_status = 1");
$stmt->execute([$eventId]);
$totalAsistencia = $stmt->fetchColumn();

// Datos para el gráfico: registros en los últimos 7 días del evento
$stmtChart = $pdo->prepare("
    SELECT DATE(created_at) as fecha, COUNT(*) as cantidad 
    FROM registrations 
    WHERE event_id = ?
    GROUP BY DATE(created_at) 
    ORDER BY fecha ASC 
    LIMIT 7
");
$stmtChart->execute([$eventId]);

$fechas = [];
$cantidades = [];
while ($row = $stmtChart->fetch(PDO::FETCH_ASSOC)) {
    $fechas[] = date('d/m', strtotime($row['fecha']));
    $cantidades[] = $row['cantidad'];
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<style>
    :root {
        --primary: #6C63FF;
        --secondary: #17a2b8;
        --success: #2ECC71;
        --info: #4A90E2;
        --warning: #F39C12;
        --dark: #2C3E50;
        --light-bg: #F5F7FA;
        --card-radius: 20px;
        --shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .dashboard-wrapper {
        padding: 1rem;
    }

    .dashboard-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .dashboard-header h3 {
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .dashboard-header h6 {
        color: #7f8c8d;
        margin: 0.25rem 0 0 0;
    }

    .stat-card {
        background: white;
        border-radius: var(--card-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.12);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-icon.primary { background: linear-gradient(135deg, #6C63FF, #8B82FF); }
    .stat-icon.info { background: linear-gradient(135deg, #4A90E2, #6BB5FF); }
    .stat-icon.success { background: linear-gradient(135deg, #2ECC71, #58D68D); }
    .stat-icon.warning { background: linear-gradient(135deg, #F39C12, #F8C471); }

    .stat-content {
        flex: 1;
        min-width: 0;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0.25rem 0 0 0;
    }

    .stat-sub {
        color: #95a5a6;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    .chart-card {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .chart-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .chart-body {
        padding: 1.5rem;
    }

    @media (max-width: 768px) {
        .stat-value { font-size: 1.5rem; }
        .stat-icon { width: 50px; height: 50px; font-size: 1.5rem; }
    }
</style>

<div class="dashboard-wrapper">
    <!-- Encabezado -->
    <div class="dashboard-header">
        <div>
            <h3>Dashboard del Hackathon</h3>
            <h6>Resumen y estado general del evento seleccionado</h6>
        </div>
        <div class="d-flex gap-2">
            <a href="eventos.php" class="btn btn-label-info btn-round">
                <i class="fas fa-list"></i> Ver Eventos
            </a>
            <a href="escanear_qr.php" class="btn btn-primary btn-round">
                <i class="fas fa-qrcode"></i> Escanear Acceso
            </a>
        </div>
    </div>

    <!-- Tarjetas de indicadores -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Participantes</p>
                    <p class="stat-value"><?php echo $total; ?></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Equipos Activos</p>
                    <p class="stat-value"><?php echo $totalEquipos; ?></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Desglose Categorías</p>
                    <p class="stat-value" style="font-size: 1.4rem;">
                        <span class="text-primary">Jr: <?php echo $totalJunior; ?></span> |
                        <span class="text-success">Sr: <?php echo $totalSenior; ?></span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Asistencia (Check-in)</p>
                    <p class="stat-value"><?php echo $totalAsistencia; ?> <small class="text-muted">/ <?php echo $total; ?></small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-line text-primary"></i> Evolución de Inscripciones
                </div>
                <div class="chart-body">
                    <?php if (empty($fechas)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Aún no hay registros en el sistema para generar gráficas.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-container" style="min-height: 375px; position: relative;">
                            <canvas id="statisticsChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<!-- Archivos de la plantilla base (mantén los que tu sistema necesite) -->
<script src="assets/js/setting-demo.js"></script>

<!-- Gráfico Dinámico -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartLabels = <?php echo json_encode($fechas); ?>;
    const chartData = <?php echo json_encode($cantidades); ?>;

    if (chartLabels.length > 0) {
        const ctx = document.getElementById('statisticsChart');
        if(ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: "Participantes Inscritos",
                        borderColor: '#6C63FF',
                        pointBackgroundColor: '#6C63FF',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        backgroundColor: 'rgba(108, 99, 255, 0.15)',
                        fill: true,
                        borderWidth: 3,
                        data: chartData,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.parsed.y + ' Inscripciones';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                padding: 10
                            },
                            grid: {
                                borderDash: [2, 2]
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>