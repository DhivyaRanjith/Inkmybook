<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    redirect('/inkmybook/modules/user/dashboard.php');
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch Order Details
$stmt = $pdo->prepare("
    SELECT o.*, s.title as service_title, s.price as service_price, 
    t.title as task_title, t.budget as task_budget,
    u_buyer.name as buyer_name, u_provider.name as provider_name
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    LEFT JOIN tasks t ON o.task_id = t.id
    JOIN users u_buyer ON o.buyer_id = u_buyer.id
    JOIN users u_provider ON o.provider_id = u_provider.id
    WHERE o.id = ? AND (o.buyer_id = ? OR o.provider_id = ?)
");
$stmt->execute([$order_id, $user_id, $user_id]);
$order = $stmt->fetch();

// Check if review exists
$review_stmt = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ?");
$review_stmt->execute([$order_id]);
$existing_review = $review_stmt->fetch();

if (!$order) {
    flash('error', 'Order not found or access denied.', 'danger');
    redirect('/inkmybook/modules/user/dashboard.php');
}

// Determine Order Price
$order_price = $order['service_price'] ?? $order['task_budget'] ?? 0;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action === 'accept_order' && $user_role === 'provider' && $order['status'] === 'pending') {
        $update = $pdo->prepare("UPDATE orders SET status = 'in_progress' WHERE id = ?");
        $update->execute([$order_id]);
        flash('success', 'Order accepted! Work started.');
    }

    if ($action === 'deliver_work' && $user_role === 'provider' && ($order['status'] === 'in_progress' || $order['status'] === 'revision_requested')) {
        $message = sanitizeInput($_POST['message']);

        // File Upload
        $file_path = '';
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $filename = uniqid() . '_' . $_FILES['file']['name'];
            $upload_dir = '../../uploads/deliveries/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename);
            $file_path = 'uploads/deliveries/' . $filename;
        }

        if ($file_path) {
            $stmt = $pdo->prepare("INSERT INTO deliveries (order_id, file, message) VALUES (?, ?, ?)");
            $stmt->execute([$order_id, $file_path, $message]);

            $update = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
            $update->execute([$order_id]);
            flash('success', 'Work delivered successfully!');
        } else {
            flash('error', 'Please upload a file.', 'danger');
        }
    }

    if ($action === 'request_revision' && $user_role === 'seeker' && $order['status'] === 'delivered') {
        $update = $pdo->prepare("UPDATE orders SET status = 'revision_requested' WHERE id = ?");
        $update->execute([$order_id]);
        flash('success', 'Revision requested.');
    }

    if ($action === 'cancel_order' && ($order['status'] === 'in_progress' || $order['status'] === 'pending')) {
        $pdo->beginTransaction();
        try {
            // 1. Update Order Status
            $update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $update->execute([$order_id]);

            // 2. Refund Buyer (Credit Wallet)
            if ($order_price > 0) {
                $wallet_update = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $wallet_update->execute([$order_price, $order['buyer_id']]);

                // 3. Log Transaction
                $txn = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, reference_id) VALUES (?, ?, 'credit', ?, ?)");
                $txn->execute([$order['buyer_id'], $order_price, "Refund for Order #$order_id", $order_id]);
            }

            $pdo->commit();
            flash('success', 'Order cancelled and amount refunded to buyer wallet.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Error cancelling order: ' . $e->getMessage(), 'danger');
        }
        redirect("view.php?id=$order_id");
    }

    if ($action === 'complete_order' && $user_role === 'seeker' && $order['status'] === 'delivered') {
        $pdo->beginTransaction();
        try {
            // 1. Update Order Status
            $update = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $update->execute([$order_id]);

            // 2. Credit Provider Wallet
            // Calculate Commission (e.g., 10%)
            $commission = $order_price * 0.10;
            $earnings = $order_price - $commission;

            if ($earnings > 0) {
                $wallet_update = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $wallet_update->execute([$earnings, $order['provider_id']]);

                // 3. Log Transaction
                $txn = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, reference_id) VALUES (?, ?, 'credit', ?, ?)");
                $txn->execute([$order['provider_id'], $earnings, "Earnings for Order #$order_id", $order_id]);
            }

            $pdo->commit();
            flash('success', 'Order completed! Provider has been paid.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Error completing order: ' . $e->getMessage(), 'danger');
        }
    }

    redirect("view.php?id=$order_id");
}

