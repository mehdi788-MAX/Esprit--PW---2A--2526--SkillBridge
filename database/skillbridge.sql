-- =====================================================
-- SkillBridge — Schéma complet pour XAMPP / phpMyAdmin
-- =====================================================
-- ATTENTION : ce script SUPPRIME les tables existantes.
-- Sauvegardez vos données avant import si nécessaire.
--
-- Import : phpMyAdmin → base "skillbridge" → onglet Importer
--          → choisir ce fichier → Exécuter.
-- (Si la base "skillbridge" n'existe pas, créez-la d'abord :
--  CREATE DATABASE skillbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;)
--
-- Inclus : Gestion Utilisateurs (Mehdi) + Gestion Chat (Oussema)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- DROP (ordre inverse des dépendances)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `message_reactions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `typing_indicators`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `propositions`;
DROP TABLE IF EXISTS `demandes`;
DROP TABLE IF EXISTS `profils`;
DROP TABLE IF EXISTS `utilisateurs`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. UTILISATEURS — auth, profil, OAuth, vérif. email,
--    reconnaissance faciale (Mehdi)
-- =====================================================
CREATE TABLE `utilisateurs` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `nom`                VARCHAR(100)  NOT NULL,
    `prenom`             VARCHAR(100)  NOT NULL,
    `email`              VARCHAR(150)  NOT NULL UNIQUE,
    `password`           VARCHAR(255)  NOT NULL,
    `role`               ENUM('freelancer','client','admin') NOT NULL DEFAULT 'client',
    `telephone`          VARCHAR(20)   NULL,
    `photo`              VARCHAR(255)  NULL,
    `is_active`          TINYINT(1)    NOT NULL DEFAULT 1,
    `is_verified`        TINYINT(1)    NOT NULL DEFAULT 1,
    `verification_token` VARCHAR(64)   NULL,
    `reset_token`        VARCHAR(64)   NULL,
    `reset_token_expiry` DATETIME      NULL,
    `oauth_provider`     VARCHAR(20)   NULL,
    `oauth_id`           VARCHAR(100)  NULL,
    `face_descriptor`    TEXT          NULL,
    `date_inscription`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email`         (`email`),
    INDEX `idx_oauth`         (`oauth_provider`, `oauth_id`),
    INDEX `idx_reset_token`   (`reset_token`),
    INDEX `idx_verify_token`  (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PROFILS — bio, compétences, localisation
-- =====================================================
CREATE TABLE `profils` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `utilisateur_id`  INT           NOT NULL,
    `bio`             TEXT          NULL,
    `competences`     TEXT          NULL,
    `localisation`    VARCHAR(150)  NULL,
    `site_web`        VARCHAR(255)  NULL,
    `date_naissance`  DATE          NULL,
    INDEX `idx_user`  (`utilisateur_id`),
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. DEMANDES — projets postés par les clients
-- =====================================================
CREATE TABLE `demandes` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `title`        VARCHAR(150)   NOT NULL,
    `price`        DECIMAL(10,2)  NOT NULL,
    `deadline`     DATE           NOT NULL,
    `description`  TEXT           NOT NULL,
    `created_at`   DATETIME       NOT NULL,
    `user_id`      INT            NULL,
    INDEX `idx_user_id` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. PROPOSITIONS — réponses des freelancers
-- =====================================================
CREATE TABLE `propositions` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `demande_id`       INT            NOT NULL,
    `user_id`          INT            NULL,
    `freelancer_name`  VARCHAR(100)   NULL,
    `message`          TEXT           NULL,
    `price`            DECIMAL(10,2)  NULL,
    `created_at`       DATETIME       DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_demande`            (`demande_id`),
    INDEX `idx_propositions_user`  (`user_id`),
    FOREIGN KEY (`demande_id`) REFERENCES `demandes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_propositions_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CONVERSATIONS — fil entre 2 utilisateurs (Oussema)
-- =====================================================
CREATE TABLE `conversations` (
    `id_conversation`  INT AUTO_INCREMENT PRIMARY KEY,
    `user1_id`         INT       NOT NULL,
    `user2_id`         INT       NOT NULL,
    `date_creation`    DATETIME  DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user1`        (`user1_id`),
    INDEX `idx_user2`        (`user2_id`),
    INDEX `idx_pair`         (`user1_id`, `user2_id`),
    FOREIGN KEY (`user1_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user2_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. MESSAGES — texte / image / fichier (type)
-- =====================================================
CREATE TABLE `messages` (
    `id_message`       INT AUTO_INCREMENT PRIMARY KEY,
    `id_conversation`  INT          NOT NULL,
    `sender_id`        INT          NOT NULL,
    `contenu`          TEXT         NOT NULL,
    `date_envoi`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `is_seen`          TINYINT(1)   DEFAULT 0,
    `type`             VARCHAR(50)  DEFAULT 'text',
    INDEX `idx_conv_id`         (`id_conversation`, `id_message`),
    INDEX `idx_sender`          (`sender_id`),
    INDEX `idx_unseen`          (`id_conversation`, `is_seen`),
    FOREIGN KEY (`id_conversation`) REFERENCES `conversations`(`id_conversation`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`)       REFERENCES `utilisateurs`(`id`)               ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. NOTIFICATIONS — cloche / toast (Oussema)
-- =====================================================
CREATE TABLE `notifications` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT          NOT NULL,
    `type`             VARCHAR(50)  NOT NULL,
    `conversation_id`  INT          NULL,
    `message_id`       INT          NULL,
    `payload_json`     TEXT         NULL,
    `is_read`          TINYINT(1)   DEFAULT 0,
    `created_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_unread`  (`user_id`, `is_read`, `id`),
    INDEX `idx_conv`         (`conversation_id`),
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. TYPING INDICATORS — saisie en cours (Oussema)
-- =====================================================
CREATE TABLE `typing_indicators` (
    `conversation_id`  INT       NOT NULL,
    `user_id`          INT       NOT NULL,
    `updated_at`       DATETIME  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`conversation_id`, `user_id`),
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id_conversation`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)         REFERENCES `utilisateurs`(`id`)               ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. MESSAGE REACTIONS — emojis sous chaque bulle
-- =====================================================
CREATE TABLE `message_reactions` (
    `message_id`  INT          NOT NULL,
    `user_id`     INT          NOT NULL,
    `emoji`       VARCHAR(20)  NOT NULL,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`message_id`, `user_id`),
    INDEX `idx_msg` (`message_id`),
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id_message`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `utilisateurs`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DONNÉES DE TEST
-- =====================================================
-- Mot de passe pour les 3 comptes : "password"
-- (hash bcrypt $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
-- =====================================================

INSERT INTO `utilisateurs`
    (`id`, `nom`, `prenom`, `email`, `password`, `role`, `telephone`, `is_active`, `is_verified`)
VALUES
    (1, 'Admin',     'SkillBridge', 'admin@skillbridge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      '+216 00 000 000', 1, 1),
    (2, 'Ben Ali',   'Mohamed',     'freelancer@test.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', '+216 11 111 111', 1, 1),
    (3, 'Trabelsi',  'Sarra',       'client@test.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client',     '+216 22 222 222', 1, 1);

INSERT INTO `profils` (`id`, `utilisateur_id`, `bio`, `competences`, `localisation`) VALUES
    (1, 1, 'Administrateur de la plateforme SkillBridge.',     'Administration, Gestion',  'Tunis'),
    (2, 2, 'Développeur web fullstack avec 3 ans d expérience.', 'PHP, JavaScript, MySQL',   'Sousse'),
    (3, 3, 'Entrepreneur à la recherche de talents.',           '',                         'Tunis');

INSERT INTO `conversations` (`id_conversation`, `user1_id`, `user2_id`) VALUES
    (1, 2, 3);

INSERT INTO `messages` (`id_message`, `id_conversation`, `sender_id`, `contenu`, `is_seen`, `type`) VALUES
    (1, 1, 2, 'Bonjour, je suis intéressé par votre projet.',                          1, 'text'),
    (2, 1, 3, 'Bonjour ! Pouvez-vous me donner plus de détails sur votre expérience ?', 1, 'text'),
    (3, 1, 2, 'Bien sûr, j ai travaillé sur plusieurs projets similaires.',             0, 'text');

-- =====================================================
-- FIN DU SCRIPT
-- Connectez-vous ensuite avec :
--   admin@skillbridge.com / password
--   freelancer@test.com   / password
--   client@test.com       / password
-- =====================================================
