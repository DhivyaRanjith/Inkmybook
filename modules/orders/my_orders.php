<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

// Fetch Seeker's Orders
$stmt = $pdo->prepare("
    SELECT o.*, s.title as service_title, t.title as task_title, u.name as provider_name
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    LEFT JOIN tasks t ON o.task_id = t.id
    JOIN users u ON o.provider_id = u.id
    WHERE o.buyer_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4">My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">You haven't purchased any services yet.</div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Order ID</th>
                                <th class="py-3">Provider</th>
                                <th class="py-3">Service / Task</th>
                                <th class="py-3">Amount</th>
                                <th class="py-3">Delivery Date</th>
                                <th class="py-3">Status</th>
                                <th class="pe-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://placehold.co/30" class="rounded-circle me-2" alt="">
                                            <?php echo htmlspecialchars($order['provider_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($order['service_id']) echo htmlspecialchars($order['service_title']);
                                            elseif ($order['task_id']) echo htmlspecialchars($order['task_title']);
                                            else echo "Custom Order";
                                        ?>
                                    </td>
                                    <td class="fw-bold">$<?php echo number_format($order['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></td>
                                    <td>
                                        <?php 
                                            $status_class = 'secondary';
                                            if ($order['status'] == 'pending') $status_class = 'warning';
                                            if ($order['status'] == 'in_progress') $status_class = 'primary';
                                            if ($order['status'] == 'delivered') $status_class = 'info';
                                            if ($order['status'] == 'completed') $status_class = 'success';
                                            if ($order['status'] == 'cancelled') $status_class = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?> text-uppercase"><?php echo str_replace('_', ' ', $order['status']); ?></span>
                                    </td>
                                    <td class="pe-4">
                                        <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>