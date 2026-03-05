<?php
/**
 * Stations API
 * Fuel Monitoring System - Soyo City
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

switch ($action) {
    case 'availability':
        $stmt = $pdo->query("SELECT id, name, address, gasoline_available, diesel_available, gasoline_price, diesel_price, last_updated FROM stations WHERE status = 'approved' ORDER BY name ASC");
        $stations = $stmt->fetchAll();
        jsonResponse(['success' => true, 'stations' => $stations]);
        break;

    case 'search':
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            jsonResponse(['success' => false, 'message' => 'Query too short'], 400);
        }
        $stmt = $pdo->prepare("SELECT id, name, address, gasoline_available, diesel_available FROM stations WHERE status = 'approved' AND (name LIKE ? OR address LIKE ?) ORDER BY name ASC LIMIT 20");
        $search = "%{$query}%";
        $stmt->execute([$search, $search]);
        $stations = $stmt->fetchAll();
        jsonResponse(['success' => true, 'stations' => $stations]);
        break;

    case 'map':
        $stmt = $pdo->query("SELECT id, name, address, latitude, longitude, phone, gasoline_available, diesel_available FROM stations WHERE status = 'approved'");
        $stations = $stmt->fetchAll();
        jsonResponse(['success' => true, 'stations' => $stations]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
