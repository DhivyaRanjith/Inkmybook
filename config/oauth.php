<?php
// OAuth configuration — set environment variables or edit constants below.

define('OAUTH_BASE_PATH', '/inkmybook');

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '361737453586-oo7ej2krtlir93nlj5lrghgfqvdmhkv8.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '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');

define('FACEBOOK_APP_ID', getenv('FACEBOOK_APP_ID') ?: '875156475191843');
define('FACEBOOK_APP_SECRET', getenv('FACEBOOK_APP_SECRET') ?: '632f0b1fc36bbcadc4ab9c1753791e48');

function oauth_base_url()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . OAUTH_BASE_PATH;
}
