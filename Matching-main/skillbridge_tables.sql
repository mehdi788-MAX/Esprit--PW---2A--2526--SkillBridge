-- SkillBridge : tables attendues par le front-office et le back-office.
-- Exécuter dans phpMyAdmin sur le serveur MySQL/MariaDB (base skillbridge).

CREATE DATABASE IF NOT EXISTS skillbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skillbridge;

CREATE TABLE IF NOT EXISTS demandes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    deadline DATE NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS propositions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    demande_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    freelancer_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_propositions_demande (demande_id),
    CONSTRAINT fk_propositions_demande FOREIGN KEY (demande_id) REFERENCES demandes (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
