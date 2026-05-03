-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 18 avr. 2026 à 12:09
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Base de données : `skillbridge`
--

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
    `id_conversation` int(11) NOT NULL,
    `user1_id` int(11) NOT NULL,
    `user2_id` int(11) NOT NULL,
    `date_creation` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `conversations`
--

INSERT INTO
    `conversations` (
        `id_conversation`,
        `user1_id`,
        `user2_id`,
        `date_creation`
    )
VALUES (
        1,
        2,
        3,
        '2026-04-11 12:26:01'
    );

-- --------------------------------------------------------

--
-- Structure de la table `demandes`
--

CREATE TABLE `demandes` (
    `id` int(11) NOT NULL,
    `title` varchar(150) NOT NULL,
    `price` decimal(10, 2) NOT NULL,
    `deadline` date NOT NULL,
    `description` text NOT NULL,
    `created_at` datetime NOT NULL,
    `user_id` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `demandes`
--

INSERT INTO
    `demandes` (
        `id`,
        `title`,
        `price`,
        `deadline`,
        `description`,
        `created_at`,
        `user_id`
    )
VALUES (
        2,
        'logo',
        12.00,
        '2026-04-16',
        'simple',
        '2026-04-09 16:04:04',
        NULL
    ),
    (
        4,
        'test',
        100.00,
        '2026-01-01',
        'test desc',
        '2026-04-11 13:58:31',
        NULL
    ),
    (
        5,
        'une video ia',
        74.00,
        '2026-05-02',
        'rien',
        '2026-04-11 14:12:09',
        NULL
    ),
    (
        6,
        'affiche',
        47.00,
        '2026-04-29',
        'creatif&mysterieux',
        '2026-04-11 14:29:40',
        NULL
    ),
    (
        7,
        'ouioui',
        21.00,
        '2026-04-30',
        'as soon as possible',
        '2026-04-11 15:02:45',
        NULL
    ),
    (
        8,
        'video',
        45.00,
        '2026-04-29',
        'animation',
        '2026-04-11 16:52:31',
        NULL
    ),
    (
        9,
        'pic',
        12.00,
        '2026-04-21',
        '::::',
        '2026-04-11 17:05:52',
        NULL
    ),
    (
        10,
        'khalil',
        123.00,
        '2026-05-02',
        'hello fom the other side',
        '2026-04-11 18:00:17',
        NULL
    ),
    (
        11,
        'nade',
        549.00,
        '2026-04-30',
        'brabi ekhdm',
        '2026-04-17 17:44:51',
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
    `id_message` int(11) NOT NULL,
    `id_conversation` int(11) NOT NULL,
    `sender_id` int(11) NOT NULL,
    `contenu` text NOT NULL,
    `date_envoi` datetime DEFAULT current_timestamp(),
    `is_seen` tinyint(1) DEFAULT 0,
    `type` varchar(50) DEFAULT 'text'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO
    `messages` (
        `id_message`,
        `id_conversation`,
        `sender_id`,
        `contenu`,
        `date_envoi`,
        `is_seen`,
        `type`
    )
VALUES (
        1,
        1,
        2,
        'Bonjour, je suis intéressé par votre projet.',
        '2026-04-11 12:26:01',
        1,
        'text'
    ),
    (
        2,
        1,
        3,
        'Bonjour ! Pouvez-vous me donner plus de détails sur votre expérience ?',
        '2026-04-11 12:26:01',
        1,
        'text'
    ),
    (
        3,
        1,
        2,
        'Bien sûr, j ai travaillé sur plusieurs projets similaires.',
        '2026-04-11 12:26:01',
        0,
        'text'
    );

-- --------------------------------------------------------

--
-- Structure de la table `profils`
--

CREATE TABLE `profils` (
    `id` int(11) NOT NULL,
    `utilisateur_id` int(11) NOT NULL,
    `bio` text DEFAULT NULL,
    `competences` text DEFAULT NULL,
    `localisation` varchar(150) DEFAULT NULL,
    `site_web` varchar(255) DEFAULT NULL,
    `date_naissance` date DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `profils`
--

INSERT INTO
    `profils` (
        `id`,
        `utilisateur_id`,
        `bio`,
        `competences`,
        `localisation`,
        `site_web`,
        `date_naissance`
    )
VALUES (
        1,
        1,
        'Administrateur de la plateforme SkillBridge.',
        'Administration, Gestion',
        'Tunis',
        NULL,
        NULL
    ),
    (
        2,
        2,
        'Développeur web fullstack avec 3 ans d expérience.',
        'PHP, JavaScript, MySQL',
        'Sousse',
        NULL,
        NULL
    ),
    (
        3,
        3,
        'Entrepreneur à la recherche de talents.',
        '',
        'Tunis',
        NULL,
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `propositions`
--

CREATE TABLE `propositions` (
    `id` int(11) NOT NULL,
    `demande_id` int(11) NOT NULL,
    `freelancer_name` varchar(100) DEFAULT NULL,
    `message` text DEFAULT NULL,
    `price` decimal(10, 2) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `propositions`
--

INSERT INTO
    `propositions` (
        `id`,
        `demande_id`,
        `freelancer_name`,
        `message`,
        `price`,
        `created_at`
    )
VALUES (
        3,
        2,
        'Nour Dev',
        'Disponible immédiatement',
        200.00,
        '2026-04-10 19:16:48'
    );

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
    `id` int(11) NOT NULL,
    `nom` varchar(100) NOT NULL,
    `prenom` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum(
        'freelancer',
        'client',
        'admin'
    ) NOT NULL DEFAULT 'client',
    `telephone` varchar(20) DEFAULT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `date_inscription` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO
    `utilisateurs` (
        `id`,
        `nom`,
        `prenom`,
        `email`,
        `password`,
        `role`,
        `telephone`,
        `photo`,
        `date_inscription`
    )
VALUES (
        1,
        'Admin',
        'SkillBridge',
        'admin@skillbridge.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin',
        '+216 00 000 000',
        NULL,
        '2026-04-11 12:26:01'
    ),
    (
        2,
        'Ben Ali',
        'Mohamed',
        'freelancer@test.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'freelancer',
        '+216 11 111 111',
        NULL,
        '2026-04-11 12:26:01'
    ),
    (
        3,
        'Trabelsi',
        'Sarra',
        'client@test.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'client',
        '+216 22 222 222',
        NULL,
        '2026-04-11 12:26:01'
    );

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
ADD PRIMARY KEY (`id_conversation`),
ADD KEY `user1_id` (`user1_id`),
ADD KEY `user2_id` (`user2_id`);

--
-- Index pour la table `demandes`
--
ALTER TABLE `demandes`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_demandes_user` (`user_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
ADD PRIMARY KEY (`id_message`),
ADD KEY `id_conversation` (`id_conversation`),
ADD KEY `sender_id` (`sender_id`);

--
-- Index pour la table `profils`
--
ALTER TABLE `profils`
ADD PRIMARY KEY (`id`),
ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `propositions`
--
ALTER TABLE `propositions`
ADD PRIMARY KEY (`id`),
ADD KEY `demande_id` (`demande_id`),
ADD KEY `fk_propositions_user` (`user_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
MODIFY `id_conversation` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

--
-- AUTO_INCREMENT pour la table `demandes`
--
ALTER TABLE `demandes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 12;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

--
-- AUTO_INCREMENT pour la table `profils`
--
ALTER TABLE `profils`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

--
-- AUTO_INCREMENT pour la table `propositions`
--
ALTER TABLE `propositions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes`
--
ALTER TABLE `demandes`
ADD CONSTRAINT `fk_demandes_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_conversation`) REFERENCES `conversations` (`id_conversation`) ON DELETE CASCADE,
ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `profils`
--
ALTER TABLE `profils`
ADD CONSTRAINT `profils_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `propositions`
--
ALTER TABLE `propositions`
ADD CONSTRAINT `propositions_ibfk_1` FOREIGN KEY (`demande_id`) REFERENCES `demandes` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_propositions_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;
