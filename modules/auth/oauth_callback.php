<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/oauth.php';
require_once '../../includes/functions.php';

$provider = $_GET['state'] ?? ''; // Provider is passed in state
$code = $_GET['code'] ?? '';

if (!$code) {
    die("Error: No code returned");
}

$userInfo = [];

if ($provider === 'google') {
    // Exchange code for token
    $tokenParams = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => oauth_base_url() . '/modules/auth/oauth_callback.php',
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $response = curl_exec($ch);
    $tokenData = json_decode($response, true);
    curl_close($ch);

    if (isset($tokenData['access_token'])) {
        // Get User Info
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
        $userResponse = curl_exec($ch);
        $googleUser = json_decode($userResponse, true);
        curl_close($ch);

        $userInfo = [
            'name' => $googleUser['name'],
            'email' => $googleUser['email'],
            'username' => explode('@', $googleUser['email'])[0] . rand(100, 999), // Generate temp username
            'provider' => 'google'
        ];
    } else {
        die("Error fetching Google token: " . json_encode($tokenData));
    }

} elseif ($provider === 'facebook') {
    // Exchange code for token
    $tokenUrl = "https://graph.facebook.com/v12.0/oauth/access_token?" . http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'redirect_uri' => oauth_base_url() . '/modules/auth/oauth_callback.php',
        'client_secret' => FACEBOOK_APP_SECRET,
        'code' => $code
    ]);

    $response = file_get_contents($tokenUrl);
    $tokenData = json_decode($response, true);

    if (isset($tokenData['access_token'])) {
        // Get User Info
        $userUrl = "https://graph.facebook.com/me?fields=name,email&access_token=" . $tokenData['access_token'];
        $userResponse = file_get_contents($userUrl);
        $fbUser = json_decode($userResponse, true);

        $userInfo = [
            'name' => $fbUser['name'],
            'email' => $fbUser['email'] ?? $fbUser['id'] . '@facebook.com', // Fallback if email not provided
            'username' => str_replace(' ', '', strtolower($fbUser['name'])) . rand(100, 999),
            'provider' => 'facebook'
        ];
    } else {
        die("Error fetching Facebook token");
    }
}

if (!empty($userInfo)) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$userInfo['email']]);
    $user = $stmt->fetch();

    if ($user) {
        // Login existing user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        redirect('/inkmybook/index.php');
    } else {
        // Register new user (Default to 'seeker' or ask user - here defaulting to seeker for simplicity)
        // In a real app, you might redirect to a "finish registration" page to pick a role.
        $role = 'seeker';
        $password = password_hash(uniqid(), PASSWORD_DEFAULT); // Random password

        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$userInfo['name'], $userInfo['username'], $userInfo['email'], $password, $role])) {
            $newUserId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $userInfo['name'];
            $_SESSION['user_role'] = $role;
            redirect('/inkmybook/index.php');
        } else {
            die("Registration failed");
        }
    }
} else {
    die("Authentication failed");
}
