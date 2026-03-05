<?php
/**
 * Helper Functions
 * Fuel Monitoring System - Soyo City
 */

/**
 * Sanitize input string
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique station operator code
 */
function generateOperatorCode(): string
{
    return 'SOYO-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'd/m/Y H:i'): string
{
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Format currency (Angolan Kwanza)
 */
function formatPrice(?float $price): string
{
    if ($price === null) {
        return 'N/A';
    }
    return number_format($price, 2, ',', '.') . ' Kz';
}

/**
 * Get fuel availability badge HTML
 */
function availabilityBadge(bool $available): string
{
    if ($available) {
        return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Disponível</span>';
    }
    return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Indisponível</span>';
}

/**
 * Get status badge HTML
 */
function statusBadge(string $status): string
{
    $badges = [
        'pending'  => '<span class="badge bg-warning text-dark">Pendente</span>',
        'approved' => '<span class="badge bg-success">Aprovado</span>',
        'rejected' => '<span class="badge bg-danger">Rejeitado</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Desconhecido</span>';
}

/**
 * Handle file upload
 */
function uploadFile(array $file, string $destination, array $allowedTypes = ['image/jpeg', 'image/png', 'image/webp']): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return null;
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('station_', true) . '.' . $ext;
    $filepath = $destination . $filename;

    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return null;
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get time ago string
 */
function timeAgo(string $datetime): string
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' ano(s) atrás';
    if ($diff->m > 0) return $diff->m . ' mês(es) atrás';
    if ($diff->d > 0) return $diff->d . ' dia(s) atrás';
    if ($diff->h > 0) return $diff->h . ' hora(s) atrás';
    if ($diff->i > 0) return $diff->i . ' minuto(s) atrás';
    return 'Agora mesmo';
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
