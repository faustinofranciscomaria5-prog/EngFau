<?php
/**
 * Alert Subscriptions API
 * Fuel Monitoring System - Soyo City
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
}

$user = getCurrentUser();
$pdo = getDBConnection();

$action = $_POST['action'] ?? '';
$stationId = (int)($_POST['station_id'] ?? 0);

if (!$stationId) {
    jsonResponse(['success' => false, 'message' => 'Posto inválido'], 400);
}

switch ($action) {
    case 'subscribe':
        // Check if already subscribed
        $stmt = $pdo->prepare("SELECT id FROM alert_subscriptions WHERE user_id = ? AND station_id = ?");
        $stmt->execute([$user['id'], $stationId]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Já está inscrito neste posto.']);
        }

        $stmt = $pdo->prepare("INSERT INTO alert_subscriptions (user_id, station_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $stationId]);
        jsonResponse(['success' => true, 'message' => 'Inscrito para alertas deste posto!']);
        break;

    case 'unsubscribe':
        $stmt = $pdo->prepare("DELETE FROM alert_subscriptions WHERE user_id = ? AND station_id = ?");
        $stmt->execute([$user['id'], $stationId]);
        jsonResponse(['success' => true, 'message' => 'Inscrição de alertas cancelada.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
}
