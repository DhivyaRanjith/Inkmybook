<?php
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // Localhost
    $host = 'localhost';
    $db_name = 'inkmybook';
    $username = 'root';
    $password = '';
} else {
    // Live Server (Hostinger)
    $host = 'localhost';
    $db_name = 'u541004126_inkmybook'; // Update these if known, otherwise placeholder
    $username = 'u541004126_root';
    $password = 'YourStrongPassword123!';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
