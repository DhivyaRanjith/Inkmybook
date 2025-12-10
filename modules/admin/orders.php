<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// requireAdmin();

// Fetch Orders
$orders = $pdo->query("
    SELECT o.*, 
    buyer.name as buyer_name, 
    provider.name as provider_name,
    s.title as service_title
    FROM orders o 
    JOIN users buyer ON o.buyer_id = buyer.id 
    JOIN users provider ON o.provider_id = provider.id 
    LEFT JOIN services s ON o.service_id = s.id
    ORDER BY o.created_at DESC
")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Order Management</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left me-2"></i>
            Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">ID</th>
                            <th class="py-3">Service</th>
                            <th class="py-3">Buyer</th>
                            <th class="py-3">Provider</th>
                            <th class="py-3">Amount</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars(substr($order['service_title'] ?? 'Custom Order', 0, 30)) . '...'; ?>
                                    </div>
                                    <small
                                        class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['provider_name']); ?></td>
                                <td class="fw-bold">$<?php echo number_format($order['amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'secondary';
                                    if ($order['status'] == 'completed')
                                        $status_class = 'success';
                                    if ($order['status'] == 'in_progress')
                                        $status_class = 'primary';
                                    if ($order['status'] == 'cancelled')
                                        $status_class = 'danger';
                                    ?>
                                    <span
                                        class="badge bg-<?php echo $status_class; ?> rounded-pill text-uppercase"><?php echo str_replace('_', ' ', $order['status']); ?></span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="../orders/view.php?id=<?php echo $order['id']; ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill" target="_blank">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>