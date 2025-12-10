<?php
session_start();
require_once '../../config/oauth.php';

$provider = $_GET['provider'] ?? '';

if ($provider === 'google') {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => oauth_base_url() . '/modules/auth/oauth_callback.php',
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'state' => 'google'
    ];
    $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    header("Location: $url");
    exit;
} elseif ($provider === 'facebook') {
    $params = [
        'client_id' => FACEBOOK_APP_ID,
        'redirect_uri' => oauth_base_url() . '/modules/auth/oauth_callback.php',
        'response_type' => 'code',
        'scope' => 'email',
        'state' => 'facebook'
    ];
    $url = 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query($params);
    header("Location: $url");
    exit;
} else {
    die("Invalid provider");
}
