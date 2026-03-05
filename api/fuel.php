<?php
/**
 * Fuel Update API
 * Fuel Monitoring System - Soyo City
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

$user = getCurrentUser();
if ($user['role'] !== 'operator' && $user['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
}

$stationId = (int)($_POST['station_id'] ?? 0);
$fuelType = $_POST['fuel_type'] ?? '';
$available = (int)($_POST['available'] ?? 0);
$price = !empty($_POST['price']) ? (float)$_POST['price'] : null;

if (!$stationId || !in_array($fuelType, ['gasoline', 'diesel'])) {
    jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
}

$pdo = getDBConnection();

// Verify operator has access to this station
if ($user['role'] === 'operator') {
    $stmt = $pdo->prepare("SELECT station_id FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    if (!$userData || (int)$userData['station_id'] !== $stationId) {
        jsonResponse(['success' => false, 'message' => 'Sem acesso a este posto'], 403);
    }
}

// Get current state before update
$stmt = $pdo->prepare("SELECT gasoline_available, diesel_available FROM stations WHERE id = ?");
$stmt->execute([$stationId]);
$currentState = $stmt->fetch();

if (!$currentState) {
    jsonResponse(['success' => false, 'message' => 'Posto não encontrado'], 404);
}

// Update station
$column = $fuelType === 'gasoline' ? 'gasoline_available' : 'diesel_available';
$priceColumn = $fuelType === 'gasoline' ? 'gasoline_price' : 'diesel_price';

$stmt = $pdo->prepare("UPDATE stations SET {$column} = ?, {$priceColumn} = ?, last_updated = NOW() WHERE id = ?");
$stmt->execute([$available, $price, $stationId]);

// Record history
$stmt = $pdo->prepare("INSERT INTO fuel_history (station_id, fuel_type, available, price, updated_by) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$stationId, $fuelType, $available, $price, $user['id']]);

// Determine if status changed
$previousAvailable = $fuelType === 'gasoline' ? (int)$currentState['gasoline_available'] : (int)$currentState['diesel_available'];

if ($previousAvailable !== $available) {
    // Get station name
    $stmtName = $pdo->prepare("SELECT name FROM stations WHERE id = ?");
    $stmtName->execute([$stationId]);
    $stationName = $stmtName->fetchColumn();

    $fuelName = $fuelType === 'gasoline' ? 'Gasolina' : 'Gasóleo';
    $statusText = $available ? 'disponível' : 'indisponível';

    // Send notifications to subscribed users
    $subStmt = $pdo->prepare("
        SELECT as2.user_id, as2.alert_restock, as2.alert_depleted, as2.email_alert, u.email, u.name
        FROM alert_subscriptions as2
        JOIN users u ON as2.user_id = u.id
        WHERE as2.station_id = ? AND u.is_active = 1
    ");
    $subStmt->execute([$stationId]);
    $subscribers = $subStmt->fetchAll();

    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");

    foreach ($subscribers as $sub) {
        $shouldNotify = ($available && $sub['alert_restock']) || (!$available && $sub['alert_depleted']);

        if ($shouldNotify) {
            $title = "{$fuelName} agora está {$statusText}";
            $message = "O posto '{$stationName}' atualizou: {$fuelName} está agora {$statusText}.";
            $type = $available ? 'success' : 'warning';

            $notifStmt->execute([
                $sub['user_id'],
                $title,
                $message,
                $type,
                '/stations.php?id=' . $stationId
            ]);
        }
    }
}

jsonResponse(['success' => true, 'message' => 'Disponibilidade atualizada com sucesso!']);
