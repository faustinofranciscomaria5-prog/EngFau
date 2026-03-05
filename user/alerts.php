<?php
/**
 * User - Manage Alert Subscriptions
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Meus Alertas';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Handle subscription management via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $action = $_POST['action'] ?? '';
        $stationId = (int)($_POST['station_id'] ?? 0);

        if ($stationId) {
            switch ($action) {
                case 'subscribe':
                    $check = $pdo->prepare("SELECT id FROM alert_subscriptions WHERE user_id = ? AND station_id = ?");
                    $check->execute([$user['id'], $stationId]);
                    if (!$check->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO alert_subscriptions (user_id, station_id) VALUES (?, ?)");
                        $stmt->execute([$user['id'], $stationId]);
                        setFlash('success', 'Inscrito para alertas deste posto!');
                    }
                    break;

                case 'unsubscribe':
                    $stmt = $pdo->prepare("DELETE FROM alert_subscriptions WHERE user_id = ? AND station_id = ?");
                    $stmt->execute([$user['id'], $stationId]);
                    setFlash('success', 'Inscrição de alertas cancelada.');
                    break;
            }
        }

        header('Location: ' . BASE_URL . 'user/alerts.php');
        exit;
    }
}

// Get user's subscriptions
$subStmt = $pdo->prepare("
    SELECT als.*, s.name as station_name, s.address, s.gasoline_available, s.diesel_available
    FROM alert_subscriptions als
    JOIN stations s ON als.station_id = s.id
    WHERE als.user_id = ?
    ORDER BY s.name ASC
");
$subStmt->execute([$user['id']]);
$subscriptions = $subStmt->fetchAll();
$subscribedIds = array_column($subscriptions, 'station_id');

// Get all approved stations
$stationsStmt = $pdo->query("SELECT * FROM stations WHERE status = 'approved' ORDER BY name ASC");
$allStations = $stationsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bell"></i> Gerenciar Alertas</h2>
            <a href="<?= BASE_URL ?>user/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Current Subscriptions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-bell-fill"></i> Postos com Alertas Ativos (<?= count($subscriptions) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($subscriptions)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-bell-slash fs-2 text-muted"></i>
                    <p class="text-muted mt-2">Nenhuma inscrição ativa. Adicione postos abaixo.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Posto</th>
                                <th>Endereço</th>
                                <th class="text-center">Gasolina</th>
                                <th class="text-center">Gasóleo</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td><strong><?= sanitize($sub['station_name']) ?></strong></td>
                                <td><small><?= sanitize($sub['address']) ?></small></td>
                                <td class="text-center"><?= availabilityBadge((bool)$sub['gasoline_available']) ?></td>
                                <td class="text-center"><?= availabilityBadge((bool)$sub['diesel_available']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="unsubscribe">
                                        <input type="hidden" name="station_id" value="<?= $sub['station_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-bell-slash"></i> Cancelar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Stations -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-building"></i> Postos Disponíveis para Inscrição</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Posto</th>
                                <th>Endereço</th>
                                <th class="text-center">Gasolina</th>
                                <th class="text-center">Gasóleo</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allStations as $station): ?>
                            <tr>
                                <td><strong><?= sanitize($station['name']) ?></strong></td>
                                <td><small><?= sanitize($station['address']) ?></small></td>
                                <td class="text-center"><?= availabilityBadge((bool)$station['gasoline_available']) ?></td>
                                <td class="text-center"><?= availabilityBadge((bool)$station['diesel_available']) ?></td>
                                <td>
                                    <?php if (in_array($station['id'], $subscribedIds)): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Inscrito</span>
                                    <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="subscribe">
                                        <input type="hidden" name="station_id" value="<?= $station['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-bell"></i> Inscrever
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
