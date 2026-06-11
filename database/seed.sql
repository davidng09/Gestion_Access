-- Gestion_Access — Données initiales
-- Exécuter après schema.sql

USE gestion_access;

DELETE FROM activity_logs;
DELETE FROM traffic_history;
DELETE FROM devices;
DELETE FROM network_metrics;
DELETE FROM admins;

-- Admin : admin / admin123
INSERT INTO admins (username, password_hash, full_name, role_level, avatar_url) VALUES
('admin', '$2y$10$sE5VsD.V8v0H3bzytFq.8uNTxSqs9IhsMh9zqQo8F9/xwTnV7T/KO', 'Natali Peresta', 'Administrateur Niveau 4',
 'https://lh3.googleusercontent.com/aida-public/AB6AXuDEYXMs3AlJZRjZk6fGPHeH90ss2quvffDsRqJPDu4ulrzdk1YkjedFe8fE2LH1vMY809Ld1owGYf4cj1LqNkAZ8KJ_HDAVWg6GKu3ZzqnSCPapboI_hP3rkxxxz_8uxhyV3A4noN_sAOX7HVyLr9MJn9QANEAxU0zZIpjhMbb8fhlWV-XnGhyKYF3DDXM4BrJOT6JQUWHQZrFhhFiRx4X6HfSFY46FfNmS4y4dP6IOVbqUzQtFI6zE7eAvFqNaEpSgVht4qtmb2BQ');

-- Appareils simulés (5 devices, 5 online initialement pour metrics cohérentes)
INSERT INTO devices (device_type, hostname, ip_address, mac_address, signal_level, status, is_online) VALUES
('desktop', 'AP_Library', '19.188.10.2', '08:05:A5:85:59:00', 4, 'authorized', 1),
('laptop', 'Laptop_3', '19.188.10.22', '08:08:A8:09:38:00', 4, 'authorized', 1),
('mobile', 'Laptop_Sara', '19.188.10.3', '05:05:AF:A7:77:00', 3, 'guest', 1),
('laptop', 'Laptop_Marc', '19.188.10.15', '0A:1B:2C:3D:4E:5F', 4, 'authorized', 1),
('mobile', 'Phone_Julie', '19.188.10.28', 'AA:BB:CC:DD:EE:FF', 3, 'authorized', 1);

-- Métriques réseau (42 users comme prototype — compteurs affichés incluent simulation)
INSERT INTO network_metrics (id, network_status, active_users, laptops_count, mobile_count, traffic_mbps, traffic_up_mbps, traffic_down_mbps, alert_active) VALUES
(1, 'online', 5, 2, 2, 850, 212, 638, 0);

-- Logs initiaux (alignés prototype)
INSERT INTO activity_logs (event_time, event_type, message, severity) VALUES
('10:49:15', 'Nouvelle connexion', 'Nouvelle connexion sur AP_Library : Laptop_Sara', 'info'),
('10:47:30', 'Accès bloqué', 'Tentative d''accès bloquée : appareil non autorisé', 'error'),
('10:45:00', 'Pic de trafic détecté', 'Pic de transfert interne atteint 950 Mbps', 'info');

-- Historique trafic initial
INSERT INTO traffic_history (value_mbps) VALUES (850), (820), (900), (850);
