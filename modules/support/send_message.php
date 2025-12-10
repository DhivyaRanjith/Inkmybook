<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$message = $_POST['message'] ?? '';
$attachment = $_FILES['attachment'] ?? null;

if (empty($message) && empty($attachment)) {
    echo json_encode(['status' => 'error', 'message' => 'Message or attachment required']);
    exit;
}

try {
    // Check for open ticket
    $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE user_id = ? AND status = 'open'");
    $stmt->execute([$user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        // Create new ticket
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, status) VALUES (?, 'open')");
        $stmt->execute([$user_id]);
        $ticket_id = $pdo->lastInsertId();
    } else {
        $ticket_id = $ticket['id'];
    }

    // Handle Attachment (Simplified for now)
    $attachment_path = null;
    if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/support/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $filename = uniqid() . '_' . basename($attachment['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($attachment['tmp_name'], $target_path)) {
            $attachment_path = 'uploads/support/' . $filename;
        }
    }

    // Insert Message
    $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, message, attachment) VALUES (?, 'user', ?, ?)");
    $stmt->execute([$ticket_id, $message, $attachment_path]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent',
        'attachment' => $attachment_path
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>