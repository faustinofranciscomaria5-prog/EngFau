<?php
/**
 * Admin - Station Requests
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Solicitações de Postos';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId) {
            $stmt = $pdo->prepare("SELECT * FROM station_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if ($request) {
                switch ($action) {
                    case 'approve':
                        $adminNotes = trim($_POST['admin_notes'] ?? '');
                        $operatorCode = generateOperatorCode();

                        // Handle photo upload
                        $photo = null;
                        if (isset($_FILES['station_photo']) && $_FILES['station_photo']['error'] === UPLOAD_ERR_OK) {
                            $photo = uploadFile($_FILES['station_photo'], STATION_PHOTOS_DIR);
                        }

                        // Create station
                        $stmtInsert = $pdo->prepare("
                            INSERT INTO stations (name, address, latitude, longitude, phone, photo, operator_code, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')
                        ");
                        $stmtInsert->execute([
                            $request['station_name'],
                            $request['address'],
                            $request['latitude'],
                            $request['longitude'],
                            $request['phone'],
                            $photo,
                            $operatorCode
                        ]);

                        // Update request
                        $stmtUpdate = $pdo->prepare("UPDATE station_requests SET status = 'approved', admin_notes = ? WHERE id = ?");
                        $stmtUpdate->execute([$adminNotes, $requestId]);

                        setFlash('success', "Posto aprovado! Código do operador: {$operatorCode}");
                        header('Location: ' . BASE_URL . 'admin/requests.php');
                        exit;
                        break;

                    case 'reject':
                        $adminNotes = trim($_POST['admin_notes'] ?? '');
                        $stmt = $pdo->prepare("UPDATE station_requests SET status = 'rejected', admin_notes = ? WHERE id = ?");
                        $stmt->execute([$adminNotes, $requestId]);
                        setFlash('warning', 'Solicitação rejeitada.');
                        header('Location: ' . BASE_URL . 'admin/requests.php');
                        exit;
                        break;
                }
            }
        }
    }
}

// Get requests
$statusFilter = $_GET['status'] ?? 'pending';
$sql = "SELECT * FROM station_requests";
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " WHERE status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-inbox"></i> Solicitações de Postos</h2>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Status Filter -->
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'pending' ? 'active' : '' ?>" href="?status=pending">
                    Pendentes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'approved' ? 'active' : '' ?>" href="?status=approved">
                    Aprovadas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'rejected' ? 'active' : '' ?>" href="?status=rejected">
                    Rejeitadas
                </a>
            </li>
        </ul>

        <?php if (empty($requests)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-2">Nenhuma solicitação <?= $statusFilter === 'pending' ? 'pendente' : '' ?>.</p>
        </div>
        <?php else: ?>

        <?php foreach ($requests as $req): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?= sanitize($req['station_name']) ?></h5>
                    <small class="text-muted">Solicitado em <?= formatDate($req['created_at']) ?></small>
                </div>
                <?= statusBadge($req['status']) ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Proprietário:</strong> <?= sanitize($req['owner_name']) ?></p>
                        <p><strong>E-mail:</strong> <?= sanitize($req['owner_email']) ?></p>
                        <p><strong>Endereço:</strong> <?= sanitize($req['address']) ?></p>
                        <p><strong>Coordenadas:</strong> <?= $req['latitude'] ?>, <?= $req['longitude'] ?></p>
                        <?php if ($req['phone']): ?>
                        <p><strong>Telefone:</strong> <?= sanitize($req['phone']) ?></p>
                        <?php endif; ?>
                        <?php if ($req['description']): ?>
                        <p><strong>Descrição:</strong> <?= sanitize($req['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($req['admin_notes']): ?>
                        <p><strong>Notas do Admin:</strong> <?= sanitize($req['admin_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div id="reqMap-<?= $req['id'] ?>" style="height: 200px; border-radius: 10px;"></div>
                    </div>
                </div>

                <?php if ($req['status'] === 'pending'): ?>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Foto Oficial do Posto</label>
                                <input type="file" class="form-control" name="station_photo" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notas (opcional)</label>
                                <textarea class="form-control" name="admin_notes" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Aprovar
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Motivo da Rejeição</label>
                                <textarea class="form-control" name="admin_notes" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Tem certeza que deseja rejeitar esta solicitação?');">
                                <i class="bi bi-x-lg"></i> Rejeitar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapId = 'reqMap-<?= $req['id'] ?>';
            const lat = <?= (float)$req['latitude'] ?>;
            const lng = <?= (float)$req['longitude'] ?>;
            const map = L.map(mapId).setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OSM'
            }).addTo(map);
            L.marker([lat, lng]).addTo(map);
        });
        </script>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
