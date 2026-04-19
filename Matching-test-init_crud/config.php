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
