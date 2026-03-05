<?php
/**
 * Header Template
 * Fuel Monitoring System - Soyo City
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

$currentUser = getCurrentUser();
$flash = getFlash();
$notificationCount = 0;

if ($currentUser) {
    $pdo = getDBConnection();
    $notificationCount = getUnreadNotificationCount($pdo, $currentUser['id']);
}

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME;
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">
            <i class="bi bi-fuel-pump-fill me-2"></i><?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php">
                        <i class="bi bi-house"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>stations.php">
                        <i class="bi bi-geo-alt"></i> Postos
                    </a>
                </li>
                <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/stations.php"><i class="bi bi-building"></i> Postos</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/users.php"><i class="bi bi-people"></i> Utilizadores</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/requests.php"><i class="bi bi-inbox"></i> Solicitações</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/reports.php"><i class="bi bi-graph-up"></i> Relatórios</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/settings.php"><i class="bi bi-gear"></i> Configurações</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if ($currentUser && $currentUser['role'] === 'operator'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-tools"></i> Operador
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operator/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operator/update-fuel.php"><i class="bi bi-droplet"></i> Atualizar Combustível</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>operator/station-info.php"><i class="bi bi-info-circle"></i> Info do Posto</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($currentUser): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= BASE_URL ?>notifications.php">
                        <i class="bi bi-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $notificationCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitize($currentUser['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= ucfirst($currentUser['role']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person"></i> Perfil</a></li>
                        <?php if ($currentUser['role'] === 'user'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>user/alerts.php"><i class="bi bi-bell"></i> Meus Alertas</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>login.php"><i class="bi bi-box-arrow-in-right"></i> Entrar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm ms-2 px-3" href="<?= BASE_URL ?>register.php">Cadastrar</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
