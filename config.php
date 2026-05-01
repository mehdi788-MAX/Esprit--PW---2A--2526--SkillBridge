<?php
// Database configuration - SkillBridge
// Essayer MySQL (XAMPP) d'abord, sinon SQLite en fallback local
$useMySQL = true;

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'skillbridge';

try {
    if ($useMySQL) {
        $pdo = new PDO(
            'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
            $dbUser,
            $dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
} catch (PDOException $e) {
    // MySQL non disponible, basculer vers SQLite
    $useMySQL = false;
}

if (!$useMySQL) {
    try {
        $dbPath = __DIR__ . '/database/skillbridge.sqlite';
        if (!is_dir(__DIR__ . '/database')) {
            mkdir(__DIR__ . '/database', 0777, true);
        }
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Activer les clés étrangères pour SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Créer les tables si elles n'existent pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS utilisateurs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role TEXT NOT NULL DEFAULT 'client' CHECK(role IN ('freelancer','client','admin')),
                telephone VARCHAR(20),
                photo VARCHAR(255),
                date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS conversations (
                id_conversation INTEGER PRIMARY KEY AUTOINCREMENT,
                user1_id INTEGER NOT NULL,
                user2_id INTEGER NOT NULL,
                date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user1_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
                FOREIGN KEY (user2_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS messages (
                id_message INTEGER PRIMARY KEY AUTOINCREMENT,
                id_conversation INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                contenu TEXT NOT NULL,
                date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_seen INTEGER DEFAULT 0,
                type VARCHAR(50) DEFAULT 'text',
                FOREIGN KEY (id_conversation) REFERENCES conversations(id_conversation) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS profils (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                utilisateur_id INTEGER NOT NULL,
                bio TEXT,
                competences TEXT,
                localisation VARCHAR(150),
                site_web VARCHAR(255),
                date_naissance DATE,
                FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS demandes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(150) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                deadline DATE NOT NULL,
                description TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                user_id INTEGER,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS propositions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                demande_id INTEGER NOT NULL,
                freelancer_name VARCHAR(100),
                message TEXT,
                price DECIMAL(10,2),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type VARCHAR(50) NOT NULL,
                conversation_id INTEGER,
                message_id INTEGER,
                payload_json TEXT,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE INDEX IF NOT EXISTS idx_notifications_user_unread
                ON notifications(user_id, is_read, id);

            CREATE TABLE IF NOT EXISTS typing_indicators (
                conversation_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (conversation_id, user_id),
                FOREIGN KEY (conversation_id) REFERENCES conversations(id_conversation) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS message_reactions (
                message_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                emoji VARCHAR(20) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (message_id, user_id),
                FOREIGN KEY (message_id) REFERENCES messages(id_message) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );
        ");

        // Insérer les données de test si la table est vide
        $count = $pdo->query("SELECT COUNT(*) as c FROM utilisateurs")->fetch()['c'];
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO utilisateurs (id, nom, prenom, email, password, role, telephone) VALUES
                (1, 'Admin', 'SkillBridge', 'admin@skillbridge.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+216 00 000 000'),
                (2, 'Ben Ali', 'Mohamed', 'freelancer@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', '+216 11 111 111'),
                (3, 'Trabelsi', 'Sarra', 'client@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', '+216 22 222 222');

                INSERT INTO conversations (id_conversation, user1_id, user2_id) VALUES
                (1, 2, 3);

                INSERT INTO messages (id_message, id_conversation, sender_id, contenu, is_seen, type) VALUES
                (1, 1, 2, 'Bonjour, je suis intéressé par votre projet.', 1, 'text'),
                (2, 1, 3, 'Bonjour ! Pouvez-vous me donner plus de détails sur votre expérience ?', 1, 'text'),
                (3, 1, 2, 'Bien sûr, j ai travaillé sur plusieurs projets similaires.', 0, 'text');

                INSERT INTO profils (id, utilisateur_id, bio, competences, localisation) VALUES
                (1, 1, 'Administrateur de la plateforme SkillBridge.', 'Administration, Gestion', 'Tunis'),
                (2, 2, 'Développeur web fullstack avec 3 ans d expérience.', 'PHP, JavaScript, MySQL', 'Sousse'),
                (3, 3, 'Entrepreneur à la recherche de talents.', '', 'Tunis');
            ");
        }
    } catch (PDOException $e) {
        die('Erreur de connexion à la base de données: ' . $e->getMessage());
    }
}
