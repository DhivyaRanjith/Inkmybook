<?php
require_once '../../config/db.php';

try {
    $sql = file_get_contents('schema.sql');
    $pdo->exec($sql);
    echo "Reviews table created successfully.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>