<?php
// config/env.php - Charge les variables d'environnement
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Définir la clé API depuis .env ou fallback
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');
?>
