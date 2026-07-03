-- Migration : agent Android hotspot + données réelles
-- Exécuter sur une base existante : mysql -u root gestion_access < database/migration_phone_agent.sql

USE gestion_access;

ALTER TABLE devices
    ADD COLUMN data_source ENUM('simulated', 'real') NOT NULL DEFAULT 'real' AFTER is_online;

ALTER TABLE devices
    ADD COLUMN last_seen_at TIMESTAMP NULL AFTER data_source;

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
