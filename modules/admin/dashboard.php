<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// requireAdmin(); // Implement this function or check role manually
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // redirect('/inkmybook/modules/auth/login.php');
    // For now, let's assume if they are here they might be admin or we need to add admin role to users table
}

// Fetch Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_providers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'provider'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'success'")->fetchColumn();
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();

include '../../includes/header.php';
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4">Admin Dashboard</h2>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-primary text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="opacity-75 mb-0">Total Users</h6>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo number_format($total_users); ?></h2>
                    <small class="opacity-75"><?php echo number_format($total_providers); ?> Providers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-success text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="opacity-75 mb-0">Total Revenue</h6>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                    <h2 class="fw-bold mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                    <small class="opacity-75">Lifetime Earnings</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-info text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="opacity-75 mb-0">Total Orders</h6>
                        <i class="fas fa-shopping-bag fa-2x opacity-50"></i>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo number_format($total_orders); ?></h2>
                    <small class="opacity-75">All Time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-warning text-dark h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="opacity-75 mb-0">Pending Withdrawals</h6>
                        <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo number_format($pending_withdrawals); ?></h2>
                    <a href="withdrawals.php" class="btn btn-sm btn-light rounded-pill mt-2 fw-bold">Review Requests</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Recent Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">ID</th>
                                    <th class="py-3">Service</th>
                                    <th class="py-3">Buyer</th>
                                    <th class="py-3">Amount</th>
                                    <th class="pe-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $orders = $pdo->query("
                                    SELECT o.*, s.title, u.name as buyer_name 
                                    FROM orders o 
                                    JOIN services s ON o.service_id = s.id 
                                    JOIN users u ON o.buyer_id = u.id 
                                    ORDER BY o.created_at DESC LIMIT 5
                                ")->fetchAll();
                                foreach ($orders as $order):
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($order['title'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                        <td class="fw-bold">$<?php echo number_format($order['amount'], 2); ?></td>
                                        <td class="pe-4"><span
                                                class="badge bg-secondary rounded-pill"><?php echo $order['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="users.php" class="list-group-item list-group-item-action py-3"><i
                            class="fas fa-users me-2 text-primary"></i> Manage Users</a>
                    <a href="services.php" class="list-group-item list-group-item-action py-3"><i
                            class="fas fa-briefcase me-2 text-success"></i> Manage Services</a>
                    <a href="categories.php" class="list-group-item list-group-item-action py-3"><i
                            class="fas fa-tags me-2 text-info"></i> Categories</a>
                    <a href="withdrawals.php" class="list-group-item list-group-item-action py-3"><i
                            class="fas fa-money-bill-wave me-2 text-warning"></i> Withdrawals</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>