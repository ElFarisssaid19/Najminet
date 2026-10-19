<?php

session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/SubscriptionManager.php';


const GOOGLE_AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_URL    = 'https://oauth2.googleapis.com/token';
const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

function http_json_request(string $url, ?array $postFields = null): ?array
{
   
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ];
    if ($postFields !== null) {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($postFields);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $error) {
        return null;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

if (!isset($_GET['code'])) {

    // CSRF protection — Google will send this back unchanged
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
   

    $params = http_build_query([
        'client_id'     => $_ENV["GOOGLE_CLIENT_ID"],
        'redirect_uri'  => $_ENV["GOOGLE_REDIRECT_URI"],
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);

    header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
    exit;
}


// Verify the state first to prevent CSRF.

if (
    !isset($_GET['state']) ||
    !isset($_SESSION['oauth_state']) ||
    !hash_equals($_SESSION['oauth_state'], $_GET['state'])
) {
    unset($_SESSION['oauth_state']);
    header('Location: /auth/login.php?error=invalid_state');
    exit;
}
unset($_SESSION['oauth_state']);

// User denied access on Google's consent screen
if (isset($_GET['error'])) {
    header('Location: /auth/login.php?error=oauth_failed');
    exit;
}


//  Exchange the authorization code for an access token

$tokenResponse = http_json_request(GOOGLE_TOKEN_URL, [
    'code'          => $_GET['code'],
    'client_id'     => $_ENV["GOOGLE_CLIENT_ID"],
    'client_secret' => $_ENV["GOOGLE_CLIENT_SECRET"],
    'redirect_uri'  => $_ENV["GOOGLE_REDIRECT_URI"],
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenResponse['access_token'])) {
    header('Location: /auth/login.php?error=oauth_failed');
    exit;
}

//  Fetch the users Google profile (email, name, picture)

$profile = http_json_request(
    GOOGLE_USERINFO_URL . '?access_token=' . urlencode($tokenResponse['access_token'])
);

if (empty($profile['email'])) {
    header('Location: /auth/login.php?error=no_email');
    exit;
}

// Only an address Google has verified may sign in to, or create, an account;
// otherwise anyone could claim an email they do not own.
if (!AuthSecurity::googleEmailVerified($profile)) {
    header('Location: /auth/login.php?error=email_unverified');
    exit;
}

$email    = $profile['email'];
$fullName = $profile['name'] ?? explode('@', $email)[0];
$avatar   = $profile['picture'] ?? null;


// STEP 5 — Find or create the local account, then start the session

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // New account created via Google 

    $randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, has_password, oauth_provider, avatar_url)
         VALUES (?, ?, ?, 0, 'google', ?)"
    );
    $stmt->execute([$fullName, $email, $randomPasswordHash, $avatar]);
    $userId = (int) $pdo->lastInsertId();
} else {
    $userId = $user['id'];

    // Keep the avatar / provider fresh on returning Google sign-ins
    $update = $pdo->prepare("UPDATE users SET oauth_provider = 'google', avatar_url = ? WHERE id = ?");
    $update->execute([$avatar, $userId]);
}

AuthSecurity::establishSession($pdo, [
    'id' => $userId,
    'full_name' => $fullName,
    'email' => $email,
]);

header('Location: /index.php');
exit;
