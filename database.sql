-- ============================================
-- Fuel Monitoring System - Soyo City
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS fuel_monitor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fuel_monitor;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator', 'user') NOT NULL DEFAULT 'user',
    station_id INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============================================
-- Stations Table
-- ============================================
CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    operator_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    opening_time TIME DEFAULT '06:00:00',
    closing_time TIME DEFAULT '22:00:00',
    gasoline_available TINYINT(1) NOT NULL DEFAULT 0,
    diesel_available TINYINT(1) NOT NULL DEFAULT 0,
    gasoline_price DECIMAL(10, 2) DEFAULT NULL,
    diesel_price DECIMAL(10, 2) DEFAULT NULL,
    last_updated DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_operator_code (operator_code)
) ENGINE=InnoDB;

-- ============================================
-- Station Requests Table (for new station applications)
-- ============================================
CREATE TABLE IF NOT EXISTS station_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    owner_email VARCHAR(150) NOT NULL,
    station_name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- Fuel Availability History Table
-- ============================================
CREATE TABLE IF NOT EXISTS fuel_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    fuel_type ENUM('gasoline', 'diesel') NOT NULL,
    available TINYINT(1) NOT NULL,
    price DECIMAL(10, 2) DEFAULT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_station_fuel (station_id, fuel_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Notifications Table
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- User Alert Subscriptions Table
-- ============================================
CREATE TABLE IF NOT EXISTS alert_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    station_id INT NOT NULL,
    alert_restock TINYINT(1) NOT NULL DEFAULT 1,
    alert_depleted TINYINT(1) NOT NULL DEFAULT 1,
    email_alert TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_station (user_id, station_id)
) ENGINE=InnoDB;

-- ============================================
-- System Settings Table
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Default Admin User (password: admin123)
-- ============================================
INSERT INTO users (name, email, password, role, is_active)
VALUES ('Administrador', 'admin@fuelsoyo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- ============================================
-- Default Settings
-- ============================================
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Fuel Monitor Soyo'),
('site_email', 'noreply@fuelsoyo.com'),
('email_notifications', '1'),
('auto_alerts', '1');

-- ============================================
-- Sample Stations for Soyo
-- ============================================
INSERT INTO stations (name, address, latitude, longitude, phone, operator_code, status, gasoline_available, diesel_available, gasoline_price, diesel_price, last_updated) VALUES
('Posto Sonangol Soyo Centro', 'Rua Principal, Centro do Soyo', -6.1349, 12.3691, '+244 923 456 789', 'SOYO-001', 'approved', 1, 1, 300.00, 280.00, NOW()),
('Posto Pumangol Soyo Norte', 'Av. da Independência, Soyo Norte', -6.1250, 12.3750, '+244 923 456 790', 'SOYO-002', 'approved', 1, 0, 305.00, NULL, NOW()),
('Posto Total Soyo Sul', 'Rua do Comércio, Soyo Sul', -6.1450, 12.3600, '+244 923 456 791', 'SOYO-003', 'approved', 0, 1, NULL, 275.00, NOW());
