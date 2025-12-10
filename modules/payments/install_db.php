<?php
require_once '../../config/db.php';

try {
    // 1. Create Tables
    $sql = file_get_contents('schema.sql');

    // Split by semicolon to execute statements individually (basic parsing)
    // However, schema.sql has ALTER TABLE which might fail if columns exist.
    // Let's execute the CREATE TABLEs first.

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'INR',
            txn_id VARCHAR(255),
            status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            type ENUM('credit', 'debit') NOT NULL,
            description TEXT,
            reference_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS withdrawals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            payment_details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");
    echo "Tables created successfully.<br>";

    // 2. Update Users Table (Check if columns exist first)
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('wallet_balance', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10, 2) DEFAULT 0.00");
        echo "Added wallet_balance to users.<br>";
    }

    if (!in_array('is_blocked', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
        echo "Added is_blocked to users.<br>";
    }

    echo "Database installation complete.";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>