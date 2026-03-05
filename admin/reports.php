<?php
/**
 * Admin - Reports & Statistics
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Relatórios e Estatísticas';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();

// Fuel availability over time (last 30 days)
$fuelHistory = $pdo->query("
    SELECT DATE(created_at) as date,
           fuel_type,
           SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as available_count,
           SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as unavailable_count
    FROM fuel_history
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at), fuel_type
    ORDER BY date ASC
")->fetchAll();

// Operator activity
$operatorActivity = $pdo->query("
    SELECT u.name, u.email, COUNT(fh.id) as updates_count,
           MAX(fh.created_at) as last_update
    FROM users u
    LEFT JOIN fuel_history fh ON u.id = fh.updated_by
    WHERE u.role = 'operator' AND u.is_active = 1
    GROUP BY u.id, u.name, u.email
    ORDER BY updates_count DESC
")->fetchAll();

// Station popularity
$stationPopularity = $pdo->query("
    SELECT s.name, s.address,
           COUNT(DISTINCT als.user_id) as subscribers,
           COUNT(DISTINCT fh.id) as updates,
           s.gasoline_available, s.diesel_available
    FROM stations s
    LEFT JOIN alert_subscriptions als ON s.id = als.station_id
    LEFT JOIN fuel_history fh ON s.id = fh.station_id
    WHERE s.status = 'approved'
    GROUP BY s.id, s.name, s.address, s.gasoline_available, s.diesel_available
    ORDER BY subscribers DESC
")->fetchAll();

// Overall stats
$totalUpdates = $pdo->query("SELECT COUNT(*) FROM fuel_history")->fetchColumn();
$totalSubscriptions = $pdo->query("SELECT COUNT(*) FROM alert_subscriptions")->fetchColumn();
$totalNotifications = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-graph-up"></i> Relatórios e Estatísticas</h2>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-arrow-repeat text-primary fs-1"></i>
                        <div class="stat-number text-primary"><?= $totalUpdates ?></div>
                        <div class="text-muted">Total de Atualizações</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-bell text-success fs-1"></i>
                        <div class="stat-number text-success"><?= $totalSubscriptions ?></div>
                        <div class="text-muted">Inscrições de Alertas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope text-warning fs-1"></i>
                        <div class="stat-number text-warning"><?= $totalNotifications ?></div>
                        <div class="text-muted">Notificações Enviadas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Fuel Availability Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Disponibilidade de Combustível (30 dias)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="fuelChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Operator Activity -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Atividade dos Operadores</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Operador</th>
                                        <th>E-mail</th>
                                        <th>Atualizações</th>
                                        <th>Última Atividade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($operatorActivity as $op): ?>
                                    <tr>
                                        <td><strong><?= sanitize($op['name']) ?></strong></td>
                                        <td><?= sanitize($op['email']) ?></td>
                                        <td><span class="badge bg-primary"><?= $op['updates_count'] ?></span></td>
                                        <td>
                                            <?= $op['last_update'] ? timeAgo($op['last_update']) : 'Nunca' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Station Popularity -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Popularidade dos Postos</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:250px;">
                            <canvas id="popularityChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-list-ol"></i> Ranking dos Postos</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($stationPopularity as $idx => $sp): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 <?= $idx === 0 ? 'bg-light rounded' : '' ?>">
                            <div>
                                <strong>#<?= $idx + 1 ?></strong>
                                <?= sanitize($sp['name']) ?>
                                <br><small class="text-muted"><?= $sp['subscribers'] ?> inscritos | <?= $sp['updates'] ?> atualizações</small>
                            </div>
                            <div>
                                <?= availabilityBadge((bool)$sp['gasoline_available']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fuel History Chart
    const fuelData = <?= json_encode($fuelHistory) ?>;

    // Process data for chart
    const dates = [...new Set(fuelData.map(d => d.date))];
    const gasolineAvail = dates.map(date => {
        const entry = fuelData.find(d => d.date === date && d.fuel_type === 'gasoline');
        return entry ? parseInt(entry.available_count) : 0;
    });
    const dieselAvail = dates.map(date => {
        const entry = fuelData.find(d => d.date === date && d.fuel_type === 'diesel');
        return entry ? parseInt(entry.available_count) : 0;
    });

    new Chart(document.getElementById('fuelChart'), {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Gasolina Disponível',
                    data: gasolineAvail,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderRadius: 5
                },
                {
                    label: 'Gasóleo Disponível',
                    data: dieselAvail,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Popularity Chart
    const popData = <?= json_encode($stationPopularity) ?>;
    new Chart(document.getElementById('popularityChart'), {
        type: 'pie',
        data: {
            labels: popData.map(d => d.name),
            datasets: [{
                data: popData.map(d => parseInt(d.subscribers)),
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
