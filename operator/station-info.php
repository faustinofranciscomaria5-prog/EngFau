<?php
/**
 * Operator - Station Info
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Informações do Posto';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('operator');

$user = getCurrentUser();
$pdo = getDBConnection();

// Get operator's station
$stmt = $pdo->prepare("SELECT u.station_id, s.* FROM users u JOIN stations s ON u.station_id = s.id WHERE u.id = ?");
$stmt->execute([$user['id']]);
$station = $stmt->fetch();

if (!$station) {
    setFlash('danger', 'Nenhum posto vinculado à sua conta.');
    header('Location: ' . BASE_URL . 'operator/dashboard.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $openingTime = $_POST['opening_time'] ?? '06:00';
        $closingTime = $_POST['closing_time'] ?? '22:00';

        $updateStmt = $pdo->prepare("UPDATE stations SET phone = ?, opening_time = ?, closing_time = ? WHERE id = ?");
        $updateStmt->execute([$phone, $openingTime, $closingTime, $station['station_id']]);

        $success = true;

        // Refresh data
        $stmt = $pdo->prepare("SELECT u.station_id, s.* FROM users u JOIN stations s ON u.station_id = s.id WHERE u.id = ?");
        $stmt->execute([$user['id']]);
        $station = $stmt->fetch();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 700px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-info-circle"></i> Informações do Posto</h2>
            <a href="<?= BASE_URL ?>operator/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Informações atualizadas com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Station Details (read-only) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Dados do Posto</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Nome</label>
                        <p class="fw-bold"><?= sanitize($station['name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Código Operador</label>
                        <p><code><?= sanitize($station['operator_code']) ?></code></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted">Endereço</label>
                        <p><?= sanitize($station['address']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Coordenadas</label>
                        <p><?= $station['latitude'] ?>, <?= $station['longitude'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Status</label>
                        <p><?= statusBadge($station['status']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editable Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informações Editáveis</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="<?= sanitize($station['phone'] ?? '') ?>" placeholder="+244 9XX XXX XXX">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="opening_time" class="form-label">Horário de Abertura</label>
                            <input type="time" class="form-control" id="opening_time" name="opening_time"
                                   value="<?= $station['opening_time'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="closing_time" class="form-label">Horário de Fecho</label>
                            <input type="time" class="form-control" id="closing_time" name="closing_time"
                                   value="<?= $station['closing_time'] ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>

        <!-- Map -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Localização</h5>
            </div>
            <div class="card-body p-0">
                <div id="stationMap" style="height: 300px;"></div>
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
