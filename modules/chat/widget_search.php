<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Query too short']);
    exit;
}

try {
    // Search Services (Gigs)
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.price, s.image 
        FROM services s
        WHERE s.title LIKE ? OR s.description LIKE ?
        LIMIT 5
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'results' => $results]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>