// Fetch Deliveries
$del_stmt = $pdo->prepare("SELECT * FROM deliveries WHERE order_id = ? ORDER BY created_at DESC");
$del_stmt->execute([$order_id]);
$deliveries = $del_stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Left Column: Order Details -->
        <div class="col-lg-8 animate-slide-up">
            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div
                    class="card-header bg-white d-flex justify-content-between align-items-center py-4 px-4 border-bottom">
                    <div>
                        <h4 class="mb-1 fw-bold">Order #<?php echo $order['id']; ?></h4>
                        <span class="text-muted small">Placed on
                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <?php
                    $status_class = 'secondary';
                    $status_icon = 'fa-circle';
                    if ($order['status'] == 'pending') {
                        $status_class = 'warning';
                        $status_icon = 'fa-clock';
                    }
                    if ($order['status'] == 'in_progress') {
                        $status_class = 'primary';
                        $status_icon = 'fa-spinner fa-spin';
                    }
                    if ($order['status'] == 'delivered') {
                        $status_class = 'info';
                        $status_icon = 'fa-box-open';
                    }
                    if ($order['status'] == 'completed') {
                        $status_class = 'success';
                        $status_icon = 'fa-check-circle';
                    }
                    if ($order['status'] == 'revision_requested') {
                        $status_class = 'danger';
                        $status_icon = 'fa-exclamation-circle';
                    }
                    if ($order['status'] == 'cancelled') {
                        $status_class = 'danger';
                        $status_icon = 'fa-times-circle';
                    }
                    ?>
                    <span class="badge bg-<?php echo $status_class; ?> fs-6 text-uppercase rounded-pill px-3 py-2">
                        <i class="fas <?php echo $status_icon; ?> me-2"></i>
                        <?php echo str_replace('_', ' ', $order['status']); ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-4">
                        <?php
                        if ($order['service_id'])
                            echo htmlspecialchars($order['service_title']);
                        elseif ($order['task_id'])
                            echo htmlspecialchars($order['task_title']);
                        else
                            echo "Custom Order";
                        ?>
                    </h5>

                    <div class="row g-4 mb-5">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 bg-light rounded-3">
                            <div class="rounded-circle bg-white p-2 me-3 shadow-sm text-danger">
                                <i class="far fa-calendar-alt"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Due
                                    Date</small>
                                <span
                                    class="fw-bold fs-5"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($order['notes'])): ?>
                    <div class="mb-5">
                        <h6 class="fw-bold mb-3"><i class="fas fa-sticky-note me-2 text-muted"></i>Requirements / Notes
                        </h6>
                        <div class="p-4 bg-light rounded-4 text-muted lh-lg border border-light">
                            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Activity & Deliveries</h5>
                    <span class="badge bg-light text-dark ms-3 rounded-pill"><?php echo count($deliveries); ?>
                        Updates</span>
                </div>

                <?php if (empty($deliveries)): ?>
                    <div class="text-center py-5 bg-light rounded-4 border border-dashed">
                        <i class="fas fa-box-open text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted mb-0">No deliveries yet. Work is in progress.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($deliveries as $delivery): ?>
                            <div class="card shadow-sm border-0 mb-4 rounded-4 animate-slide-up">
                                <div
                                    class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success text-white p-2 me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="fas fa-check small"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0">Delivery Submitted</h6>
                                    </div>
                                    <small
                                        class="text-muted"><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></small>
                                </div>
                                <div class="card-body p-4">
                                    <p class="mb-4 text-muted"><?php echo nl2br(htmlspecialchars($delivery['message'])); ?>
                                    </p>
                                    <a href="/inkmybook/<?php echo htmlspecialchars($delivery['file']); ?>"
                                        class="btn btn-outline-primary rounded-pill px-4 fw-bold" target="_blank">
                                        <i class="fas fa-download me-2"></i>Download Work
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Actions -->
    <div class="col-lg-4 animate-slide-up delay-100">
        <div class="card shadow-lg border-0 sticky-top rounded-4 overflow-hidden" style="top: 100px;">
            <div class="card-header bg-dark text-white fw-bold py-3 px-4">
                <i class="fas fa-bolt me-2 text-warning"></i> Actions
            </div>
            <div class="card-body p-4">
                <?php flash('success'); ?>
                <?php flash('error'); ?>

                <?php if ($user_role === 'provider'): ?>
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="text-center mb-4">
                            <i class="fas fa-handshake text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold">New Order Request</h5>
                            <p class="text-muted small">Accept this order to start working.</p>
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="accept_order">
                            <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-hover">Accept
                                Order</button>
                        </form>
                        <form action="" method="POST" class="mt-2">
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-outline-danger w-100 py-3 rounded-pill fw-bold" onclick="return confirm('Are you sure you want to decline this order?');">Decline Order</button>
                        </form>
                    <?php elseif ($order['status'] === 'in_progress' || $order['status'] === 'revision_requested'): ?>
                        <h6 class="fw-bold mb-3">Deliver Your Work</h6>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="deliver_work">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Upload File</label>
                                <input type="file" class="form-control" name="file" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">Message</label>
                                <textarea class="form-control" name="message" rows="4" placeholder="Describe your delivery..."
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-hover">Deliver
                                Work</button>
                        </form>
                        <form action="" method="POST" class="mt-3">
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-outline-danger w-100 py-2 rounded-pill fw-bold" onclick="return confirm('Are you sure you want to cancel this order? This will refund the buyer.');">Cancel Order</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold text-success">Order Completed</h5>
                            <p class="text-muted small mb-0">Great job! This order is finished.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($user_role === 'seeker'): ?>
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="text-center mb-4">
                            <i class="fas fa-credit-card text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold">Order Pending Payment</h5>
                            <p class="text-muted small">Please complete the payment to start the order.</p>
                        </div>
                        <a href="../payments/checkout.php?order_id=<?php echo $order_id; ?>"
                            class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-hover">
                            Pay Now ($<?php echo number_format($order_price, 2); ?>)
                        </a>
                        <form action="" method="POST" class="mt-2">
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-outline-danger w-100 py-3 rounded-pill fw-bold" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel Order</button>
                        </form>
                    <?php elseif ($order['status'] === 'in_progress'): ?>
                         <div class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h5 class="fw-bold">In Progress</h5>
                            <p class="text-muted small mb-3">Waiting for provider updates.</p>
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-outline-danger w-100 py-2 rounded-pill fw-bold" onclick="return confirm('Are you sure you want to cancel this order? You will be refunded.');">Cancel Order</button>
                        </form>
                    <?php elseif ($order['status'] === 'delivered'): ?>
                        <div class="alert alert-success border-0 rounded-3 mb-4">
                            <i class="fas fa-gift me-2"></i> <strong>Work Delivered!</strong><br>
                            Please review the files and complete the order or request a revision.
                        </div>
                        <form action="" method="POST" class="mb-3">
                            <input type="hidden" name="action" value="complete_order">
                            <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-hover">Accept &
                                Complete
                                Order</button>
                        </form>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="request_revision">
                            <button type="submit" class="btn btn-outline-warning w-100 py-2 rounded-pill fw-bold">Request
                                Revision</button>
                        </form>
                    <?php elseif ($order['status'] === 'completed'): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold text-success">Order Completed</h5>
                            <p class="text-muted small mb-3">Thank you for your business.</p>

                            <a href="../invoices/view.php?order_id=<?php echo $order_id; ?>"
                                class="btn btn-outline-dark btn-sm rounded-pill mb-3" target="_blank">
                                <i class="fas fa-file-invoice me-2"></i> View Invoice
                            </a>

                            <?php if (!$existing_review): ?>
                                <button type="button" class="btn btn-warning w-100 mt-3 rounded-pill fw-bold shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    <i class="fas fa-star me-2"></i> Leave a Review
                                </button>
                            <?php else: ?>
                                <div class="mt-3 p-3 bg-light rounded-3 border border-warning">
                                    <small class="text-muted d-block mb-1">Your Rating</small>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++)
                                            echo $i <= $existing_review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($order['status'] === 'cancelled'): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-times-circle text-danger mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold text-danger">Order Cancelled</h5>
                            <p class="text-muted small mb-0">This order has been cancelled and refunded.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Rate Your Experience</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="reviewForm">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

                    <div class="mb-4 text-center">
                        <label class="form-label d-block text-muted mb-3">How was the delivery?</label>
                        <div class="rating-stars h2 text-warning cursor-pointer">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Write a Review</label>
                        <textarea class="form-control bg-light border-0" name="comment" rows="4"
                            placeholder="Share your experience working with this seller..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">Submit
                        Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stars = document.querySelectorAll('.rating-stars i');
        const ratingInput = document.getElementById('ratingInput');

        stars.forEach(star => {
            star.addEventListener('mouseover', function () {
                const rating = this.dataset.rating;
                highlightStars(rating);
            });

            star.addEventListener('mouseout', function () {
                const currentRating = ratingInput.value;
                highlightStars(currentRating);
            });

            star.addEventListener('click', function () {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                highlightStars(rating);
            });
        });

        function highlightStars(rating) {
            stars.forEach(star => {
                const starRating = star.dataset.rating;
                if (starRating <= rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
        }

        document.getElementById('reviewForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!ratingInput.value) {
                alert('Please select a star rating.');
                return;
            }

            const formData = new FormData(this);

            fetch('/inkmybook/modules/reviews/api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>