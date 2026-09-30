<?php
require_once 'database.php';
require_once 'auth.php';

// Solo administradores
checkAccess(['Admin']);

// Obtener evento global desde la sesión
$currentEventId = $_SESSION['current_event_id'] ?? null;
if (!$currentEventId) {
    // Mostrar mensaje simple y salir
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ranking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-dark text-white text-center"><div class="container py-5"><h2>⚠️ No hay evento seleccionado</h2>
    <p>Selecciona un evento en el menú lateral para ver el ranking.</p>
    <a href="dashboard.php" class="btn btn-warning mt-3">Volver al Panel</a></div></body></html>';
    exit;
}

// Obtener nombre del evento actual para mostrarlo
$stmtEvent = $pdo->prepare("SELECT name FROM events WHERE id = ?");
$stmtEvent->execute([$currentEventId]);
$eventName = $stmtEvent->fetchColumn();

// ==========================================
// CONTROLADOR AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        if ($action === 'get_ranking') {
            // Usar evento global desde sesión
            $eventId = $_SESSION['current_event_id'] ?? 0;
            if (!$eventId) {
                throw new Exception("Evento no especificado");
            }

            $query = "
                SELECT 
                    r.id AS registration_id,
                    p.full_name,
                    p.last_name,
                    p.institution,
                    c.name AS category_name,
                    COUNT(DISTINCT CASE WHEN jie.status = 'superado' THEN jie.id END) AS score,
                    COALESCE(SUM(DISTINCT CASE WHEN jie.status = 'superado' THEN je.total_time_seconds END), 0) AS total_time
                FROM registrations r
                INNER JOIN participants p ON r.participant_id = p.id
                INNER JOIN event_categories c ON r.category_id = c.id
                LEFT JOIN judge_item_evaluations jie ON jie.registration_id = r.id
                LEFT JOIN judge_evaluations je ON je.registration_id = r.id 
                    AND je.challenge_id = (
                        SELECT ci.challenge_id 
                        FROM challenge_items ci 
                        WHERE ci.id = jie.challenge_item_id
                    )
                WHERE r.event_id = ?
                GROUP BY r.id, p.full_name, p.last_name, p.institution, c.name
                HAVING score > 0
                ORDER BY score DESC, total_time ASC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$eventId]);
            $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $ranking]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking en Vivo - <?= htmlspecialchars($eventName) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: radial-gradient(circle at 50% 0%, #1a1f35 0%, #0a0e1a 100%);
            min-height: 100vh;
            padding: 2rem;
            color: #fff;
            overflow-x: hidden;
        }

        .ranking-container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header-title {
            font-size: 3.5rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffd700, #ffaa00, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.4);
        }

        .event-info {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1rem 1.8rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.4rem;
            font-weight: 600;
            color: #ffd700;
        }

        .event-info i {
            font-size: 1.8rem;
        }

        .podium-card {
            border-radius: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .podium-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .podium-card.gold {
            background: linear-gradient(145deg, #ffd700, #ffb300);
            color: #000;
            border: 2px solid #ffea00;
        }

        .podium-card.silver {
            background: linear-gradient(145deg, #e0e0e0, #b0b0b0);
            color: #000;
            border: 2px solid #f0f0f0;
        }

        .podium-card.bronze {
            background: linear-gradient(145deg, #cd7f32, #b8722d);
            color: #fff;
            border: 2px solid #e8a87c;
        }

        .podium-card .rank-number {
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .podium-card .participant-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .podium-card .score {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
        }

        .podium-card .time {
            font-size: 1.2rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .table-ranking {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            color: #fff;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .table-ranking thead th {
            font-size: 1.3rem;
            color: #ffd700;
            border-bottom: 3px solid #ffd700;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
        }

        .table-ranking tbody td {
            font-size: 1.3rem;
            padding: 0.8rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table-ranking tbody tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .medal {
            font-size: 2rem;
            margin-right: 8px;
        }

        .score-badge {
            background: #ffd700;
            color: #000;
            border-radius: 10px;
            padding: 0.3rem 1rem;
            font-weight: 800;
            font-size: 1.5rem;
            display: inline-block;
        }

        .time-badge {
            background: #17a2b8;
            color: #fff;
            border-radius: 10px;
            padding: 0.3rem 1rem;
            font-weight: 700;
            font-size: 1.2rem;
            display: inline-block;
        }

        #podiumContainer {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        #podiumContainer .col {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .back-link {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.4);
            color: #fff;
        }

        .last-update {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 1rem;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <a href="dashboard.php" class="back-link" title="Volver al panel">
        <i class="bi bi-arrow-left"></i>
    </a>

    <div class="ranking-container">
        <h1 class="header-title">🏆 Ranking en Vivo</h1>

        <!-- Información del evento actual -->
        <div class="event-info">
            <i class="bi bi-calendar-event"></i>
            <span>Evento actual: <?= htmlspecialchars($eventName) ?></span>
        </div>

        <!-- Podio -->
        <div class="row g-4 mb-5" id="podiumContainer">
            <div class="col-12 text-center text-muted py-5">Cargando podio...</div>
        </div>

        <!-- Tabla completa -->
        <div class="table-responsive">
            <table class="table table-ranking mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th>Participante</th>
                        <th>Categoría</th>
                        <th>Institución</th>
                        <th class="text-center">Puntaje</th>
                        <th class="text-center">Tiempo Total</th>
                    </tr>
                </thead>
                <tbody id="rankingBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">Cargando ranking...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="last-update" id="lastUpdate"></div>
    </div>

    <script>
        let autoRefreshInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadRanking();
            autoRefreshInterval = setInterval(loadRanking, 5000);
        });

        async function loadRanking() {
            const formData = new FormData();
            formData.append('action', 'get_ranking');
            // No enviamos event_id, el servidor lo obtiene de la sesión

            try {
                const res = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.status === 'success') {
                    renderRanking(json.data);
                    document.getElementById('lastUpdate').textContent = 'Última actualización: ' + new Date().toLocaleTimeString();
                } else {
                    console.error(json.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderRanking(data) {
            renderPodium(data.slice(0, 3));
            renderTable(data);
        }

        function renderPodium(top3) {
            const container = document.getElementById('podiumContainer');
            container.innerHTML = '';

            if (top3.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-muted py-5">Aún no hay evaluaciones registradas</div>';
                return;
            }

            const colors = ['gold', 'silver', 'bronze'];
            const medals = ['🥇', '🥈', '🥉'];

            top3.forEach((participant, index) => {
                const col = document.createElement('div');
                col.className = 'col-12 col-md-4';
                col.innerHTML = `
                    <div class="podium-card ${colors[index]} shadow-lg">
                        <div class="card-body text-center p-4">
                            <div class="medal">${medals[index]}</div>
                            <div class="rank-number">${index + 1}</div>
                            <div class="participant-name">${participant.full_name}</div>
                            <div class="score">${participant.score} pts</div>
                            <div class="time">${formatTime(participant.total_time)}</div>
                            <div class="mt-2">${participant.category_name}</div>
                        </div>
                    </div>
                `;
                container.appendChild(col);
            });
        }

        function renderTable(ranking) {
            const tbody = document.getElementById('rankingBody');
            tbody.innerHTML = '';

            if (ranking.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">No hay datos para mostrar</td></tr>';
                return;
            }

            ranking.forEach((p, index) => {
                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${medal ? `<span class="medal">${medal}</span>` : index + 1}</td>
                    <td>${p.full_name}</td>
                    <td>${p.category_name}</td>
                    <td>${p.institution || '—'}</td>
                    <td class="text-center"><span class="score-badge">${p.score}</span></td>
                    <td class="text-center"><span class="time-badge">${formatTime(p.total_time)}</span></td>
                `;
                if (index < 3) {
                    tr.style.backgroundColor = 'rgba(255,215,0,0.08)';
                }
                tbody.appendChild(tr);
            });
        }

        function formatTime(seconds) {
            if (!seconds || seconds <= 0) return '—';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
        }
    </script>
</body>

</html>