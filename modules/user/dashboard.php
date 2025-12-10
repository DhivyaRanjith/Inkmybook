<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// --- Fetch Stats ---

// 1. Active Orders
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders 
    WHERE (buyer_id = ? OR provider_id = ?) 
    AND status IN ('pending', 'in_progress', 'delivered', 'revision_requested')
");
$stmt->execute([$user_id, $user_id]);
$active_orders = $stmt->fetchColumn();

// 2. Unread Messages
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    WHERE m.is_read = 0 
    AND m.sender_id != ? 
    AND (c.user_1_id = ? OR c.user_2_id = ?)
");
$stmt->execute([$user_id, $user_id, $user_id]);
$unread_messages = $stmt->fetchColumn();

// 3. Role Specific Stats
$stat3_label = '';
$stat3_value = 0;
$stat3_icon = '';

if ($user_role === 'provider') {
    // Wallet Balance
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stat3_value = '$' . number_format($stmt->fetchColumn(), 2);
    $stat3_label = 'Wallet Balance';
    $stat3_icon = 'fa-wallet';

    // Rating
    $stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE provider_id = ?");
    $stmt->execute([$user_id]);
    $rating = number_format($stmt->fetchColumn(), 1);

} else {
    // Total Spent
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE user_id = ? AND status = 'success'");
    $stmt->execute([$user_id]);
    $stat3_value = '$' . number_format($stmt->fetchColumn(), 2);
    $stat3_label = 'Total Spent';
    $stat3_icon = 'fa-receipt';
}

