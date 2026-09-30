<?php
require_once 'database.php';
// sidebar.php - Menú lateral con control de acceso por rol
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener rol del usuario (asumiendo que se guarda en $_SESSION['user_role'])
$userRole = $_SESSION['role_name'] ?? '';
$userName = $_SESSION['username'] ?? 'Invitado';


// Función auxiliar para verificar si el usuario tiene un rol específico
function hasRole($role)
{
    return ($_SESSION['role_name'] ?? '') === $role;
}
?>

<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="index" class="logo">
                <img src="assets/img/hackathon_logo.png" alt="navbar brand" class="navbar-brand" height="60" />
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <?php
                // Obtener eventos activos
                $eventStmt = $pdo->prepare("SELECT id, name, start_date, end_date FROM events WHERE status = 1 ORDER BY start_date DESC");
                $eventStmt->execute();
                $activeEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

                // Evento seleccionado actualmente
                $currentEventId = $_SESSION['current_event_id'] ?? null;
                ?>

                <!-- Selector de evento global -->
                <li class="nav-item">
                    <div class="p-3">
                        <label class="text-light small fw-bold mb-2 d-block">
                            <i class="fas fa-calendar-check me-1"></i> Evento activo
                        </label>
                        <form method="post" action="set_current_event.php" id="eventSelectorForm">
                            <select name="event_id" class="form-select form-select-sm bg-dark text-white border-secondary"
                                onchange="document.getElementById('eventSelectorForm').submit();">
                                <option value="">-- Seleccionar evento --</option>
                                <?php foreach ($activeEvents as $event): ?>
                                    <option value="<?= $event['id'] ?>"
                                        <?= ($currentEventId == $event['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['name']) ?>
                                        (<?= date('d/m/Y', strtotime($event['start_date'])) ?> - <?= date('d/m/Y', strtotime($event['end_date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php if ($currentEventId): ?>
                            <div class="small text-success mt-2">
                                <i class="fas fa-check-circle"></i> Trabajando en:
                                <?php
                                // Mostrar nombre del evento seleccionado
                                $eventName = '';
                                foreach ($activeEvents as $ev) {
                                    if ($ev['id'] == $currentEventId) {
                                        $eventName = $ev['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($eventName);
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="small text-warning mt-2">
                                <i class="fas fa-exclamation-triangle"></i> No has seleccionado evento
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php if (hasRole('Admin')): ?>
                    <!-- Menú para Administradores -->
                    <li class="nav-item active">
                        <a data-bs-toggle="collapse" href="#dashboard" class="collapsed" aria-expanded="false">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse" id="dashboard">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="index">
                                        <span class="sub-item">Dashboard</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">Gestión</h4>
                    </li>

                    <li class="nav-item">
                        <a data-bs-toggle="collapse" href="#eventos">
                            <i class="fas fa-calendar-alt"></i>
                            <p>Eventos</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse" id="eventos">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="create_event">
                                        <span class="sub-item">Crear Evento</span>
                                    </a>
                                </li>
                                
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a data-bs-toggle="collapse" href="#inscripciones">
                            <i class="fas fa-users"></i>
                            <p>Participantes</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse" id="inscripciones">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="participants">
                                        <span class="sub-item">Inscribir Participantes</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="private_event_registration">
                                        <span class="sub-item">Inscripción Colegio Interno</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="registros">
                                        <span class="sub-item">Confirmar Pagos</span>
                                    </a>
                                </li>
                                <!-- <li>
                                    <a href="inactivos">
                                        <span class="sub-item">Participantes Inactivos</span>
                                    </a>
                                </li> -->
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a data-bs-toggle="collapse" href="#jueces">
                            <i class="fas fa-gavel"></i>
                            <p>Jueces</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse" id="jueces">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="judges">
                                        <span class="sub-item">Gestión de Jueces</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="assign_judges">
                                        <span class="sub-item">Asignar Jueces</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="control_retos">
                            <i class="fas fa-toggle-on"></i>
                            <p>Control de Retos</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="juez_evaluacion">
                            <i class="fas fa-clipboard-check"></i>
                            <p>Evaluar Participantes</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="schools">
                            <i class="fas fa-school"></i>
                            <p>Colegios</p>
                        </a>
                    </li>

                    

                    <li class="nav-item">
                        <a href="escanear_qr">
                            <i class="fas fa-qrcode"></i>
                            <p>Scanner QR</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="ranking">
                            <i class="fas fa-trophy"></i>
                            <p>Ranking</p>
                        </a>
                    </li>

                <?php elseif (hasRole('Juez')): ?>
                    <!-- Menú para Jueces -->
                    <li class="nav-item active">
                        <a href="judge_dashboard">
                            <i class="fas fa-home"></i>
                            <p>Mi Panel</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="juez_evaluacion">
                            <i class="fas fa-clipboard-check"></i>
                            <p>Evaluar Participantes</p>
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Si no hay rol reconocido, mostrar solo inicio -->
                    <li class="nav-item active">
                        <a href="index">
                            <i class="fas fa-home"></i>
                            <p>Inicio</p>
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->

<!-- Barra superior simplificada -->
<div class="main-panel">
    <div class="main-header">
        <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
                <a href="index" class="logo">
                    <img src="assets/img/hackathon_logo.png" alt="navbar brand" class="navbar-brand" height="20" />
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar">
                        <i class="gg-menu-right"></i>
                    </button>
                    <button class="btn btn-toggle sidenav-toggler">
                        <i class="gg-menu-left"></i>
                    </button>
                </div>
                <button class="topbar-toggler more">
                    <i class="gg-more-vertical-alt"></i>
                </button>
            </div>
            <!-- End Logo Header -->
        </div>
        <!-- Navbar Header -->
        <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
                <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                    <!-- Usuario -->
                    <li class="nav-item topbar-user dropdown hidden-caret">
                        <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                            <div class="avatar-sm">
                                <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                            </div>
                            <span class="profile-username">
                                <span class="op-7">Hola,</span>
                                <span class="fw-bold">
                                    <?= htmlspecialchars(ucfirst($userName)) ?>
                                </span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-user animated fadeIn">
                            <div class="dropdown-user-scroll scrollbar-outer">
                                <li>
                                    <div class="user-box">
                                        <div class="avatar-lg">
                                            <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                                        </div>
                                        <div class="u-text">
                                            <h4><?= htmlspecialchars(ucfirst($userName)) ?></h4>
                                            <p class="text-muted"><?= htmlspecialchars($userRole) ?></p>
                                            <a href="profile" class="btn btn-xs btn-secondary btn-sm">Ver Perfil</a>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="logout">Cerrar Sesión</a>
                                </li>
                            </div>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- End Navbar -->
    </div>

    <div class="container">
        <div class="page-inner">