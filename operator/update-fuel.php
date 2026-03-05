<?php
/**
 * Operator - Update Fuel Availability
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Atualizar Combustível';
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

$errors = [];
$success = false;

if (!$station) {
    setFlash('danger', 'Nenhum posto vinculado à sua conta.');
    header('Location: ' . BASE_URL . 'operator/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $gasolineAvailable = isset($_POST['gasoline_available']) ? 1 : 0;
        $dieselAvailable = isset($_POST['diesel_available']) ? 1 : 0;
        $gasolinePrice = !empty($_POST['gasoline_price']) ? (float)$_POST['gasoline_price'] : null;
        $dieselPrice = !empty($_POST['diesel_price']) ? (float)$_POST['diesel_price'] : null;

        // Update station
        $updateStmt = $pdo->prepare("
            UPDATE stations SET
                gasoline_available = ?, diesel_available = ?,
                gasoline_price = ?, diesel_price = ?,
                last_updated = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$gasolineAvailable, $dieselAvailable, $gasolinePrice, $dieselPrice, $station['station_id']]);

        // Record history
        $histStmt = $pdo->prepare("INSERT INTO fuel_history (station_id, fuel_type, available, price, updated_by) VALUES (?, ?, ?, ?, ?)");

        if ((int)$station['gasoline_available'] !== $gasolineAvailable || (float)($station['gasoline_price'] ?? 0) !== (float)($gasolinePrice ?? 0)) {
            $histStmt->execute([$station['station_id'], 'gasoline', $gasolineAvailable, $gasolinePrice, $user['id']]);
        }

        if ((int)$station['diesel_available'] !== $dieselAvailable || (float)($station['diesel_price'] ?? 0) !== (float)($dieselPrice ?? 0)) {
            $histStmt->execute([$station['station_id'], 'diesel', $dieselAvailable, $dieselPrice, $user['id']]);
        }

        // Send notifications for changes
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");

        $changes = [];
        if ((int)$station['gasoline_available'] !== $gasolineAvailable) {
            $changes[] = 'Gasolina: ' . ($gasolineAvailable ? 'Disponível' : 'Indisponível');
        }
        if ((int)$station['diesel_available'] !== $dieselAvailable) {
            $changes[] = 'Gasóleo: ' . ($dieselAvailable ? 'Disponível' : 'Indisponível');
        }

        if (!empty($changes)) {
            $subStmt = $pdo->prepare("
                SELECT als.user_id FROM alert_subscriptions als
                JOIN users u ON als.user_id = u.id
                WHERE als.station_id = ? AND u.is_active = 1
            ");
            $subStmt->execute([$station['station_id']]);
            $subscribers = $subStmt->fetchAll();

            foreach ($subscribers as $sub) {
                $notifStmt->execute([
                    $sub['user_id'],
                    'Atualização de combustível - ' . $station['name'],
                    implode(', ', $changes),
                    $gasolineAvailable || $dieselAvailable ? 'success' : 'warning',
                    '/stations.php?id=' . $station['station_id']
                ]);
            }
        }

        $success = true;

        // Refresh station data
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
            <h2><i class="bi bi-droplet"></i> Atualizar Combustível</h2>
            <a href="<?= BASE_URL ?>operator/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Posto: <strong><?= sanitize($station['name']) ?></strong>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Disponibilidade atualizada com sucesso! Os utilizadores inscritos foram notificados.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrfField() ?>

                    <!-- Gasoline -->
                    <div class="card mb-4 <?= $station['gasoline_available'] ? 'border-success' : 'border-danger' ?>">
                        <div class="card-header <?= $station['gasoline_available'] ? 'bg-success' : 'bg-danger' ?> text-white">
                            <h5 class="mb-0"><i class="bi bi-droplet-fill"></i> Gasolina</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="gasoline_available"
                                               name="gasoline_available" <?= $station['gasoline_available'] ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="gasoline_available">
                                            Disponível
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="gasoline_price" class="form-label">Preço (Kz)</label>
                                    <input type="number" step="0.01" class="form-control" id="gasoline_price"
                                           name="gasoline_price" value="<?= $station['gasoline_price'] ?>"
                                           placeholder="300.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Diesel -->
                    <div class="card mb-4 <?= $station['diesel_available'] ? 'border-success' : 'border-danger' ?>">
                        <div class="card-header <?= $station['diesel_available'] ? 'bg-success' : 'bg-danger' ?> text-white">
                            <h5 class="mb-0"><i class="bi bi-droplet-half"></i> Gasóleo</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="diesel_available"
                                               name="diesel_available" <?= $station['diesel_available'] ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="diesel_available">
                                            Disponível
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="diesel_price" class="form-label">Preço (Kz)</label>
                                    <input type="number" step="0.01" class="form-control" id="diesel_price"
                                           name="diesel_price" value="<?= $station['diesel_price'] ?>"
                                           placeholder="280.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-check-lg"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
