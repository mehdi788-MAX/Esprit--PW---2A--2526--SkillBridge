<?php

require_once __DIR__ . '/load-env.php';
load_env(__DIR__ . '/.env');

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'skillbridge';

if (!defined('OLLAMA_API_URL')) {
    define('OLLAMA_API_URL', getenv('OLLAMA_API_URL') ?: 'http://127.0.0.1:11434/api/chat');
}

if (!defined('OLLAMA_MODEL')) {
    define('OLLAMA_MODEL', getenv('OLLAMA_MODEL') ?: 'qwen3:0.6b');
}

/**
 * Ollama /api/chat. Pass JSON {"system":"...","user":"..."} or a plain user string.
 * Qwen3 thinking models must receive think=false (top-level) or the assistant content stays empty.
 */
function callOllama(string $prompt, int $timeoutSeconds = 30, int $maxTokens = 300, bool $enableThinking = false): string
{
    $prompt = trim($prompt);
    if ($prompt === '') {
        return '';
    }

    $decoded = json_decode($prompt, true);
    if (is_array($decoded) && isset($decoded['system'], $decoded['user'])) {
        $messages = [
            ['role' => 'system', 'content' => (string) $decoded['system']],
            ['role' => 'user', 'content' => (string) $decoded['user']],
        ];
    } else {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];
    }

    $cap = $enableThinking ? min($maxTokens, 512) : min($maxTokens, 300);
    $ctx = $enableThinking ? 2048 : 1024;
    $payload = json_encode([
        'model' => OLLAMA_MODEL,
        'messages' => $messages,
        'stream' => false,
        'think' => $enableThinking,
        'options' => [
            'temperature' => 0.3,
            'num_predict' => $cap,
            'num_ctx' => $ctx,
        ],
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return '';
    }

    $url = str_replace('localhost', '127.0.0.1', OLLAMA_API_URL);
    $url = preg_replace('#/api/generate$#', '/api/chat', $url);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);
        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeoutSeconds,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        $statusCode = 200;
    }

    if (!is_string($response) || trim($response) === '') {
        return '';
    }
    if ($statusCode !== 0 && ($statusCode < 200 || $statusCode >= 300)) {
        return '';
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return '';
    }

    $msg = $data['message'] ?? [];
    $text = $msg['content'] ?? null;
    if (is_string($text) && trim($text) !== '') {
        return trim($text);
    }

    $think = $msg['thinking'] ?? null;
    if (is_string($think) && trim($think) !== '') {
        $thinkTrim = trim($think);
        if (preg_match('/\[[\s\S]*"id"[\s\S]*\]/u', $thinkTrim, $m)) {
            return trim($m[0]);
        }

        return $thinkTrim;
    }

    $text = $data['response'] ?? null;
    if (is_string($text) && trim($text) !== '') {
        return trim($text);
    }

    return '';
}

function db_error_message(PDOException $e): string
{
    $message = $e->getMessage();
    if ((int) $e->getCode() === 2002 || stripos($message, 'SQLSTATE[HY000] [2002]') !== false) {
        return 'Connexion MySQL impossible. Demarre MySQL dans XAMPP puis recharge la page.';
    }

    return 'Erreur de connexion a la base de donnees : ' . $message;
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

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rootFs = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);
    $docFs = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $path = '';
    if ($docFs !== '' && str_starts_with($rootFs, $docFs)) {
        $path = substr($rootFs, strlen($docFs));
    }
    $basePath = rtrim($scheme . '://' . $host . $path, '/');

    return $basePath;
}

function front_url(string $file): string
{
    return app_base_path() . '/views/front-office/' . ltrim($file, '/');
}

function back_url(string $file): string
{
    return app_base_path() . '/views/back-office/' . ltrim($file, '/');
}

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function is_admin(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function is_client(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'client';
}

function is_freelancer(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'freelancer';
}

function require_login(?string $target = null): void
{
    ensure_session_started();
    if (empty($_SESSION['user_id'])) {
        $suffix = $target !== null && $target !== '' ? '?redirect=' . rawurlencode($target) : '';
        header('Location: ' . front_url('login.php') . $suffix);
        exit;
    }
}

function require_client(): void
{
    require_login();
    if (!is_client()) {
        header('Location: ' . front_url('index.php'));
        exit;
    }
}

function require_freelancer(): void
{
    require_login();
    if (!is_freelancer()) {
        header('Location: ' . front_url('index.php'));
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        header('Location: ' . front_url('index.php'));
        exit;
    }
}

function redirect_to_user_home(): void
{
    if (is_admin()) {
        header('Location: ' . back_url('index.php'));
    } elseif (is_freelancer()) {
        header('Location: ' . front_url('mes-demandes.php'));
    } else {
        header('Location: ' . front_url('index.php'));
    }
    exit;
}

function front_demands_label(): string
{
    return is_client() ? 'Mes demandes' : 'Demandes disponibles';
}

function ensure_propositions_user_id_column(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = \'propositions\' AND COLUMN_NAME = \'user_id\''
    );
    $stmt->execute([':db' => $dbName]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE propositions ADD COLUMN user_id INT NULL');
    }
    $done = true;
}

function current_user_display_names(PDO $pdo): array
{
    $uid = current_user_id();
    if ($uid < 1) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT nom, prenom, email FROM utilisateurs WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [];
    }
    $p = trim((string) ($row['prenom'] ?? ''));
    $n = trim((string) ($row['nom'] ?? ''));
    $em = trim((string) ($row['email'] ?? ''));

    return array_values(array_unique(array_filter([
        trim($p . ' ' . $n),
        trim($n . ' ' . $p),
        $p !== '' ? $p : null,
        $n !== '' ? $n : null,
        $em !== '' ? $em : null,
    ], static fn ($v) => $v !== null && $v !== '')));
}

function proposition_ownership_sql(string $alias, array $displayNames, array &$params): string
{
    $uid = current_user_id();
    $suffix = '_' . bin2hex(random_bytes(3));
    $pUid = ':own_uid' . $suffix;
    $params[$pUid] = $uid;

    $byUser = "({$alias}.user_id = {$pUid})";
    if ($displayNames === []) {
        return '(' . $byUser . ')';
    }

    $keys = [];
    foreach (array_values($displayNames) as $i => $name) {
        $k = ':own_fn' . $i . $suffix;
        $keys[] = $k;
        $params[$k] = $name;
    }

    $legacy = "({$alias}.user_id IS NULL AND {$alias}.freelancer_name IN (" . implode(',', $keys) . '))';

    return '(' . $byUser . ' OR ' . $legacy . ')';
}
