<?php
// Database configuration shared by the project.
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'skillbridge';

function db_error_message(PDOException $e): string
{
    $message = $e->getMessage();
    if ((int) $e->getCode() === 2002 || stripos($message, 'SQLSTATE[HY000] [2002]') !== false) {
        return "Connexion MySQL impossible. Démarre MySQL dans XAMPP puis recharge la page.";
    }

    return 'Erreur de connexion à la base de données : ' . $message;
}

function db_connect(): PDO
{
    global $dbHost, $dbPort, $dbUser, $dbPassword, $dbName;

    return new PDO(
        'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

try {
    $pdo = db_connect();
} catch (PDOException $e) {
    die(db_error_message($e));
}

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $projectRoot = realpath(__DIR__);
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot !== false && $documentRoot !== false) {
        $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if (stripos($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
            $relativePath = trim(substr($normalizedProjectRoot, strlen($normalizedDocumentRoot)), '/');
            $basePath = $relativePath === '' ? '' : '/' . $relativePath;

            return $basePath;
        }
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    foreach (['/views/front-office/', '/views/back-office/'] as $marker) {
        $markerPosition = strpos($scriptName, $marker);
        if ($markerPosition !== false) {
            $basePath = rtrim(substr($scriptName, 0, $markerPosition), '/');

            return $basePath;
        }
    }

    $basePath = '';

    return $basePath;
}

function app_url(string $path = ''): string
{
    $basePath = app_base_path();
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . $trimmedPath;
}

function front_url(string $path = ''): string
{
    return app_url('views/front-office/' . ltrim($path, '/'));
}

function back_url(string $path = ''): string
{
    return app_url('views/back-office/' . ltrim($path, '/'));
}

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user_id(): ?int
{
    ensure_session_started();

    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}

function current_user_role(): ?string
{
    ensure_session_started();

    $role = $_SESSION['user_role'] ?? null;
    if (!is_string($role) || $role === '') {
        return null;
    }

    return $role;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function is_client(): bool
{
    return current_user_role() === 'client';
}

function is_freelancer(): bool
{
    return current_user_role() === 'freelancer';
}

function is_admin(): bool
{
    return current_user_role() === 'admin';
}

function front_demands_label(): string
{
    return is_client() ? 'Mes demandes' : 'Demandes';
}

function login_url_with_redirect(string $target): string
{
    return front_url('login.php?redirect=' . rawurlencode($target));
}

function redirect_to_user_home(): void
{
    if (is_admin()) {
        header('Location: ' . back_url('index.php'));
        exit;
    }

    header('Location: ' . front_url('index.php'));
    exit;
}

function require_login(?string $target = null): void
{
    if (is_logged_in()) {
        return;
    }

    $redirectTarget = $target ?? ($_SERVER['REQUEST_URI'] ?? front_url('index.php'));
    header('Location: ' . login_url_with_redirect($redirectTarget));
    exit;
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        header('Location: ' . front_url('index.php'));
        exit;
    }
}

function require_client(): void
{
    require_login();

    if (!is_client()) {
        header('Location: ' . front_url('mes-demandes.php'));
        exit;
    }
}

function require_freelancer(): void
{
    require_login();

    if (!is_freelancer()) {
        header('Location: ' . front_url('mes-demandes.php'));
        exit;
    }
}
