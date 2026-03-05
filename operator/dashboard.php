<?php
/**
 * Operator Dashboard
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Dashboard Operador';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('operator');

$user = getCurrentUser();
$pdo = getDBConnection();

// Get operator's station
$stmt = $pdo->prepare("SELECT station_id FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();
$stationId = $userData['station_id'] ?? null;

$station = null;
$recentHistory = [];

if ($stationId) {
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();

    // Recent history
    $histStmt = $pdo->prepare("
        SELECT fh.*, u.name as updated_by_name
        FROM fuel_history fh
        LEFT JOIN users u ON fh.updated_by = u.id
        WHERE fh.station_id = ?
        ORDER BY fh.created_at DESC
        LIMIT 15
    ");
    $histStmt->execute([$stationId]);
    $recentHistory = $histStmt->fetchAll();
}

// Get subscriber count
$subCount = 0;
if ($stationId) {
    $subStmt = $pdo->prepare("SELECT COUNT(*) FROM alert_subscriptions WHERE station_id = ?");
    $subStmt->execute([$stationId]);
    $subCount = (int)$subStmt->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="bi bi-speedometer2"></i> Dashboard do Operador</h2>
                    <p class="mb-0 opacity-75">
                        <?= $station ? 'Gerenciando: ' . sanitize($station['name']) : 'Nenhum posto vinculado' ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="bi bi-person"></i> <?= sanitize($user['name']) ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!$station): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Nenhum Posto Vinculado</h5>
            <p>Sua conta não está vinculada a nenhum posto. Contacte o administrador para obter o código do posto.</p>
        </div>
        <?php else: ?>

        <!-- Station Status -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-droplet-fill text-success fs-2"></i>
                        <h6 class="mt-2">Gasolina</h6>
                        <?= availabilityBadge((bool)$station['gasoline_available']) ?>
                        <p class="mt-1 mb-0 small"><?= formatPrice($station['gasoline_price'] !== null ? (float)$station['gasoline_price'] : null) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-droplet-half text-warning fs-2"></i>
                        <h6 class="mt-2">Gasóleo</h6>
                        <?= availabilityBadge((bool)$station['diesel_available']) ?>
                        <p class="mt-1 mb-0 small"><?= formatPrice($station['diesel_price'] !== null ? (float)$station['diesel_price'] : null) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-people text-info fs-2"></i>
                        <h6 class="mt-2">Inscritos</h6>
                        <div class="stat-number text-info" style="font-size:1.5rem;"><?= $subCount ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-clock text-primary fs-2"></i>
                        <h6 class="mt-2">Última Atualização</h6>
                        <small class="text-muted"><?= $station['last_updated'] ? timeAgo($station['last_updated']) : 'Nunca' ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Update -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Atualização Rápida</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Gasoline -->
                            <div class="col-12">
                                <div class="card <?= $station['gasoline_available'] ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body">
                                        <h6>Gasolina</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="gasolineToggle"
                                                           <?= $station['gasoline_available'] ? 'checked' : '' ?>
                                                           onchange="quickUpdateFuel('gasoline', this.checked)">
                                                    <label class="form-check-label" for="gasolineToggle">
                                                        <?= $station['gasoline_available'] ? 'Disponível' : 'Indisponível' ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" class="form-control form-control-sm" id="gasolinePrice"
                                                       value="<?= $station['gasoline_price'] ?>" placeholder="Preço" step="0.01">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-sm btn-outline-success w-100" onclick="quickUpdateFuel('gasoline')">
                                                    <i class="bi bi-check-lg"></i> Atualizar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Diesel -->
                            <div class="col-12">
                                <div class="card <?= $station['diesel_available'] ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body">
                                        <h6>Gasóleo</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="dieselToggle"
                                                           <?= $station['diesel_available'] ? 'checked' : '' ?>
                                                           onchange="quickUpdateFuel('diesel', this.checked)">
                                                    <label class="form-check-label" for="dieselToggle">
                                                        <?= $station['diesel_available'] ? 'Disponível' : 'Indisponível' ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" class="form-control form-control-sm" id="dieselPrice"
                                                       value="<?= $station['diesel_price'] ?>" placeholder="Preço" step="0.01">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-sm btn-outline-warning w-100" onclick="quickUpdateFuel('diesel')">
                                                    <i class="bi bi-check-lg"></i> Atualizar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>operator/update-fuel.php" class="btn btn-outline-primary">
                                <i class="bi bi-droplet"></i> Formulário Completo de Atualização
                            </a>
                            <a href="<?= BASE_URL ?>operator/station-info.php" class="btn btn-outline-secondary">
                                <i class="bi bi-info-circle"></i> Informações do Posto
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico Recente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentHistory)): ?>
                        <p class="text-muted text-center">Nenhum histórico.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Preço</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentHistory as $h): ?>
                                    <tr>
                                        <td><small><?= formatDate($h['created_at']) ?></small></td>
                                        <td><?= $h['fuel_type'] === 'gasoline' ? 'Gasolina' : 'Gasóleo' ?></td>
                                        <td><?= availabilityBadge((bool)$h['available']) ?></td>
                                        <td><?= formatPrice($h['price'] !== null ? (float)$h['price'] : null) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
const stationId = <?= $stationId ?? 'null' ?>;
const csrfToken = '<?= generateCSRFToken() ?>';

async function quickUpdateFuel(fuelType, toggled) {
    const toggleId = fuelType === 'gasoline' ? 'gasolineToggle' : 'dieselToggle';
    const priceId = fuelType === 'gasoline' ? 'gasolinePrice' : 'dieselPrice';

    const available = toggled !== undefined ? toggled : document.getElementById(toggleId).checked;
    const price = document.getElementById(priceId).value;

    const result = await FuelMonitor.updateFuel(stationId, fuelType, available, price, csrfToken);

    if (result.success) {
        FuelMonitor.showToast(result.message, 'success');
        // Update label
        const label = document.querySelector(`label[for="${toggleId}"]`);
        if (label) label.textContent = available ? 'Disponível' : 'Indisponível';
    } else {
        FuelMonitor.showToast(result.message || 'Erro ao atualizar.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
