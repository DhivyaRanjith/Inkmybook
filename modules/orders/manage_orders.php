<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'provider') {
    redirect('/inkmybook/index.php');
}

// Fetch Provider's Orders
$stmt = $pdo->prepare("
    SELECT o.*, s.title as service_title, t.title as task_title, u.name as buyer_name
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    LEFT JOIN tasks t ON o.task_id = t.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.provider_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate Stats
$total_orders = count($orders);
$pending_orders = 0;
$completed_orders = 0;
$earnings = 0;

foreach ($orders as $order) {
    if ($order['status'] == 'pending')
        $pending_orders++;
    if ($order['status'] == 'completed') {
        $completed_orders++;
        $earnings += $order['amount'];
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-5 animate-slide-up">
        <div>
            <h2 class="fw-bold mb-1">Manage Orders</h2>
            <p class="text-muted mb-0">Track and manage your service orders.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                <i class="fas fa-calendar-alt me-2 text-primary"></i> <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5 animate-slide-up delay-100">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Total Earnings</p>
                            <h3 class="fw-bold mb-0 text-primary">$<?php echo number_format($earnings, 2); ?></h3>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-primary">
                            <i class="fas fa-dollar-sign fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Active Orders</p>
                            <h3 class="fw-bold mb-0 text-info"><?php echo $pending_orders; ?></h3>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-info">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Completed</p>
                            <h3 class="fw-bold mb-0 text-success"><?php echo $completed_orders; ?></h3>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-success">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div class="text-center py-5 animate-slide-up delay-200">
            <div class="empty-state-icon mb-4">
                <i class="fas fa-clipboard-list text-muted opacity-25" style="font-size: 5rem;"></i>
            </div>
            <h4 class="fw-bold text-muted">No orders yet</h4>
            <p class="text-muted mb-4">When you receive an order, it will appear here.</p>
            <a href="../services/create.php" class="btn btn-primary rounded-pill px-4">Create a Gig</a>
        </div>
    <?php else: ?>
        <div class="card table-card animate-slide-up delay-200">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3">Order ID</th>
                            <th class="py-3">Buyer</th>
                            <th class="py-3">Service / Task</th>
                            <th class="py-3">Amount</th>
                            <th class="py-3">Due Date</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#<?php echo $order['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-ring rounded-circle me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold"
                                            style="width: 35px; height: 35px;">
                                            <?php echo strtoupper(substr($order['buyer_name'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-medium"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 200px;">
                                        <?php
                                        if ($order['service_id'])
                                            echo htmlspecialchars($order['service_title']);
                                        elseif ($order['task_id'])
                                            echo htmlspecialchars($order['task_title']);
                                        else
                                            echo "Custom Order";
                                        ?>
                                    </span>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($order['amount'], 2); ?></td>
                                <td>
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="far fa-calendar me-2"></i>
                                        <?php echo date('M d', strtotime($order['delivery_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'secondary';
                                    $icon = 'circle';
                                    if ($order['status'] == 'pending') {
                                        $status_class = 'warning';
                                        $icon = 'clock';
                                    }
                                    if ($order['status'] == 'in_progress') {
                                        $status_class = 'info';
                                        $icon = 'spinner fa-spin';
                                    }
                                    if ($order['status'] == 'delivered') {
                                        $status_class = 'primary';
                                        $icon = 'box';
                                    }
                                    if ($order['status'] == 'completed') {
                                        $status_class = 'success';
                                        $icon = 'check-circle';
                                    }
                                    if ($order['status'] == 'cancelled') {
                                        $status_class = 'danger';
                                        $icon = 'times-circle';
                                    }
                                    ?>
                                    <span class="badge badge-soft-<?php echo $status_class; ?> rounded-pill">
                                        <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                        <?php echo str_replace('_', ' ', $order['status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="view.php?id=<?php echo $order['id']; ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>