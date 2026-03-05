<?php
/**
 * Login Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Entrar';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
        case 'operator':
            header('Location: ' . BASE_URL . 'operator/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'user/dashboard.php');
    }
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = 'O e-mail é obrigatório.';
    if (empty($password)) $errors[] = 'A senha é obrigatória.';

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            setUserSession($user);

            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($redirect) {
                header('Location: ' . $redirect);
            } else {
                switch ($user['role']) {
                    case 'admin':
                        header('Location: ' . BASE_URL . 'admin/dashboard.php');
                        break;
                    case 'operator':
                        header('Location: ' . BASE_URL . 'operator/dashboard.php');
                        break;
                    default:
                        header('Location: ' . BASE_URL . 'user/dashboard.php');
                }
            }
            exit;
        } else {
            $errors[] = 'E-mail ou senha incorretos.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-5">
    <div class="auth-container">
        <div class="card auth-card">
            <div class="card-header">
                <h3><i class="bi bi-box-arrow-in-right"></i> Entrar</h3>
                <p class="mb-0 opacity-75">Acesse sua conta</p>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= sanitize($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <?= csrfField() ?>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="seu@email.com" value="<?= sanitize($email) ?>" required>
                        <label for="email"><i class="bi bi-envelope"></i> E-mail</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Senha" required>
                        <label for="password"><i class="bi bi-lock"></i> Senha</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Lembrar-me</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                </form>

                <div class="text-center">
                    <p class="mb-0">Não tem conta?
                        <a href="<?= BASE_URL ?>register.php">Cadastre-se aqui</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
