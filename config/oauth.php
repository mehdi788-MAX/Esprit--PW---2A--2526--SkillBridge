<?php
$envPath = __DIR__ . '/../.env';
$env = [];
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $env[trim($parts[0])] = trim($parts[1], " \t\"'");
        }
    }
}

return [
    'google' => [
        'client_id'     => $env['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $env['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => 'http://localhost/skillbridgeutilisateur/controller/oauthcontroller.php?provider=google',
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url'     => 'https://oauth2.googleapis.com/token',
        'userinfo_url'  => 'https://www.googleapis.com/oauth2/v3/userinfo',
        'scope'         => 'openid email profile',
    ],
    'github' => [
        'client_id'     => $env['GITHUB_CLIENT_ID'] ?? '',
        'client_secret' => $env['GITHUB_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => 'http://localhost/skillbridgeutilisateur/controller/oauthcontroller.php?provider=github',
        'auth_url'      => 'https://github.com/login/oauth/authorize',
        'token_url'     => 'https://github.com/login/oauth/access_token',
        'userinfo_url'  => 'https://api.github.com/user',
        'scope'         => 'user:email',
    ],
    'facebook' => [
        'client_id'     => $env['FACEBOOK_CLIENT_ID'] ?? '',
        'client_secret' => $env['FACEBOOK_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => 'http://localhost/SkillBridge/controller/oauthcontroller.php?provider=facebook',
        'auth_url'      => 'https://www.facebook.com/v18.0/dialog/oauth',
        'token_url'     => 'https://graph.facebook.com/v18.0/oauth/access_token',
        'userinfo_url'  => 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name',
        'scope'         => 'email',
    ],
    'linkedin' => [
        'client_id'     => $env['LINKEDIN_CLIENT_ID'] ?? '',
        'client_secret' => $env['LINKEDIN_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => 'http://localhost/SkillBridge/controller/oauthcontroller.php?provider=linkedin',
        'auth_url'      => 'https://www.linkedin.com/oauth/v2/authorization',
        'token_url'     => 'https://www.linkedin.com/oauth/v2/accessToken',
        'userinfo_url'  => 'https://api.linkedin.com/v2/userinfo',
        'scope'         => 'openid profile email',
    ],
    'discord' => [
        'client_id'     => $env['DISCORD_CLIENT_ID'] ?? '',
        'client_secret' => $env['DISCORD_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => 'http://localhost/skillbridgeutilisateur/controller/oauthcontroller.php?provider=discord',
        'auth_url'      => 'https://discord.com/oauth2/authorize',
        'token_url'     => 'https://discord.com/api/oauth2/token',
        'userinfo_url'  => 'https://discord.com/api/users/@me',
        'scope'         => 'identify email',
    ],
];
