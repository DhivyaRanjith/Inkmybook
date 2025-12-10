<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Simulate PayU Response Handling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    // Update order status to processing (simulating payment success)
    // In a real scenario, we would verify the hash/signature from PayU here
    $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$order_id]);

    flash('success', 'Payment successful! Your order is now being processed.');
    redirect('/inkmybook/modules/orders/my_orders.php');
} else {
    // If accessed directly without POST data
    flash('error', 'Invalid payment response.');
    redirect('/inkmybook/modules/orders/my_orders.php');
}
?>