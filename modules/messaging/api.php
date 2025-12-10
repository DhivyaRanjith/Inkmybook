<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_conversations':
        getConversations($pdo, $user_id);
        break;
    case 'get_messages':
        getMessages($pdo, $user_id);
        break;
    case 'send_message':
        sendMessage($pdo, $user_id);
        break;
    case 'start_conversation':
        startConversation($pdo, $user_id);
        break;
    case 'mark_read':
        markRead($pdo, $user_id);
        break;
    case 'check_new':
        checkNew($pdo, $user_id);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

function getConversations($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name as other_user_name, 
               u.id as other_user_id,
               (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND is_read = 0 AND sender_id != ?) as unread_count
        FROM conversations c
        JOIN users u ON (c.user_1_id = u.id OR c.user_2_id = u.id) AND u.id != ?
        WHERE c.user_1_id = ? OR c.user_2_id = ?
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format time
    foreach ($conversations as &$conv) {
        $conv['time_ago'] = time_elapsed_string($conv['last_message_time']);
    }

    echo json_encode(['status' => 'success', 'data' => $conversations]);
}

function getMessages($pdo, $user_id)
{
    $conversation_id = $_GET['conversation_id'] ?? 0;

    // Verify access
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND (user_1_id = ? OR user_2_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $messages]);
}

function sendMessage($pdo, $user_id)
{
    $conversation_id = $_POST['conversation_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');

    if (empty($message) && empty($_FILES['attachment']['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
        return;
    }

    // Verify access
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND (user_1_id = ? OR user_2_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        return;
    }

    $attachment = null;
    if (!empty($_FILES['attachment']['name'])) {
        $target_dir = "../../uploads/chat/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $attachment = 'uploads/chat/' . $new_filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, attachment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$conversation_id, $user_id, $message, $attachment]);

    // Update conversation timestamp
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversation_id]);

    echo json_encode(['status' => 'success']);
}

function startConversation($pdo, $user_id)
{
    $other_user_id = $_POST['other_user_id'] ?? 0;
    $entity_type = $_POST['entity_type'] ?? 'direct';
    $entity_id = $_POST['entity_id'] ?? null;

    if (!$other_user_id || $other_user_id == $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
        return;
    }

    // Check if conversation exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE ((user_1_id = ? AND user_2_id = ?) OR (user_1_id = ? AND user_2_id = ?))
        AND related_entity_type = ? AND (related_entity_id = ? OR related_entity_id IS NULL)
        LIMIT 1
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id, $entity_type, $entity_id]);
    $conversation = $stmt->fetch();

    if ($conversation) {
        echo json_encode(['status' => 'success', 'conversation_id' => $conversation['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO conversations (user_1_id, user_2_id, related_entity_type, related_entity_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $other_user_id, $entity_type, $entity_id]);
        echo json_encode(['status' => 'success', 'conversation_id' => $pdo->lastInsertId()]);
    }
}

function markRead($pdo, $user_id)
{
    $conversation_id = $_POST['conversation_id'] ?? 0;
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
    $stmt->execute([$conversation_id, $user_id]);
    echo json_encode(['status' => 'success']);
}

function checkNew($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages m JOIN conversations c ON m.conversation_id = c.id WHERE (c.user_1_id = ? OR c.user_2_id = ?) AND m.sender_id != ? AND m.is_read = 0");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $result = $stmt->fetch();
    echo json_encode(['status' => 'success', 'count' => $result['count']]);
}

function time_elapsed_string($datetime, $full = false)
{
    if (!$datetime)
        return '';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>