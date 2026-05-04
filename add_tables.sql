-- --------------------------------------------------------
-- Ajouter à la base de données : skillbridge
-- Tables : categories et tests
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `created_at` DATETIME     DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données de base pour les catégories
INSERT INTO `categories` (`name`) VALUES
('Développement'),
('Design'),
('Data'),
('Marketing'),
('Management');

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tests` (
  `id`            INT(11)                              NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(150)                         NOT NULL,
  `category_id`   INT(11)                              NOT NULL,
  `duration`      INT(11)                              NOT NULL,
  `level`         ENUM('Débutant','Moyen','Avancé')    NOT NULL DEFAULT 'Débutant',
  `average_score` DECIMAL(5,2)                         DEFAULT 0.00,
  `created_at`    DATETIME                             DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données de base pour les tests
INSERT INTO `tests` (`title`, `category_id`, `duration`, `level`, `average_score`) VALUES
('Développement Web Frontend', 1, 60, 'Débutant', 82.00),
('UI/UX Design & Prototypage',  2, 60, 'Débutant', 78.00),
('Data Science & Analytics',    3, 60, 'Moyen',    74.00),
('Marketing Digital & SEO',     4, 60, 'Débutant', 85.00),
('Développement Backend Node.js',1, 60, 'Avancé',  79.00),
('Gestion de Projet Agile',     5, 60, 'Moyen',    88.00);

-- --------------------------------------------------------
-- NOUVELLES TABLES POUR L'INTÉGRATION IA (QUESTIONS ET RÉSULTATS)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `test_id` INT(11) NOT NULL,
  `question` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'qcm',
  `option_a` TEXT, 
  `option_b` TEXT, 
  `option_c` TEXT, 
  `option_d` TEXT,
  `bonne_reponse` VARCHAR(5) NOT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `resultats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `test_id` INT(11) NOT NULL,
  `score` INT(11) NOT NULL,
  `total` INT(11) NOT NULL,
  `details` TEXT,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
