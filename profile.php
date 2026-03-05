<?php
/**
 * User Profile Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Meu Perfil';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

$errors = [];
$success = false;

// Get full user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token de segurança inválido.';
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) $errors[] = 'O nome é obrigatório.';
        if (empty($email) || !isValidEmail($email)) $errors[] = 'E-mail válido é obrigatório.';

        // Check email uniqueness
        if ($email !== $userData['email']) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $user['id']]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Este e-mail já está em uso.';
            }
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $updateStmt->execute([$name, $email, $user['id']]);
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $userData['name'] = $name;
            $userData['email'] = $email;
            $success = true;
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) $errors[] = 'Senha atual é obrigatória.';
        if (empty($newPassword) || strlen($newPassword) < 6) $errors[] = 'Nova senha deve ter pelo menos 6 caracteres.';
        if ($newPassword !== $confirmPassword) $errors[] = 'As senhas não coincidem.';

        if (empty($errors)) {
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = 'Senha atual incorreta.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $user['id']]);
                $success = true;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 700px;">
        <h2 class="mb-4"><i class="bi bi-person"></i> Meu Perfil</h2>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Perfil atualizado com sucesso!
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

        <!-- Profile Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informações Pessoais</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= sanitize($userData['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= sanitize($userData['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" class="form-control" value="<?= ucfirst($userData['role']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Membro desde</label>
                        <input type="text" class="form-control" value="<?= formatDate($userData['created_at'], 'd/m/Y') ?>" readonly>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Alterar Senha</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-lock"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
