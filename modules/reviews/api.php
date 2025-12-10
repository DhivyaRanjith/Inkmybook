<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'submit_review':
        submitReview($pdo);
        break;
    case 'get_reviews':
        getReviews($pdo);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

function submitReview($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $reviewer_id = $_SESSION['user_id'];
    $order_id = $_POST['order_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rating']);
        return;
    }

    // Verify order eligibility
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'completed'");
    $stmt->execute([$order_id, $reviewer_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid order or order not completed']);
        return;
    }

    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
    $stmt->execute([$order_id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Order already reviewed']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (order_id, reviewer_id, provider_id, service_id, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $reviewer_id, $order['provider_id'], $order['service_id'], $rating, $comment]);
        echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getReviews($pdo)
{
    $service_id = $_GET['service_id'] ?? 0;
    $provider_id = $_GET['provider_id'] ?? 0;

    $sql = "SELECT r.*, u.name as reviewer_name, p.avatar as reviewer_avatar 
            FROM reviews r 
            JOIN users u ON r.reviewer_id = u.id 
            LEFT JOIN profiles p ON u.id = p.user_id 
            WHERE 1=1";
    $params = [];

    if ($service_id) {
        $sql .= " AND r.service_id = ?";
        $params[] = $service_id;
    } elseif ($provider_id) {
        $sql .= " AND r.provider_id = ?";
        $params[] = $provider_id;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        return;
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average
    $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE " . ($service_id ? "service_id = ?" : "provider_id = ?"));
    $avgStmt->execute([$service_id ?: $provider_id]);
    $stats = $avgStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $reviews,
        'stats' => [
            'average' => round($stats['avg_rating'], 1),
            'total' => $stats['total_reviews']
        ]
    ]);
}
?>