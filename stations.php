<?php
/**
 * Stations List Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Postos de Combustível';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Get specific station if ID provided
$stationId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($stationId) {
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND status = 'approved'");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();

    if (!$station) {
        setFlash('danger', 'Posto não encontrado.');
        header('Location: ' . BASE_URL . 'stations.php');
        exit;
    }

    // Get fuel history
    $histStmt = $pdo->prepare("
        SELECT fh.*, u.name as updated_by_name
        FROM fuel_history fh
        LEFT JOIN users u ON fh.updated_by = u.id
        WHERE fh.station_id = ?
        ORDER BY fh.created_at DESC
        LIMIT 20
    ");
    $histStmt->execute([$stationId]);
    $history = $histStmt->fetchAll();

    // Check if user is subscribed
    $isSubscribed = false;
    if ($currentUser) {
        $subStmt = $pdo->prepare("SELECT id FROM alert_subscriptions WHERE user_id = ? AND station_id = ?");
        $subStmt->execute([$currentUser['id'], $stationId]);
        $isSubscribed = (bool)$subStmt->fetch();
    }
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Início</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>stations.php">Postos</a></li>
                <li class="breadcrumb-item active"><?= sanitize($station['name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <?php if ($station['photo']): ?>
                    <img src="<?= BASE_URL ?>uploads/stations/<?= sanitize($station['photo']) ?>"
                         class="card-img-top" style="height:300px; object-fit:cover;" alt="<?= sanitize($station['name']) ?>">
                    <?php else: ?>
                    <div class="bg-primary bg-opacity-10 text-center py-5">
                        <i class="bi bi-fuel-pump-fill text-primary" style="font-size: 5rem;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2><?= sanitize($station['name']) ?></h2>
                        <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= sanitize($station['address']) ?></p>

                        <?php if ($station['phone']): ?>
                        <p><i class="bi bi-phone"></i> <?= sanitize($station['phone']) ?></p>
                        <?php endif; ?>

                        <p>
                            <i class="bi bi-clock"></i>
                            Horário: <?= date('H:i', strtotime($station['opening_time'])) ?> - <?= date('H:i', strtotime($station['closing_time'])) ?>
                        </p>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <div class="card <?= $station['gasoline_available'] ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body text-center">
                                        <h5>Gasolina</h5>
                                        <?= availabilityBadge((bool)$station['gasoline_available']) ?>
                                        <p class="mt-2 mb-0 fw-bold">
                                            <?= formatPrice($station['gasoline_price'] !== null ? (float)$station['gasoline_price'] : null) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card <?= $station['diesel_available'] ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body text-center">
                                        <h5>Gasóleo</h5>
                                        <?= availabilityBadge((bool)$station['diesel_available']) ?>
                                        <p class="mt-2 mb-0 fw-bold">
                                            <?= formatPrice($station['diesel_price'] !== null ? (float)$station['diesel_price'] : null) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($currentUser && $currentUser['role'] === 'user'): ?>
                        <div class="mt-4">
                            <button class="btn <?= $isSubscribed ? 'btn-outline-danger' : 'btn-outline-primary' ?>"
                                    id="alertBtn" onclick="toggleAlert(<?= $station['id'] ?>)">
                                <i class="bi bi-bell<?= $isSubscribed ? '-slash' : '' ?>"></i>
                                <?= $isSubscribed ? 'Cancelar Alertas' : 'Receber Alertas' ?>
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <small class="text-muted">
                                Última atualização: <?= $station['last_updated'] ? formatDate($station['last_updated']) : 'N/A' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Fuel History -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico de Disponibilidade</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($history)): ?>
                        <p class="text-muted text-center">Nenhum histórico disponível.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Combustível</th>
                                        <th>Status</th>
                                        <th>Preço</th>
                                        <th>Atualizado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?= formatDate($h['created_at']) ?></td>
                                        <td><?= $h['fuel_type'] === 'gasoline' ? 'Gasolina' : 'Gasóleo' ?></td>
                                        <td><?= availabilityBadge((bool)$h['available']) ?></td>
                                        <td><?= formatPrice($h['price'] !== null ? (float)$h['price'] : null) ?></td>
                                        <td><?= sanitize($h['updated_by_name'] ?? 'Sistema') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Map -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Localização</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="stationMap" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const station = {
        name: <?= json_encode($station['name']) ?>,
        address: <?= json_encode($station['address']) ?>,
        latitude: <?= (float)$station['latitude'] ?>,
        longitude: <?= (float)$station['longitude'] ?>,
        phone: <?= json_encode($station['phone'] ?? '') ?>,
        gasoline_available: <?= $station['gasoline_available'] ? 'true' : 'false' ?>,
        diesel_available: <?= $station['diesel_available'] ? 'true' : 'false' ?>
    };
    FuelMonitor.initMap('stationMap', [station], [station.latitude, station.longitude], 15);
});

function toggleAlert(stationId) {
    const btn = document.getElementById('alertBtn');
    const isSubscribed = btn.classList.contains('btn-outline-danger');
    const csrfToken = '<?= generateCSRFToken() ?>';

    const action = isSubscribed
        ? FuelMonitor.unsubscribeAlerts(stationId, csrfToken)
        : FuelMonitor.subscribeAlerts(stationId, csrfToken);

    action.then(data => {
        if (data.success) {
            if (isSubscribed) {
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-outline-primary');
                btn.innerHTML = '<i class="bi bi-bell"></i> Receber Alertas';
            } else {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-outline-danger');
                btn.innerHTML = '<i class="bi bi-bell-slash"></i> Cancelar Alertas';
            }
            FuelMonitor.showToast(data.message, 'success');
        } else {
            FuelMonitor.showToast(data.message || 'Erro ao processar solicitação.', 'danger');
        }
    });
}
</script>

<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// List all stations
$stmt = $pdo->query("SELECT * FROM stations WHERE status = 'approved' ORDER BY name ASC");
$stations = $stmt->fetchAll();
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-geo-alt"></i> Postos de Combustível</h2>
            <a href="<?= BASE_URL ?>request-station.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Solicitar Cadastro de Posto
            </a>
        </div>

        <!-- Map -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div id="stationsMap"></div>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-md-4">
                <select class="form-select" id="filterFuel" onchange="filterStations()">
                    <option value="">Todos os combustíveis</option>
                    <option value="gasoline">Com Gasolina</option>
                    <option value="diesel">Com Gasóleo</option>
                    <option value="both">Com Ambos</option>
                </select>
            </div>
        </div>

        <!-- Station Cards -->
        <div class="row g-4" id="stationsList">
            <?php if (empty($stations)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">Nenhum posto cadastrado ainda.</p>
            </div>
            <?php else: ?>
            <?php foreach ($stations as $station): ?>
            <div class="col-md-6 col-lg-4 station-item"
                 data-gasoline="<?= $station['gasoline_available'] ?>"
                 data-diesel="<?= $station['diesel_available'] ?>">
                <div class="card station-card shadow-sm h-100">
                    <?php if ($station['photo']): ?>
                    <img src="<?= BASE_URL ?>uploads/stations/<?= sanitize($station['photo']) ?>"
                         class="card-img-top" alt="<?= sanitize($station['name']) ?>">
                    <?php else: ?>
                    <div class="bg-primary bg-opacity-10 text-center py-4 card-img-top" style="height:200px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-fuel-pump-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= sanitize($station['name']) ?></h5>
                        <p class="card-text text-muted small">
                            <i class="bi bi-geo-alt"></i> <?= sanitize($station['address']) ?>
                        </p>
                        <div class="d-flex gap-2 mb-3">
                            <?= availabilityBadge((bool)$station['gasoline_available']) ?>
                            <small class="text-muted">Gasolina</small>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <?= availabilityBadge((bool)$station['diesel_available']) ?>
                            <small class="text-muted">Gasóleo</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?= BASE_URL ?>stations.php?id=<?= $station['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-eye"></i> Ver Detalhes
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stations = <?= json_encode(array_map(function($s) {
        return [
            'name' => $s['name'],
            'address' => $s['address'],
            'latitude' => (float)$s['latitude'],
            'longitude' => (float)$s['longitude'],
            'phone' => $s['phone'],
            'gasoline_available' => (bool)$s['gasoline_available'],
            'diesel_available' => (bool)$s['diesel_available'],
        ];
    }, $stations)) ?>;

    if (document.getElementById('stationsMap')) {
        FuelMonitor.initMap('stationsMap', stations);
    }
});

function filterStations() {
    const filter = document.getElementById('filterFuel').value;
    const items = document.querySelectorAll('.station-item');

    items.forEach(item => {
        const gas = item.dataset.gasoline === '1';
        const diesel = item.dataset.diesel === '1';

        let show = true;
        if (filter === 'gasoline') show = gas;
        else if (filter === 'diesel') show = diesel;
        else if (filter === 'both') show = gas && diesel;

        item.style.display = show ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
