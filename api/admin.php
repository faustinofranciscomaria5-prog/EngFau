<?php
/**
 * Admin API
 * Fuel Monitoring System - Soyo City
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'stats':
        $stats = [];

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
        $stats['total_users'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'operator' AND is_active = 1");
        $stats['total_operators'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved'");
        $stats['total_stations'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM station_requests WHERE status = 'pending'");
        $stats['pending_requests'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved' AND gasoline_available = 1");
        $stats['gasoline_available'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'approved' AND diesel_available = 1");
        $stats['diesel_available'] = (int)$stmt->fetchColumn();

        // Recent activity
        $stmt = $pdo->query("
            SELECT fh.*, s.name as station_name, u.name as user_name
            FROM fuel_history fh
            JOIN stations s ON fh.station_id = s.id
            JOIN users u ON fh.updated_by = u.id
            ORDER BY fh.created_at DESC
            LIMIT 10
        ");
        $stats['recent_activity'] = $stmt->fetchAll();

        // Fuel history for charts (last 7 days)
        $stmt = $pdo->query("
            SELECT DATE(created_at) as date,
                   SUM(CASE WHEN fuel_type = 'gasoline' AND available = 1 THEN 1 ELSE 0 END) as gasoline_restocks,
                   SUM(CASE WHEN fuel_type = 'diesel' AND available = 1 THEN 1 ELSE 0 END) as diesel_restocks,
                   SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as depletions
            FROM fuel_history
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stats['chart_data'] = $stmt->fetchAll();

        // Station popularity (by subscriptions)
        $stmt = $pdo->query("
            SELECT s.name, COUNT(als.id) as subscribers
            FROM stations s
            LEFT JOIN alert_subscriptions als ON s.id = als.station_id
            WHERE s.status = 'approved'
            GROUP BY s.id, s.name
            ORDER BY subscribers DESC
            LIMIT 10
        ");
        $stats['station_popularity'] = $stmt->fetchAll();

        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    case 'toggle_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            jsonResponse(['success' => false, 'message' => 'Usuário inválido'], 400);
        }
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != ?");
        $stmt->execute([$userId, $user['id']]);
        jsonResponse(['success' => true, 'message' => 'Status do usuário atualizado.']);
        break;

    case 'delete_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId || $userId === $user['id']) {
            jsonResponse(['success' => false, 'message' => 'Operação inválida'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        jsonResponse(['success' => true, 'message' => 'Usuário removido.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
}
