<?php
require_once __DIR__ . '/../../config/db.php';

try {
    // Check if support agent exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'support@inkmybook.com'");
    $stmt->execute();
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent) {
        echo "Support Agent already exists:\n";
        echo "Email: " . $agent['email'] . "\n";
        echo "Password: (Hidden, but try 'support123' if you just created it)\n";
    } else {
        // Create support agent
        $email = 'support@inkmybook.com';
        $password = password_hash('support123', PASSWORD_DEFAULT);
        $name = 'InkMyBook Support';

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'support')");
        $stmt->execute([$name, $email, $password]);

        echo "Support Agent created successfully!\n";
        echo "Email: $email\n";
        echo "Password: support123\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>