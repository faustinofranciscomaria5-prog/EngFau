<?php
/**
 * Homepage - Fuel Monitor Soyo
 */
$pageTitle = 'Início';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Get approved stations
$stmt = $pdo->query("SELECT * FROM stations WHERE status = 'approved' ORDER BY name ASC");
$stations = $stmt->fetchAll();
?>

<main>
    <!-- Hero Carousel -->
    <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1545262810-77515befe149?w=1200&h=450&fit=crop');">
                <div class="carousel-caption">
                    <h2>Fuel Monitor Soyo</h2>
                    <p>Monitoramento em tempo real de combustíveis na cidade do Soyo</p>
                    <a href="<?= BASE_URL ?>stations.php" class="btn btn-primary btn-lg mt-2">
                        <i class="bi bi-geo-alt"></i> Ver Postos
                    </a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1611117775350-ac3950990985?w=1200&h=450&fit=crop');">
                <div class="carousel-caption">
                    <h2>Encontre Combustível Disponível</h2>
                    <p>Saiba onde encontrar gasolina e gasóleo antes de se deslocar</p>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-success btn-lg mt-2">
                        <i class="bi bi-bell"></i> Receber Alertas
                    </a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=1200&h=450&fit=crop');">
                <div class="carousel-caption">
                    <h2>Para Operadores de Postos</h2>
                    <p>Atualize a disponibilidade de combustível do seu posto em tempo real</p>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-warning btn-lg mt-2">
                        <i class="bi bi-person-plus"></i> Cadastrar-se
                    </a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Search Bar -->
    <section class="search-section">
        <div class="container">
            <div class="card search-card">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" id="searchStations" class="form-control border-start-0"
                                       placeholder="Pesquisar postos por nome ou endereço..."
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3 mt-2 mt-md-0">
                            <button class="btn btn-primary w-100" onclick="performSearch()">
                                <i class="bi bi-search"></i> Pesquisar
                            </button>
                        </div>
                    </div>
                    <div id="searchResults" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Overview -->
    <section class="container mt-5">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <div class="stat-number text-primary"><?= count($stations) ?></div>
                            <div class="text-muted small">Postos Ativos</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <?php
                $gasolineCount = count(array_filter($stations, fn($s) => $s['gasoline_available']));
                ?>
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-droplet-fill"></i>
                        </div>
                        <div>
                            <div class="stat-number text-success"><?= $gasolineCount ?></div>
                            <div class="text-muted small">Com Gasolina</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <?php
                $dieselCount = count(array_filter($stations, fn($s) => $s['diesel_available']));
                ?>
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-droplet-half"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning"><?= $dieselCount ?></div>
                            <div class="text-muted small">Com Gasóleo</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <?php
                $bothCount = count(array_filter($stations, fn($s) => $s['gasoline_available'] && $s['diesel_available']));
                ?>
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="stat-number text-info"><?= $bothCount ?></div>
                            <div class="text-muted small">Ambos Disponíveis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Map -->
    <section class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="bi bi-map"></i> Mapa dos Postos</h3>
            <a href="<?= BASE_URL ?>stations.php" class="btn btn-outline-primary btn-sm">Ver Todos</a>
        </div>
        <div id="stationsMap"></div>
    </section>

    <!-- Fuel Availability Table -->
    <section class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="bi bi-table"></i> Disponibilidade de Combustíveis</h3>
            <span class="badge bg-primary" id="lastUpdate">
                <i class="bi bi-arrow-repeat"></i> Atualização automática a cada 30s
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover fuel-table" id="fuelAvailabilityTable">
                <thead>
                    <tr>
                        <th>Posto</th>
                        <th>Endereço</th>
                        <th class="text-center">Gasolina</th>
                        <th class="text-center">Gasóleo</th>
                        <th>Preço Gasolina</th>
                        <th>Preço Gasóleo</th>
                        <th>Última Atualização</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stations)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhum posto cadastrado ainda.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($stations as $station): ?>
                    <tr>
                        <td><strong><?= sanitize($station['name']) ?></strong></td>
                        <td><?= sanitize($station['address']) ?></td>
                        <td class="text-center">
                            <span class="fuel-status-indicator <?= $station['gasoline_available'] ? 'available' : 'unavailable' ?>"></span>
                            <?= availabilityBadge((bool)$station['gasoline_available']) ?>
                        </td>
                        <td class="text-center">
                            <span class="fuel-status-indicator <?= $station['diesel_available'] ? 'available' : 'unavailable' ?>"></span>
                            <?= availabilityBadge((bool)$station['diesel_available']) ?>
                        </td>
                        <td><?= formatPrice($station['gasoline_price'] !== null ? (float)$station['gasoline_price'] : null) ?></td>
                        <td><?= formatPrice($station['diesel_price'] !== null ? (float)$station['diesel_price'] : null) ?></td>
                        <td>
                            <small class="text-muted">
                                <?= $station['last_updated'] ? timeAgo($station['last_updated']) : 'N/A' ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
// Initialize map with stations
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

// Search functionality
function performSearch() {
    const query = document.getElementById('searchStations').value.trim();
    if (query.length < 2) {
        FuelMonitor.showToast('Digite pelo menos 2 caracteres para pesquisar.', 'warning');
        return;
    }

    FuelMonitor.searchStations(query).then(stations => {
        const resultsDiv = document.getElementById('searchResults');
        if (stations.length === 0) {
            resultsDiv.innerHTML = '<p class="text-muted">Nenhum posto encontrado.</p>';
        } else {
            let html = '<div class="list-group">';
            stations.forEach(s => {
                html += `
                    <a href="stations.php?id=${s.id}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${FuelMonitor.escapeHtml(s.name)}</strong>
                                <br><small class="text-muted">${FuelMonitor.escapeHtml(s.address)}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge ${s.gasoline_available ? 'bg-success' : 'bg-danger'}">Gasolina</span>
                                <span class="badge ${s.diesel_available ? 'bg-success' : 'bg-danger'}">Gasóleo</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
            resultsDiv.innerHTML = html;
        }
        resultsDiv.style.display = 'block';
    });
}

// Search on Enter key
document.getElementById('searchStations')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') performSearch();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
