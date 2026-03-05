<?php
/**
 * User Dashboard
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Meu Dashboard';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get user's subscriptions
$subStmt = $pdo->prepare("
    SELECT als.*, s.name as station_name, s.address, s.gasoline_available, s.diesel_available,
           s.gasoline_price, s.diesel_price, s.last_updated
    FROM alert_subscriptions als
    JOIN stations s ON als.station_id = s.id
    WHERE als.user_id = ?
    ORDER BY s.name ASC
");
$subStmt->execute([$user['id']]);
$subscriptions = $subStmt->fetchAll();

// Get recent notifications
$notifStmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$notifStmt->execute([$user['id']]);
$notifications = $notifStmt->fetchAll();

// Get available stations for subscription
$stationsStmt = $pdo->query("SELECT id, name, address FROM stations WHERE status = 'approved' ORDER BY name ASC");
$allStations = $stationsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2><i class="bi bi-speedometer2"></i> Olá, <?= sanitize($user['name']) ?>!</h2>
            <p class="mb-0 opacity-75">Bem-vindo ao seu painel de monitoramento de combustíveis</p>
        </div>

        <div class="row g-4">
            <!-- Subscribed Stations -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Meus Alertas (<?= count($subscriptions) ?>)</h5>
                        <a href="<?= BASE_URL ?>user/alerts.php" class="btn btn-sm btn-outline-primary">
                            Gerenciar Alertas
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subscriptions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-bell-slash fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Você não está inscrito em nenhum posto.</p>
                            <a href="<?= BASE_URL ?>stations.php" class="btn btn-primary">
                                <i class="bi bi-geo-alt"></i> Explorar Postos
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($subscriptions as $sub): ?>
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= sanitize($sub['station_name']) ?></h6>
                                        <p class="card-text small text-muted">
                                            <i class="bi bi-geo-alt"></i> <?= sanitize($sub['address']) ?>
                                        </p>
                                        <div class="d-flex gap-2 mb-2">
                                            <div>
                                                <small class="text-muted">Gasolina:</small>
                                                <?= availabilityBadge((bool)$sub['gasoline_available']) ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mb-2">
                                            <div>
                                                <small class="text-muted">Gasóleo:</small>
                                                <?= availabilityBadge((bool)$sub['diesel_available']) ?>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">
                                                <?= $sub['last_updated'] ? timeAgo($sub['last_updated']) : 'N/A' ?>
                                            </small>
                                            <a href="<?= BASE_URL ?>stations.php?id=<?= $sub['station_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Ver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Stations Quick View -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-fuel-pump"></i> Disponibilidade Geral</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="userMap" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Notifications -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Notificações</h5>
                        <a href="<?= BASE_URL ?>notifications.php" class="btn btn-sm btn-link">Ver Todas</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center small">Nenhuma notificação.</p>
                        <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                        <div class="d-flex align-items-start mb-3 pb-2 border-bottom">
                            <?php
                            $iconMap = ['info' => 'info-circle text-primary', 'success' => 'check-circle text-success',
                                       'warning' => 'exclamation-triangle text-warning', 'danger' => 'x-circle text-danger'];
                            $icon = $iconMap[$notif['type']] ?? 'info-circle text-primary';
                            ?>
                            <i class="bi bi-<?= $icon ?> me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong class="small"><?= sanitize($notif['title']) ?></strong>
                                <p class="mb-0 small text-muted"><?= truncate(sanitize($notif['message']), 80) ?></p>
                                <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>stations.php" class="btn btn-outline-primary">
                                <i class="bi bi-geo-alt"></i> Explorar Postos
                            </a>
                            <a href="<?= BASE_URL ?>user/alerts.php" class="btn btn-outline-success">
                                <i class="bi bi-bell"></i> Gerenciar Alertas
                            </a>
                            <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline-secondary">
                                <i class="bi bi-person"></i> Meu Perfil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load map with all stations
    fetch(FuelMonitor.baseUrl + 'api/stations.php?action=map')
        .then(r => r.json())
        .then(data => {
            if (data.success && document.getElementById('userMap')) {
                FuelMonitor.initMap('userMap', data.stations);
            }
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
