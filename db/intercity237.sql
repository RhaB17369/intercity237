-- ============================================================
-- Intercity237 — Plateforme de Réservation de Bus Interurbain
-- Cameroun | SEN3244 Software Architecture — Spring 2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS `intercity237`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `intercity237`;

-- -----------------------------------------------------------
-- Villes desservies
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cities` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `region`     VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `cities` (`name`, `region`) VALUES
('Douala',     'Littoral'),
('Yaoundé',    'Centre'),
('Bafoussam',  'Ouest'),
('Bamenda',    'Nord-Ouest'),
('Garoua',     'Nord'),
('Maroua',     'Extrême-Nord'),
('Ngaoundéré', 'Adamaoua'),
('Bertoua',    'Est'),
('Ebolowa',    'Sud'),
('Limbe',      'Sud-Ouest');

-- -----------------------------------------------------------
-- Opérateurs de transport
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `operators` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(150) NOT NULL,
    `logo_url`   VARCHAR(255),
    `phone`      VARCHAR(30),
    `email`      VARCHAR(150),
    `active`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `operators` (`name`, `phone`, `email`) VALUES
('Général Express',    '+237 233 411 000', 'contact@generalexpress.cm'),
('Buca Voyages',       '+237 233 412 000', 'info@bucavoyages.cm'),
('Vatican Express',    '+237 233 413 000', 'contact@vaticanexpress.cm'),
('Touristique Express','+237 233 414 000', 'info@touristique.cm');

-- -----------------------------------------------------------
-- Bus (flotte)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buses` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `operator_id` INT UNSIGNED NOT NULL,
    `plate`       VARCHAR(20) NOT NULL UNIQUE,
    `model`       VARCHAR(100),
    `capacity`    INT NOT NULL DEFAULT 70,
    `amenities`   JSON,
    `active`      TINYINT(1) DEFAULT 1,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`operator_id`) REFERENCES `operators`(`id`)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Lignes (routes)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `routes` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `origin_id`     INT UNSIGNED NOT NULL,
    `destination_id`INT UNSIGNED NOT NULL,
    `distance_km`   INT,
    `duration_min`  INT,
    `base_price`    DECIMAL(10,2) NOT NULL,
    `active`        TINYINT(1) DEFAULT 1,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`origin_id`)      REFERENCES `cities`(`id`),
    FOREIGN KEY (`destination_id`) REFERENCES `cities`(`id`)
) ENGINE=InnoDB;

INSERT INTO `routes` (`origin_id`, `destination_id`, `distance_km`, `duration_min`, `base_price`) VALUES
(1, 2, 250, 210, 3500),   -- Douala → Yaoundé
(2, 1, 250, 210, 3500),   -- Yaoundé → Douala
(1, 3, 300, 270, 4000),   -- Douala → Bafoussam
(2, 3, 180, 180, 3000),   -- Yaoundé → Bafoussam
(1, 4, 380, 330, 5000),   -- Douala → Bamenda
(2, 7, 600, 480, 8000);   -- Yaoundé → Ngaoundéré

