<?php
/**
 * Admin Dashboard
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totalOperators = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'operator' AND is_active = 1")->fetchColumn();
$totalStations = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved'")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM station_requests WHERE status = 'pending'")->fetchColumn();
$gasolineAvailable = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved' AND gasoline_available = 1")->fetchColumn();
$dieselAvailable = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved' AND diesel_available = 1")->fetchColumn();

// Recent activity
$recentActivity = $pdo->query("
    SELECT fh.*, s.name as station_name, u.name as user_name
    FROM fuel_history fh
    JOIN stations s ON fh.station_id = s.id
    JOIN users u ON fh.updated_by = u.id
    ORDER BY fh.created_at DESC
    LIMIT 10
")->fetchAll();

// Chart data - last 7 days
$chartData = $pdo->query("
    SELECT DATE(created_at) as date,
           SUM(CASE WHEN fuel_type = 'gasoline' AND available = 1 THEN 1 ELSE 0 END) as gasoline_restocks,
           SUM(CASE WHEN fuel_type = 'diesel' AND available = 1 THEN 1 ELSE 0 END) as diesel_restocks,
           SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as depletions
    FROM fuel_history
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

// Station popularity
$stationPopularity = $pdo->query("
    SELECT s.name, COUNT(als.id) as subscribers
    FROM stations s
    LEFT JOIN alert_subscriptions als ON s.id = als.station_id
    WHERE s.status = 'approved'
    GROUP BY s.id, s.name
    ORDER BY subscribers DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="bi bi-speedometer2"></i> Dashboard Administrativo</h2>
                    <p class="mb-0 opacity-75">Visão geral do sistema de monitoramento de combustíveis</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-2">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-number text-primary"><?= $totalUsers ?></div>
                        <div class="text-muted small">Utilizadores</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-2">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="stat-number text-info"><?= $totalOperators ?></div>
                        <div class="text-muted small">Operadores</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="stat-number text-success"><?= $totalStations ?></div>
                        <div class="text-muted small">Postos Ativos</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div class="stat-number text-warning"><?= $pendingRequests ?></div>
                        <div class="text-muted small">Pendentes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                            <i class="bi bi-droplet-fill"></i>
                        </div>
                        <div class="stat-number text-success"><?= $gasolineAvailable ?></div>
                        <div class="text-muted small">Com Gasolina</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger mx-auto mb-2">
                            <i class="bi bi-droplet-half"></i>
                        </div>
                        <div class="stat-number text-danger"><?= $dieselAvailable ?></div>
                        <div class="text-muted small">Com Gasóleo</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Charts -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Atividade dos Últimos 7 Dias</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Atividade Recente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivity)): ?>
                        <p class="text-muted text-center">Nenhuma atividade recente.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Posto</th>
                                        <th>Combustível</th>
                                        <th>Status</th>
                                        <th>Operador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td><small><?= formatDate($activity['created_at']) ?></small></td>
                                        <td><?= sanitize($activity['station_name']) ?></td>
                                        <td><?= $activity['fuel_type'] === 'gasoline' ? 'Gasolina' : 'Gasóleo' ?></td>
                                        <td><?= availabilityBadge((bool)$activity['available']) ?></td>
                                        <td><?= sanitize($activity['user_name']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-outline-warning">
                                <i class="bi bi-inbox"></i> Solicitações Pendentes
                                <?php if ($pendingRequests > 0): ?>
                                <span class="badge bg-warning text-dark"><?= $pendingRequests ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?= BASE_URL ?>admin/stations.php" class="btn btn-outline-primary">
                                <i class="bi bi-building"></i> Gerenciar Postos
                            </a>
                            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-info">
                                <i class="bi bi-people"></i> Gerenciar Utilizadores
                            </a>
                            <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-outline-success">
                                <i class="bi bi-graph-up"></i> Relatórios
                            </a>
                            <a href="<?= BASE_URL ?>admin/settings.php" class="btn btn-outline-secondary">
                                <i class="bi bi-gear"></i> Configurações
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Station Popularity -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Popularidade dos Postos</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:200px;">
                            <canvas id="popularityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Current Fuel Status -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-fuel-pump"></i> Status Geral</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Gasolina Disponível</span>
                                <span><?= $totalStations > 0 ? round(($gasolineAvailable / $totalStations) * 100) : 0 ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= $totalStations > 0 ? ($gasolineAvailable / $totalStations) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Gasóleo Disponível</span>
                                <span><?= $totalStations > 0 ? round(($dieselAvailable / $totalStations) * 100) : 0 ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: <?= $totalStations > 0 ? ($dieselAvailable / $totalStations) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity Chart
    const chartData = <?= json_encode($chartData) ?>;
    const labels = chartData.map(d => d.date);
    const gasolineData = chartData.map(d => parseInt(d.gasoline_restocks));
    const dieselData = chartData.map(d => parseInt(d.diesel_restocks));
    const depletionData = chartData.map(d => parseInt(d.depletions));

    new Chart(document.getElementById('activityChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Reabastecimento Gasolina',
                    data: gasolineData,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Reabastecimento Gasóleo',
                    data: dieselData,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Esgotamentos',
                    data: depletionData,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
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
        type: 'doughnut',
        data: {
            labels: popData.map(d => d.name),
            datasets: [{
                data: popData.map(d => parseInt(d.subscribers)),
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1']
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
