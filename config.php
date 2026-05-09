<?php
// =====================================================
// URL helper — détecte automatiquement la racine du projet
// (ex. http://localhost/skillbridgeutilisateur OU
//      http://localhost/MonProjet OU
//      http://localhost:8000)
// → permet de déployer le projet dans n'importe quel
//   sous-dossier d'XAMPP sans modifier le code.
// =====================================================
if (!function_exists('base_url')) {
    function base_url() {
        static $cached = null;
        if ($cached !== null) return $cached;
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = $_SERVER['SCRIPT_NAME'] ?? '';
        // Tronque tout ce qui suit /controller/, /view/, /api/ pour
        // remonter à la racine logique du projet.
        $base     = preg_replace('#/(controller|view|api|public)(/.*)?$#i', '', $script);
        if ($base === $script) $base = '';
        return $cached = $protocol . '://' . $host . $base;
    }
}

/**
 * Préfixe d'URL pour le frontoffice (zone publique : index, login, register, profil, chat).
 * Usage : frontoffice_url() . '/login.php'  →  http://localhost/<projet>/view/frontoffice/EasyFolio/login.php
 * Pour les pages chat (sous /view/frontoffice/chat/) utiliser frontoffice_url('chat') . '/conversations.php'.
 */
if (!function_exists('frontoffice_url')) {
    function frontoffice_url($section = 'EasyFolio') {
        $section = trim((string)$section, '/');
        $tail = $section !== '' ? '/' . $section : '';
        return base_url() . '/view/frontoffice' . $tail;
    }
}

/**
 * Préfixe d'URL pour le backoffice (zone admin : dashbord, users_list, edit_user, chat admin).
 * Usage : backoffice_url() . '/dashbord.php'  →  http://localhost/<projet>/view/backoffice/dashbord.php
 * Pour les pages chat admin (sous /view/backoffice/chat/) utiliser backoffice_url('chat') . '/conversations.php'.
 */
if (!function_exists('backoffice_url')) {
    function backoffice_url($section = '') {
        $section = trim((string)$section, '/');
        $tail = $section !== '' ? '/' . $section : '';
        return base_url() . '/view/backoffice' . $tail;
    }
}

/**
 * Préfixe d'URL pour les contrôleurs (POST forms, OAuth callbacks, etc.).
 * Usage : controller_url() . '/utilisateurcontroller.php'
 */
if (!function_exists('controller_url')) {
    function controller_url() {
        return base_url() . '/controller';
    }
}

/**
 * Préfixe d'URL pour l'API JSON (chat poll, uploads, etc.).
 * Usage : api_url() . '/chat.php?action=poll'
 */
if (!function_exists('api_url')) {
    function api_url() {
        return base_url() . '/api';
    }
}

/**
 * Préfixe d'URL pour les uploads (photos de profil, fichiers chat).
 * Usage : uploads_url() . '/chat/<file>'
 */
if (!function_exists('uploads_url')) {
    function uploads_url() {
        return base_url() . '/uploads';
    }
}

/**
 * URL "courte" de la page d'accueil — la racine du projet déclenche
 * la redirection /index.php → frontoffice/EasyFolio/index.php.
 * Usage : header('Location: ' . home_url())
 */
if (!function_exists('home_url')) {
    function home_url() {
        return base_url() . '/';
    }
}

/**
 * URL "courte" de l'espace administrateur — pointe vers /admin/index.php
 * qui redirige vers backoffice/login.php (ou dashbord.php si déjà connecté).
 * Usage : <a href="<?= admin_url() ?>">Admin</a>
 */
if (!function_exists('admin_url')) {
    function admin_url() {
        return base_url() . '/admin/';
    }
}

/**
 * Aperçu lisible d'un message de chat.
 * Les pièces jointes sont stockées en base sous forme d'enveloppe JSON
 * ({"kind":"file"|"image","url":"...","name":"...","mime":"..."}). On évite
 * que ces JSON bruts apparaissent dans les listes "derniers échanges",
 * la sidebar conversations ou les notifications — on les remplace par
 * une libellé court avec icône texte.
 */
if (!function_exists('chat_message_preview')) {
    function chat_message_preview($raw, $maxLen = 90) {
        $raw = trim((string)$raw);
        if ($raw === '') return '';
        if ($raw !== '' && ($raw[0] === '{' || $raw[0] === '[')) {
            $env = json_decode($raw, true);
            if (is_array($env) && isset($env['kind'])) {
                if ($env['kind'] === 'image') {
                    return '📷 Photo';
                }
                if ($env['kind'] === 'file') {
                    $name = trim((string)($env['name'] ?? 'Fichier'));
                    if ($name === '') $name = 'Fichier';
                    if (mb_strlen($name, 'UTF-8') > 40) {
                        $name = mb_substr($name, 0, 37, 'UTF-8') . '…';
                    }
                    return '📎 ' . $name;
                }
            }
        }
        $clean = strip_tags($raw);
        if (mb_strlen($clean, 'UTF-8') > $maxLen) {
            $clean = mb_substr($clean, 0, $maxLen, 'UTF-8') . '…';
        }
        return $clean;
    }
}

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
        $pdo->exec('PRAGMA foreign_keys = ON');

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
                date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_active INTEGER DEFAULT 1,
                is_verified INTEGER DEFAULT 1,
                verification_token VARCHAR(64),
                reset_token VARCHAR(64),
                reset_token_expiry DATETIME,
                oauth_provider VARCHAR(20),
                oauth_id VARCHAR(100),
                face_descriptor TEXT
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
                user_id INTEGER,
                freelancer_name VARCHAR(100),
                message TEXT,
                price DECIMAL(10,2),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
            );

            -- Tables temps réel du module Chat (Gestion Chat)
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

        $count = $pdo->query("SELECT COUNT(*) as c FROM utilisateurs")->fetch()['c'];
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO utilisateurs (id, nom, prenom, email, password, role, telephone) VALUES
                (1, 'Admin', 'SkillBridge', 'admin@skillbridge.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+216 00 000 000'),
                (2, 'Ben Ali', 'Mohamed', 'freelancer@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', '+216 11 111 111'),
                (3, 'Trabelsi', 'Sarra', 'client@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', '+216 22 222 222');

                INSERT INTO profils (id, utilisateur_id, bio, competences, localisation) VALUES
                (1, 1, 'Administrateur de la plateforme SkillBridge.', 'Administration, Gestion', 'Tunis'),
                (2, 2, 'Développeur web fullstack avec 3 ans d expérience.', 'PHP, JavaScript, MySQL', 'Sousse'),
                (3, 3, 'Entrepreneur à la recherche de talents.', '', 'Tunis');

                INSERT INTO conversations (id_conversation, user1_id, user2_id) VALUES
                (1, 2, 3);

                INSERT INTO messages (id_message, id_conversation, sender_id, contenu, is_seen, type) VALUES
                (1, 1, 2, 'Bonjour, je suis intéressé par votre projet.', 1, 'text'),
                (2, 1, 3, 'Bonjour ! Pouvez-vous me donner plus de détails sur votre expérience ?', 1, 'text'),
                (3, 1, 2, 'Bien sûr, j ai travaillé sur plusieurs projets similaires.', 0, 'text');
            ");
        }
    } catch (PDOException $e) {
        die('Erreur de connexion à la base de données: ' . $e->getMessage());
    }
}
