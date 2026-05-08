<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/utilisateur.php';
require_once __DIR__ . '/../model/profil.php';

$providers = require __DIR__ . '/../config/oauth.php';
$provider  = $_GET['provider'] ?? '';

if (!array_key_exists($provider, $providers)) {
    header('Location: ../view/frontoffice/EasyFolio/login.php');
    exit;
}

$cfg = $providers[$provider];

// ÉTAPE 1 : Rediriger vers Google
if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $params = http_build_query([
        'client_id'     => $cfg['client_id'],
        'redirect_uri'  => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope'         => $cfg['scope'],
        'state'         => $state,
        'access_type'   => 'online',
    ]);

    header('Location: ' . $cfg['auth_url'] . '?' . $params);
    exit;
}

// ÉTAPE 2 : Callback
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    $_SESSION['error'] = "Erreur de sécurité OAuth.";
    header('Location: ../view/frontoffice/EasyFolio/login.php');
    exit;
}
unset($_SESSION['oauth_state']);

// Échanger code → token
$tokenData = oauthPost($cfg['token_url'], [
    'client_id'     => $cfg['client_id'],
    'client_secret' => $cfg['client_secret'],
    'code'          => $_GET['code'],
    'redirect_uri'  => $cfg['redirect_uri'],
    'grant_type'    => 'authorization_code',
], $provider);

$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    $_SESSION['error'] = "Impossible d'obtenir le token OAuth.";
    header('Location: ../view/frontoffice/EasyFolio/login.php');
    exit;
}

// Récupérer profil
$userInfo = oauthGet($cfg['userinfo_url'], $accessToken, $provider);

$email  = normalizeEmail($userInfo, $provider, $accessToken);
$prenom = normalizePrenom($userInfo, $provider);
$nom    = normalizeNom($userInfo, $provider);

if (!$email) {
    $_SESSION['error'] = "Email non fourni par " . ucfirst($provider) . ".";
    header('Location: ../view/frontoffice/EasyFolio/login.php');
    exit;
}

// ÉTAPE 3 : Créer ou connecter
$utilisateur        = new Utilisateur($pdo);
$profil             = new Profil($pdo);
$utilisateur->email = $email;

$existing = $utilisateur->readByEmail();

if ($existing) {
    if (!$existing['is_active']) {
        $_SESSION['error'] = "Votre compte est désactivé.";
        header('Location: ../view/frontoffice/EasyFolio/login.php');
        exit;
    }
    $_SESSION['user_id']   = $existing['id'];
    $_SESSION['user_nom']  = $existing['prenom'] . ' ' . $existing['nom'];
    $_SESSION['user_role'] = $existing['role'];
} else {
    $utilisateur->nom       = $nom ?: 'Utilisateur';
    $utilisateur->prenom    = $prenom ?: ucfirst($provider);
    $utilisateur->email     = $email;
    $utilisateur->password  = bin2hex(random_bytes(32));
    $utilisateur->role      = 'client';
    $utilisateur->telephone = '';

    if ($utilisateur->create()) {
        $profil->utilisateur_id = $utilisateur->id;
        $profil->bio            = '';
        $profil->competences    = '';
        $profil->localisation   = '';
        $profil->site_web       = '';
        $profil->create();

        $_SESSION['user_id']   = $utilisateur->id;
        $_SESSION['user_nom']  = $utilisateur->prenom . ' ' . $utilisateur->nom;
        $_SESSION['user_role'] = 'client';
    } else {
        $_SESSION['error'] = "Erreur lors de la création du compte.";
        header('Location: ../view/frontoffice/EasyFolio/login.php');
        exit;
    }
}

header('Location: ../view/frontoffice/EasyFolio/profil.php');
exit;

// =====================
// FONCTIONS
// =====================

function oauthPost($url, $params, $provider) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: SkillBridge/1.0',
        ],
        CURLOPT_SSL_VERIFYPEER => false, // pour localhost
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function oauthGet($url, $token, $provider) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'User-Agent: SkillBridge/1.0',
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false, // pour localhost
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function normalizeEmail($userInfo, $provider, $token) {
    if ($provider === 'github' && empty($userInfo['email'])) {
        $emails = oauthGet('https://api.github.com/user/emails', $token, 'github');
        foreach ($emails as $e) {
            if ($e['primary'] && $e['verified']) return $e['email'];
        }
        return null;
    }
    return $userInfo['email'] ?? null;
}

function normalizePrenom($userInfo, $provider) {
    if ($provider === 'github') {
        // Si l'utilisateur a un display name, on l'utilise.
        // Sinon (souvent null), on retombe sur le 'login' (username GitHub).
        $name = $userInfo['name'] ?? '';
        if ($name !== '') {
            return explode(' ', $name)[0] ?? '';
        }
        return $userInfo['login'] ?? '';
    }
    return match($provider) {
        'google'   => $userInfo['given_name']  ?? '',
        'facebook' => $userInfo['first_name']  ?? '',
        'linkedin' => $userInfo['given_name']  ?? '',
        'discord'  => $userInfo['global_name'] ?? ($userInfo['username'] ?? ''),
        default    => '',
    };
}

function normalizeNom($userInfo, $provider) {
    if ($provider === 'github') {
        // Last name uniquement si display name multi-mots, sinon vide
        $name = $userInfo['name'] ?? '';
        if ($name !== '' && str_contains($name, ' ')) {
            return explode(' ', $name, 2)[1] ?? '';
        }
        return ''; // pas de nom de famille fiable côté GitHub
    }
    return match($provider) {
        'google'   => $userInfo['family_name'] ?? '',
        'facebook' => $userInfo['last_name']   ?? '',
        'linkedin' => $userInfo['family_name'] ?? '',
        'discord'  => '',
        default    => '',
    };
}