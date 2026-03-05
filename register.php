<?php
/**
 * Registration Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Cadastrar';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

$errors = [];
$name = '';
$email = '';
$role = 'user';
$operatorCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $operatorCode = trim($_POST['operator_code'] ?? '');

    // Validations
    if (empty($name)) $errors[] = 'O nome é obrigatório.';
    if (strlen($name) < 3) $errors[] = 'O nome deve ter pelo menos 3 caracteres.';
    if (empty($email)) $errors[] = 'O e-mail é obrigatório.';
    if (!isValidEmail($email)) $errors[] = 'E-mail inválido.';
    if (empty($password)) $errors[] = 'A senha é obrigatória.';
    if (strlen($password) < 6) $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    if ($password !== $confirmPassword) $errors[] = 'As senhas não coincidem.';
    if (!in_array($role, ['user', 'operator'])) $errors[] = 'Categoria de usuário inválida.';

    // If operator, validate code
    $stationId = null;
    if ($role === 'operator') {
        if (empty($operatorCode)) {
            $errors[] = 'O código do posto é obrigatório para operadores.';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM stations WHERE operator_code = ? AND status = 'approved'");
            $stmt->execute([$operatorCode]);
            $station = $stmt->fetch();
            if (!$station) {
                $errors[] = 'Código do posto inválido ou posto não aprovado.';
            } else {
                $stationId = $station['id'];
                // Check if station already has an operator
                $stmt = $pdo->prepare("SELECT id FROM users WHERE station_id = ? AND role = 'operator' AND is_active = 1");
                $stmt->execute([$stationId]);
                if ($stmt->fetch()) {
                    $errors[] = 'Este posto já tem um operador registrado.';
                }
            }
        }
    }

    // Check if email already exists
    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este e-mail já está cadastrado.';
        }
    }

    // Create user
    if (empty($errors)) {
        $pdo = getDBConnection();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, station_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $role, $stationId]);

        $userId = $pdo->lastInsertId();

        // Create welcome notification
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $userId,
            'Bem-vindo ao Fuel Monitor Soyo!',
            'Sua conta foi criada com sucesso. Explore os postos de combustível disponíveis na cidade do Soyo.',
            'success'
        ]);

        // Log in the user
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ];
        setUserSession($user);

        setFlash('success', 'Conta criada com sucesso! Bem-vindo ao Fuel Monitor Soyo.');

        if ($role === 'operator') {
            header('Location: ' . BASE_URL . 'operator/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'user/dashboard.php');
        }
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-5">
    <div class="auth-container" style="max-width: 600px;">
        <div class="card auth-card">
            <div class="card-header">
                <h3><i class="bi bi-person-plus"></i> Cadastrar</h3>
                <p class="mb-0 opacity-75">Crie sua conta</p>
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
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="Seu Nome" value="<?= sanitize($name) ?>" required>
                        <label for="name"><i class="bi bi-person"></i> Nome Completo</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="seu@email.com" value="<?= sanitize($email) ?>" required>
                        <label for="email"><i class="bi bi-envelope"></i> E-mail</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Senha" required minlength="6">
                        <label for="password"><i class="bi bi-lock"></i> Senha (mín. 6 caracteres)</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Confirmar Senha" required>
                        <label for="confirm_password"><i class="bi bi-lock-fill"></i> Confirmar Senha</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoria de Usuário</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check card p-3 <?= $role === 'user' ? 'border-primary' : '' ?>">
                                    <input class="form-check-input" type="radio" name="role" id="roleUser"
                                           value="user" <?= $role === 'user' ? 'checked' : '' ?> onchange="toggleOperatorCode()">
                                    <label class="form-check-label" for="roleUser">
                                        <i class="bi bi-person text-primary"></i> <strong>Usuário Comum</strong>
                                        <br><small class="text-muted">Receba alertas de combustível</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check card p-3 <?= $role === 'operator' ? 'border-primary' : '' ?>">
                                    <input class="form-check-input" type="radio" name="role" id="roleOperator"
                                           value="operator" <?= $role === 'operator' ? 'checked' : '' ?> onchange="toggleOperatorCode()">
                                    <label class="form-check-label" for="roleOperator">
                                        <i class="bi bi-tools text-warning"></i> <strong>Operador</strong>
                                        <br><small class="text-muted">Gerencie um posto</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="operatorCodeGroup" class="mb-3" style="display: <?= $role === 'operator' ? 'block' : 'none' ?>;">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="operator_code" name="operator_code"
                                   placeholder="Código do Posto" value="<?= sanitize($operatorCode) ?>">
                            <label for="operator_code"><i class="bi bi-key"></i> Código do Posto (fornecido pelo Admin)</label>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> O código é gerado pelo administrador ao aprovar o posto.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="bi bi-person-plus"></i> Criar Conta
                    </button>
                </form>

                <div class="text-center">
                    <p class="mb-1">Já tem conta? <a href="<?= BASE_URL ?>login.php">Entre aqui</a></p>
                    <p class="mb-0"><a href="<?= BASE_URL ?>request-station.php">Solicitar cadastro de posto</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleOperatorCode() {
    const isOperator = document.getElementById('roleOperator').checked;
    document.getElementById('operatorCodeGroup').style.display = isOperator ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
