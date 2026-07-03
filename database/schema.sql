-- Gestion_Access — Schéma MySQL
-- Importer via phpMyAdmin ou : mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS gestion_access
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestion_access;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_level VARCHAR(30) NOT NULL DEFAULT 'Admin Level 4',
    avatar_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_type ENUM('laptop', 'mobile', 'desktop', 'unknown') NOT NULL,
    hostname VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17) NOT NULL,
    signal_level TINYINT NOT NULL DEFAULT 4,
    status ENUM('authorized', 'inactive', 'blocked', 'guest') NOT NULL DEFAULT 'authorized',
    is_online TINYINT(1) NOT NULL DEFAULT 1,
    data_source ENUM('simulated', 'real') NOT NULL DEFAULT 'real',
    last_seen_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS network_metrics (
    id INT PRIMARY KEY DEFAULT 1,
    network_status ENUM('online', 'degraded', 'offline') NOT NULL DEFAULT 'online',
    active_users INT NOT NULL DEFAULT 0,
    laptops_count INT NOT NULL DEFAULT 0,
    mobile_count INT NOT NULL DEFAULT 0,
    traffic_mbps INT NOT NULL DEFAULT 850,
    traffic_up_mbps INT NOT NULL DEFAULT 212,
    traffic_down_mbps INT NOT NULL DEFAULT 638,
    alert_active TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_time TIME NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    device_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS traffic_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    value_mbps INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS observability_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_name VARCHAR(50) NOT NULL UNIQUE,
    role_label VARCHAR(120) NOT NULL,
    source_status ENUM('online', 'warning', 'offline') NOT NULL DEFAULT 'online',
    endpoint VARCHAR(255) NOT NULL,
    scrape_interval_sec INT NOT NULL DEFAULT 15,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS observability_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_id INT NOT NULL,
    metric_key VARCHAR(80) NOT NULL,
    metric_value VARCHAR(80) NOT NULL,
    metric_unit VARCHAR(30) NOT NULL DEFAULT '',
    severity ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES observability_sources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS phone_actions_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    mac_address VARCHAR(17) NOT NULL,
    action ENUM('block', 'unblock') NOT NULL,
    status ENUM('pending', 'done', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL,
    error_message VARCHAR(255) NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS agent_heartbeats (
    agent_id VARCHAR(50) PRIMARY KEY,
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    clients_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
