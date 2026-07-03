-- Gestion_Access — Données initiales
-- Exécuter après schema.sql

USE gestion_access;

DELETE FROM phone_actions_queue;
DELETE FROM agent_heartbeats;
DELETE FROM activity_logs;
DELETE FROM traffic_history;
DELETE FROM observability_metrics;
DELETE FROM observability_sources;
DELETE FROM devices;
DELETE FROM network_metrics;
DELETE FROM admins;

-- Admin : jeremie / admin123 (générer via php database/create_admin.php)
INSERT INTO admins (username, password_hash, full_name, role_level, avatar_url) VALUES
('jeremie', '$2y$10$sE5VsD.V8v0H3bzytFq.8uNTxSqs9IhsMh9zqQo8F9/xwTnV7T/KO', 'Jeremie BOKOTA', 'Administrateur Niveau 4', NULL);

-- Métriques réseau initiales (alimentées par l'agent Android / collector)
INSERT INTO network_metrics (id, network_status, active_users, laptops_count, mobile_count, traffic_mbps, traffic_up_mbps, traffic_down_mbps, alert_active) VALUES
(1, 'online', 0, 0, 0, 0, 0, 0, 0);
