<?php
/**
 * Admin - System Settings
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Configurações';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = getDBConnection();
$success = false;

// Get current settings
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settingsStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $fieldsToUpdate = ['site_name', 'site_email', 'email_notifications', 'auto_alerts'];

        foreach ($fieldsToUpdate as $field) {
            $value = $_POST[$field] ?? '';

            // Check if setting exists
            $checkStmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $checkStmt->execute([$field]);

            if ($checkStmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $updateStmt->execute([$value, $field]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $insertStmt->execute([$field, $value]);
            }

            $settings[$field] = $value;
        }

        $success = true;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 700px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-gear"></i> Configurações do Sistema</h2>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Configurações salvas com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrfField() ?>

                    <h5 class="mb-3"><i class="bi bi-globe"></i> Configurações Gerais</h5>

                    <div class="mb-3">
                        <label for="site_name" class="form-label">Nome do Site</label>
                        <input type="text" class="form-control" id="site_name" name="site_name"
                               value="<?= sanitize($settings['site_name'] ?? 'Fuel Monitor Soyo') ?>">
                    </div>

                    <div class="mb-4">
                        <label for="site_email" class="form-label">E-mail do Sistema</label>
                        <input type="email" class="form-control" id="site_email" name="site_email"
                               value="<?= sanitize($settings['site_email'] ?? 'noreply@fuelsoyo.com') ?>">
                    </div>

                    <h5 class="mb-3"><i class="bi bi-bell"></i> Configurações de Notificações</h5>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications"
                                   value="1" <?= ($settings['email_notifications'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notifications">
                                Ativar notificações por e-mail
                            </label>
                        </div>
                        <small class="text-muted">
                            Enviar e-mails automáticos quando houver alteração na disponibilidade de combustível.
                        </small>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto_alerts" name="auto_alerts"
                                   value="1" <?= ($settings['auto_alerts'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_alerts">
                                Alertas automáticos no dashboard
                            </label>
                        </div>
                        <small class="text-muted">
                            Criar notificações automáticas no dashboard para utilizadores inscritos.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-check-lg"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
