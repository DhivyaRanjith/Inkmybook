<?php
require_once '../../config/db.php';

try {
    $pdo->exec("DROP TABLE IF EXISTS reviews");
    $sql = file_get_contents('schema.sql');
    $pdo->exec($sql);
    echo "Reviews table dropped and recreated successfully.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>