<?php
/**
 * Request New Station Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Solicitar Cadastro de Posto';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

$errors = [];
$success = false;
$ownerName = '';
$ownerEmail = '';
$stationName = '';
$address = '';
$latitude = '';
$longitude = '';
$phone = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token de segurança inválido.';
    }

    $ownerName = trim($_POST['owner_name'] ?? '');
    $ownerEmail = trim($_POST['owner_email'] ?? '');
    $stationName = trim($_POST['station_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($ownerName)) $errors[] = 'Nome do proprietário é obrigatório.';
    if (empty($ownerEmail) || !isValidEmail($ownerEmail)) $errors[] = 'E-mail válido é obrigatório.';
    if (empty($stationName)) $errors[] = 'Nome do posto é obrigatório.';
    if (empty($address)) $errors[] = 'Endereço é obrigatório.';
    if (empty($latitude) || !is_numeric($latitude)) $errors[] = 'Latitude válida é obrigatória.';
    if (empty($longitude) || !is_numeric($longitude)) $errors[] = 'Longitude válida é obrigatória.';

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO station_requests (owner_name, owner_email, station_name, address, latitude, longitude, phone, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ownerName, $ownerEmail, $stationName, $address, $latitude, $longitude, $phone, $description]);

        // Notify admin
        $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();

        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        foreach ($admins as $admin) {
            $notifStmt->execute([
                $admin['id'],
                'Nova Solicitação de Posto',
                "O proprietário {$ownerName} solicitou o cadastro do posto '{$stationName}'.",
                'info',
                BASE_URL . 'admin/requests.php'
            ]);
        }

        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-5">
    <div class="container" style="max-width: 700px;">
        <h2 class="mb-4"><i class="bi bi-building-add"></i> Solicitar Cadastro de Posto</h2>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <h5><i class="bi bi-check-circle"></i> Solicitação Enviada!</h5>
            <p class="mb-0">Sua solicitação foi enviada com sucesso. O administrador irá analisar e responder em breve.</p>
        </div>
        <a href="<?= BASE_URL ?>" class="btn btn-primary">
            <i class="bi bi-house"></i> Voltar ao Início
        </a>
        <?php else: ?>

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
                <p class="text-muted mb-4">
                    Preencha o formulário abaixo para solicitar o cadastro do seu posto de combustível.
                    O administrador irá analisar sua solicitação.
                </p>

                <form method="POST" action="" novalidate>
                    <?= csrfField() ?>

                    <h5 class="mb-3">Dados do Proprietário</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="owner_name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="owner_name" name="owner_name"
                                   value="<?= sanitize($ownerName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="owner_email" class="form-label">E-mail *</label>
                            <input type="email" class="form-control" id="owner_email" name="owner_email"
                                   value="<?= sanitize($ownerEmail) ?>" required>
                        </div>
                    </div>

                    <h5 class="mb-3">Dados do Posto</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="station_name" class="form-label">Nome do Posto *</label>
                            <input type="text" class="form-control" id="station_name" name="station_name"
                                   value="<?= sanitize($stationName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   value="<?= sanitize($phone) ?>" placeholder="+244 9XX XXX XXX">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Endereço Completo *</label>
                            <input type="text" class="form-control" id="address" name="address"
                                   value="<?= sanitize($address) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude *</label>
                            <input type="number" step="any" class="form-control" id="latitude" name="latitude"
                                   value="<?= sanitize($latitude) ?>" placeholder="-6.1349" required>
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude *</label>
                            <input type="number" step="any" class="form-control" id="longitude" name="longitude"
                                   value="<?= sanitize($longitude) ?>" placeholder="12.3691" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Descrição / Observações</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= sanitize($description) ?></textarea>
                        </div>
                    </div>

                    <!-- Map for location selection -->
                    <div class="mb-4">
                        <label class="form-label">Clique no mapa para selecionar a localização:</label>
                        <div id="locationMap" style="height: 300px; border-radius: 10px;"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-send"></i> Enviar Solicitação
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('locationMap').setView([-6.1349, 12.3691], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;

    map.on('click', function(e) {
        document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(8);

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
    });

    // If coordinates are already filled, show marker
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    if (lat && lng) {
        marker = L.marker([lat, lng]).addTo(map);
        map.setView([lat, lng], 15);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
