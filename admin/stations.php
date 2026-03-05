<?php
/**
 * Admin - Manage Stations
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Gerenciar Postos';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();
$errors = [];
$success = false;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_station':
                $stationId = (int)($_POST['station_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $openingTime = $_POST['opening_time'] ?? '06:00';
                $closingTime = $_POST['closing_time'] ?? '22:00';

                if ($stationId && $name && $address) {
                    // Handle photo upload
                    $photo = null;
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $photo = uploadFile($_FILES['photo'], STATION_PHOTOS_DIR);
                    }

                    $sql = "UPDATE stations SET name = ?, address = ?, phone = ?, opening_time = ?, closing_time = ?";
                    $params = [$name, $address, $phone, $openingTime, $closingTime];

                    if ($photo) {
                        $sql .= ", photo = ?";
                        $params[] = $photo;
                    }

                    $sql .= " WHERE id = ?";
                    $params[] = $stationId;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    setFlash('success', 'Posto atualizado com sucesso!');
                    header('Location: ' . BASE_URL . 'admin/stations.php');
                    exit;
                }
                break;

            case 'delete_station':
                $stationId = (int)($_POST['station_id'] ?? 0);
                if ($stationId) {
                    $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
                    $stmt->execute([$stationId]);
                    setFlash('success', 'Posto removido com sucesso!');
                    header('Location: ' . BASE_URL . 'admin/stations.php');
                    exit;
                }
                break;
        }
    }
}

// Get all stations
$stations = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM users u WHERE u.station_id = s.id AND u.role = 'operator') as operator_count FROM stations ORDER BY status ASC, name ASC")->fetchAll();

// Edit mode
$editStation = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$editId]);
    $editStation = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building"></i> Gerenciar Postos</h2>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($editStation): ?>
        <!-- Edit Station Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Editar Posto: <?= sanitize($editStation['name']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_station">
                    <input type="hidden" name="station_id" value="<?= $editStation['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="name" value="<?= sanitize($editStation['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="phone" value="<?= sanitize($editStation['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="address" value="<?= sanitize($editStation['address']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Horário de Abertura</label>
                            <input type="time" class="form-control" name="opening_time" value="<?= $editStation['opening_time'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Horário de Fecho</label>
                            <input type="time" class="form-control" name="closing_time" value="<?= $editStation['closing_time'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Foto do Posto</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                        <a href="<?= BASE_URL ?>admin/stations.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stations List -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Endereço</th>
                                <th>Código Operador</th>
                                <th>Operadores</th>
                                <th>Gasolina</th>
                                <th>Gasóleo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stations as $station): ?>
                            <tr>
                                <td><?= $station['id'] ?></td>
                                <td><strong><?= sanitize($station['name']) ?></strong></td>
                                <td><small><?= sanitize($station['address']) ?></small></td>
                                <td><code><?= sanitize($station['operator_code']) ?></code></td>
                                <td><span class="badge bg-info"><?= $station['operator_count'] ?></span></td>
                                <td><?= availabilityBadge((bool)$station['gasoline_available']) ?></td>
                                <td><?= availabilityBadge((bool)$station['diesel_available']) ?></td>
                                <td><?= statusBadge($station['status']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?= $station['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja remover este posto?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_station">
                                            <input type="hidden" name="station_id" value="<?= $station['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Remover">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
