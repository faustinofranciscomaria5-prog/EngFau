<?php
/**
 * Logout
 * Fuel Monitoring System - Soyo City
 */
require_once __DIR__ . '/includes/session.php';

destroySession();
header('Location: ' . BASE_URL . 'login.php');
exit;
