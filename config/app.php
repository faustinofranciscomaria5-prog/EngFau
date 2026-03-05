<?php
/**
 * Application Configuration
 * Fuel Monitoring System - Soyo City
 */

// Base URL - adjust for your environment
define('BASE_URL', '/');
define('SITE_NAME', 'Fuel Monitor Soyo');
define('SITE_EMAIL', 'noreply@fuelsoyo.com');

// Upload paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('STATION_PHOTOS_DIR', UPLOAD_DIR . 'stations/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('Africa/Luanda');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CSRF Token name
define('CSRF_TOKEN_NAME', 'csrf_token');