-- -----------------------------------------------------------
-- Horaires (schedules)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `route_id`      INT UNSIGNED NOT NULL,
    `bus_id`        INT UNSIGNED NOT NULL,
    `departure_at`  DATETIME NOT NULL,
    `arrival_at`    DATETIME NOT NULL,
    `seats_total`   INT NOT NULL DEFAULT 70,
    `seats_booked`  INT NOT NULL DEFAULT 0,
    `status`        ENUM('scheduled','boarding','departed','arrived','cancelled') DEFAULT 'scheduled',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`),
    FOREIGN KEY (`bus_id`)   REFERENCES `buses`(`id`)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Utilisateurs (voyageurs, agents, admins)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name`    VARCHAR(200) NOT NULL,
    `email`        VARCHAR(150) NOT NULL UNIQUE,
    `phone`        VARCHAR(30),
    `username`     VARCHAR(100) NOT NULL UNIQUE,
    `password`     VARCHAR(255) NOT NULL,
    `role`         ENUM('superadmin','admin','agent','passenger') NOT NULL DEFAULT 'passenger',
    `operator_id`  INT UNSIGNED DEFAULT NULL,
    `reset_token`  VARCHAR(255),
    `reset_expiry` DATETIME,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`operator_id`) REFERENCES `operators`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Réservations
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reference`    VARCHAR(20) NOT NULL UNIQUE,
    `user_id`      INT UNSIGNED NOT NULL,
    `schedule_id`  INT UNSIGNED NOT NULL,
    `seat_number`  VARCHAR(5),
    `passenger_name` VARCHAR(200) NOT NULL,
    `passenger_phone`VARCHAR(30),
    `amount`       DECIMAL(10,2) NOT NULL,
    `status`       ENUM('pending','confirmed','cancelled','used') DEFAULT 'pending',
    `payment_method` ENUM('cash','mobile_money','card') DEFAULT 'cash',
    `paid_at`      DATETIME,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`),
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Tickets (QR codes)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tickets` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL UNIQUE,
    `qr_token`   VARCHAR(64) NOT NULL UNIQUE,
    `qr_data`    TEXT,
    `scanned_at` DATETIME DEFAULT NULL,
    `scanned_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`),
    FOREIGN KEY (`scanned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Données de démonstration
-- -----------------------------------------------------------

-- Buses
INSERT INTO `buses` (`operator_id`, `plate`, `model`, `capacity`) VALUES
(1, 'LT-2024-A1', 'King Long XMQ6127', 70),
(1, 'LT-2024-A2', 'King Long XMQ6127', 70),
(2, 'LT-2024-B1', 'Yutong ZK6122', 65),
(2, 'LT-2024-B2', 'Yutong ZK6122', 65),
(3, 'LT-2024-C1', 'Higer KLQ6119', 60),
(4, 'LT-2024-D1', 'Daewoo BH090', 55);

-- Horaires (14 départs sur les 3 prochains jours)
INSERT INTO `schedules` (`route_id`, `bus_id`, `departure_at`, `arrival_at`, `seats_total`, `seats_booked`, `status`) VALUES
(1, 1, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 06:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 09:30:00'), 70, 0, 'scheduled'),
(1, 2, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 10:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 13:30:00'), 70, 5, 'scheduled'),
(1, 1, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 06:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 09:30:00'), 70, 0, 'scheduled'),
(1, 2, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), '%Y-%m-%d 07:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), '%Y-%m-%d 10:30:00'), 70, 12, 'scheduled'),
(2, 3, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 07:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 10:30:00'), 65, 3, 'scheduled'),
(2, 3, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 08:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 11:30:00'), 65, 0, 'scheduled'),
(3, 4, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 05:30:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 10:00:00'), 65, 8, 'scheduled'),
(3, 5, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 06:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 10:30:00'), 60, 0, 'scheduled'),
(4, 4, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 08:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 11:00:00'), 65, 0, 'scheduled'),
(4, 5, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 09:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 12:00:00'), 60, 15, 'scheduled'),
(5, 6, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 05:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 10:30:00'), 55, 0, 'scheduled'),
(5, 6, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), '%Y-%m-%d 05:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), '%Y-%m-%d 10:30:00'), 55, 2, 'scheduled'),
(6, 2, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 20:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 04:00:00'), 70, 0, 'scheduled'),
(6, 2, DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d 20:00:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), '%Y-%m-%d 04:00:00'), 70, 6, 'scheduled');

-- Superadmin (login: superadmin / Admin@1234)
INSERT IGNORE INTO `users` (`full_name`, `email`, `username`, `password`, `role`, `phone`) VALUES
('Super Administrateur', 'superadmin@intercity237.cm', 'superadmin', '$2y$10$vkkFUoWW0OE485tYTIaM7OYZ0xdqMhGpNDE.CA4a8yrJGcDRZFGNS', 'superadmin', '+237 650 000 001');
