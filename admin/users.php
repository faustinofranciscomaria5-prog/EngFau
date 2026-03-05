<?php
/**
 * Admin - Manage Users
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Gerenciar Utilizadores';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();
$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $action = $_POST['action'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId && $userId !== $user['id']) {
            switch ($action) {
                case 'toggle_active':
                    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$userId]);
                    setFlash('success', 'Status do utilizador atualizado.');
                    break;

                case 'delete_user':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    setFlash('success', 'Utilizador removido com sucesso.');
                    break;

                case 'change_role':
                    $newRole = $_POST['new_role'] ?? '';
                    if (in_array($newRole, ['user', 'operator', 'admin'])) {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$newRole, $userId]);
                        setFlash('success', 'Função do utilizador atualizada.');
                    }
                    break;
            }
        }
        header('Location: ' . BASE_URL . 'admin/users.php');
        exit;
    }
}

// Get filter
$roleFilter = $_GET['role'] ?? '';
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT u.*, s.name as station_name FROM users u LEFT JOIN stations s ON u.station_id = s.id WHERE 1=1";
$params = [];

if ($roleFilter && in_array($roleFilter, ['admin', 'operator', 'user'])) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($searchQuery) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gerenciar Utilizadores</h2>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" name="q" value="<?= sanitize($searchQuery) ?>"
                               placeholder="Nome ou e-mail...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filtrar por Função</label>
                        <select class="form-select" name="role">
                            <option value="">Todos</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="operator" <?= $roleFilter === 'operator' ? 'selected' : '' ?>>Operador</option>
                            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>Utilizador</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary w-100">Limpar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Função</th>
                                <th>Posto</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><strong><?= sanitize($u['name']) ?></strong></td>
                                <td><?= sanitize($u['email']) ?></td>
                                <td>
                                    <?php
                                    $roleBadges = ['admin' => 'bg-danger', 'operator' => 'bg-warning text-dark', 'user' => 'bg-info'];
                                    $roleNames = ['admin' => 'Admin', 'operator' => 'Operador', 'user' => 'Utilizador'];
                                    ?>
                                    <span class="badge <?= $roleBadges[$u['role']] ?>">
                                        <?= $roleNames[$u['role']] ?>
                                    </span>
                                </td>
                                <td><?= $u['station_name'] ? sanitize($u['station_name']) : '-' ?></td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= formatDate($u['created_at'], 'd/m/Y') ?></small></td>
                                <td>
                                    <?php if ((int)$u['id'] !== $user['id']): ?>
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>"
                                                    title="<?= $u['is_active'] ? 'Desativar' : 'Ativar' ?>">
                                                <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este utilizador?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Remover">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted small">Você</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2">Total: <?= count($users) ?> utilizador(es)</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
