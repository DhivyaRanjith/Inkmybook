<?php
// Define Base URL logic
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    define('BASE_URL', 'http://localhost/inkmybook/');
} else {
    // Check if HTTPS is used
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    define('BASE_URL', $protocol . "://" . $_SERVER['HTTP_HOST'] . "/");
}
?>