// --- Fetch Recent Activity (Orders) ---
$stmt = $pdo->prepare("
    SELECT o.*, s.title as service_title, t.title as task_title, 
    u.name as other_party_name
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    LEFT JOIN tasks t ON o.task_id = t.id
    JOIN users u ON (o.buyer_id = u.id OR o.provider_id = u.id)
    WHERE (o.buyer_id = ? OR o.provider_id = ?) AND u.id != ?
    ORDER BY o.created_at DESC LIMIT 5
");
$stmt->execute([$user_id, $user_id, $user_id]);
$recent_orders = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4 mb-lg-0 animate-slide-up">
            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div class="card-body text-center p-4">
                    <div class="mb-3 position-relative d-inline-block">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=random&size=150"
                            class="rounded-circle img-fluid border border-4 border-white shadow-sm" alt="User Avatar"
                            style="width: 100px; height: 100px;">
                        <span
                            class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2"></span>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                    <p class="text-muted mb-3 text-capitalize small"><i class="fas fa-user-tag me-1"></i>
                        <?php echo htmlspecialchars($user_role); ?></p>
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary btn-sm rounded-pill">Edit Profile</a>
                    </div>
                </div>
            </div>

            <div class="list-group shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="dashboard.php" class="list-group-item list-group-item-action active border-0 py-3 fw-bold">
                    <i class="fas fa-tachometer-alt me-3"></i> Dashboard
                </a>
                <a href="../messaging/inbox.php"
                    class="list-group-item list-group-item-action border-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-envelope me-3 text-muted"></i> Messages</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($user_role === 'provider'): ?>
                    <a href="../services/my_gigs.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="fas fa-briefcase me-3 text-muted"></i> My Gigs
                    </a>
                    <a href="../orders/manage_orders.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="fas fa-tasks me-3 text-muted"></i> Manage Orders
                    </a>
                    <a href="../wallet/index.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="fas fa-wallet me-3 text-muted"></i> My Wallet
                    </a>
                <?php else: ?>
                    <a href="../orders/my_orders.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="fas fa-shopping-bag me-3 text-muted"></i> My Orders
                    </a>
                    <a href="../tasks/my_tasks.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="fas fa-clipboard-list me-3 text-muted"></i> My Tasks
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-slide-up">
                <div>
                    <h2 class="fw-bold mb-0">Welcome back, <?php echo explode(' ', $user_name)[0]; ?>! 👋</h2>
                    <p class="text-muted small mb-0">Here's what's happening with your account today.</p>
                </div>
                <span class="text-muted small bg-white px-3 py-2 rounded-pill shadow-sm border">
                    <i class="far fa-calendar-alt me-2"></i> <?php echo date('l, F j, Y'); ?>
                </span>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-5 animate-slide-up delay-100">
                <div class="col-md-4">
                    <div
                        class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden shadow-hover bg-primary text-white">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-white bg-opacity-25 p-3 rounded-circle text-white">
                                    <i class="fas fa-spinner fa-lg"></i>
                                </div>
                            </div>
                            <h5 class="text-white text-opacity-75 small text-uppercase fw-bold mb-1">
                                Active Orders</h5>
                            <h2 class="display-5 fw-bold mb-0"><?php echo $active_orders; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden shadow-hover">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                    <i class="fas <?php echo $stat3_icon; ?> fa-lg"></i>
                                </div>
                            </div>
                            <h5 class="text-muted small text-uppercase fw-bold mb-1">
                                <?php echo $stat3_label; ?>
                            </h5>
                            <h2 class="display-5 fw-bold mb-0 text-dark"><?php echo $stat3_value; ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden shadow-hover">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                                    <i class="fas fa-envelope fa-lg"></i>
                                </div>
                            </div>
                            <h5 class="text-muted small text-uppercase fw-bold mb-1">Unread Messages
                            </h5>
                            <h2 class="display-5 fw-bold mb-0 text-dark">
                                <?php echo $unread_messages; ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section (Premium Stats) -->
            <div class="row mb-5 animate-slide-up delay-150">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 px-4 border-bottom">
                            <h5 class="fw-bold mb-0">Analytics Overview</h5>
                        </div>
                        <div class="card-body p-4">
                            <canvas id="ordersChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-5 animate-slide-up delay-200">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="row g-3">
                    <?php if ($user_role === 'provider'): ?>
                        <div class="col-md-3">
                            <a href="../services/create.php"
                                class="card border-0 shadow-sm h-100 rounded-4 text-center text-decoration-none hover-lift">
                                <div class="card-body p-4">
                                    <i class="fas fa-plus-circle fa-2x text-primary mb-3"></i>
                                    <h6 class="fw-bold text-dark mb-0">Create Gig</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../wallet/index.php"
                                class="card border-0 shadow-sm h-100 rounded-4 text-center text-decoration-none hover-lift">
                                <div class="card-body p-4">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-3"></i>
                                    <h6 class="fw-bold text-dark mb-0">Withdraw</h6>
                                </div>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3">
                            <a href="../tasks/create.php"
                                class="card border-0 shadow-sm h-100 rounded-4 text-center text-decoration-none hover-lift">
                                <div class="card-body p-4">
                                    <i class="fas fa-plus-circle fa-2x text-primary mb-3"></i>
                                    <h6 class="fw-bold text-dark mb-0">Post a Job</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../services/browse.php"
                                class="card border-0 shadow-sm h-100 rounded-4 text-center text-decoration-none hover-lift">
                                <div class="card-body p-4">
                                    <i class="fas fa-search fa-2x text-info mb-3"></i>
                                    <h6 class="fw-bold text-dark mb-0">Browse Gigs</h6>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <a href="profile.php"
                            class="card border-0 shadow-sm h-100 rounded-4 text-center text-decoration-none hover-lift">
                            <div class="card-body p-4">
                                <i class="fas fa-user-cog fa-2x text-secondary mb-3"></i>
                                <h6 class="fw-bold text-dark mb-0">Edit Profile</h6>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card border-0 shadow-sm rounded-4 animate-slide-up delay-300">
                <div
                    class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Orders</h5>
                    <a href="<?php echo $user_role === 'provider' ? '../orders/manage_orders.php' : '../orders/my_orders.php'; ?>"
                        class="btn btn-sm btn-light rounded-pill fw-bold">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">Order</th>
                                        <th class="py-3">Service/Task</th>
                                        <th class="py-3">
                                            <?php echo $user_role === 'provider' ? 'Buyer' : 'Provider'; ?>
                                        </th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-4 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                            <td>
                                                <?php
                                                echo htmlspecialchars($order['service_title'] ?: $order['task_title'] ?: 'Custom Order');
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['other_party_name']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                if ($order['status'] == 'pending')
                                                    $status_class = 'warning';
                                                if ($order['status'] == 'in_progress')
                                                    $status_class = 'primary';
                                                if ($order['status'] == 'delivered')
                                                    $status_class = 'info';
                                                if ($order['status'] == 'completed')
                                                    $status_class = 'success';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?> rounded-pill text-uppercase"
                                                    style="font-size: 0.7rem;">
                                                    <?php echo str_replace('_', ' ', $order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="../orders/view.php?id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary rounded-pill">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted mb-0">No active orders found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Orders Activity',
                data: [12, 19, 3, 5, 2, 3, 10], // Dummy data for visual
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [5, 5]
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<style>
    .hover-lift {
        transition: transform 0.2s;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
    }
</style>

<?php include '../../includes/footer.php'; ?>