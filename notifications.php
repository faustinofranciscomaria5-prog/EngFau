<?php
/**
 * Notifications Page
 * Fuel Monitoring System - Soyo City
 */
$pageTitle = 'Notificações';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bell"></i> Notificações</h2>
            <?php if (!empty($notifications)): ?>
            <button class="btn btn-outline-primary btn-sm" onclick="markAllRead()">
                <i class="bi bi-check-all"></i> Marcar Todas como Lidas
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="text-center py-5">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <p class="text-muted mt-2">Nenhuma notificação.</p>
        </div>
        <?php else: ?>
        <div class="list-group" id="notificationsList">
            <?php foreach ($notifications as $notif): ?>
            <div class="list-group-item notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>"
                 id="notif-<?= $notif['id'] ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?php
                            $iconMap = ['info' => 'info-circle text-primary', 'success' => 'check-circle text-success',
                                       'warning' => 'exclamation-triangle text-warning', 'danger' => 'x-circle text-danger'];
                            $icon = $iconMap[$notif['type']] ?? 'info-circle text-primary';
                            ?>
                            <i class="bi bi-<?= $icon ?>"></i>
                            <strong><?= sanitize($notif['title']) ?></strong>
                            <?php if (!$notif['is_read']): ?>
                            <span class="badge bg-primary">Nova</span>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><?= sanitize($notif['message']) ?></p>
                        <?php if ($notif['link']): ?>
                        <a href="<?= sanitize($notif['link']) ?>" class="btn btn-sm btn-outline-primary">Ver mais</a>
                        <?php endif; ?>
                    </div>
                    <div class="text-end ms-3">
                        <span class="notification-time"><?= timeAgo($notif['created_at']) ?></span>
                        <?php if (!$notif['is_read']): ?>
                        <br><button class="btn btn-sm btn-link p-0" onclick="markRead(<?= $notif['id'] ?>)">Marcar como lida</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
async function markRead(id) {
    const data = await FuelMonitor.markNotificationRead(id);
    if (data.success) {
        const el = document.getElementById('notif-' + id);
        el.classList.remove('unread');
        el.querySelector('.badge')?.remove();
        el.querySelector('.btn-link')?.remove();
    }
}

async function markAllRead() {
    const data = await FuelMonitor.markAllNotificationsRead();
    if (data.success) {
        document.querySelectorAll('.notification-item.unread').forEach(el => {
            el.classList.remove('unread');
            el.querySelector('.badge')?.remove();
            el.querySelector('.btn-link')?.remove();
        });
        FuelMonitor.showToast('Todas as notificações foram marcadas como lidas.', 'success');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
