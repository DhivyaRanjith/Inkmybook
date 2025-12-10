<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'seeker') {
    flash('error', 'Only seekers can place orders.', 'danger');
    redirect('/inkmybook/index.php');
}

if (!isset($_GET['service_id'])) {
    redirect('/inkmybook/index.php');
}

$service_id = $_GET['service_id'];
$buyer_id = $_SESSION['user_id'];

// Fetch Service Details
$stmt = $pdo->prepare("SELECT s.*, u.name as provider_name FROM services s JOIN users u ON s.provider_id = u.id WHERE s.id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    flash('error', 'Service not found.', 'danger');
    redirect('/inkmybook/index.php');
}

if ($service['provider_id'] == $buyer_id) {
    flash('error', 'You cannot buy your own service.', 'warning');
    redirect('/inkmybook/modules/services/detail.php?id=' . $service_id);
}

// Handle Order Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = sanitizeInput($_POST['notes']);

    try {
        $delivery_days = $service['delivery_days'];
        $delivery_date = date('Y-m-d', strtotime("+$delivery_days days"));

        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, provider_id, service_id, amount, status, delivery_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$buyer_id, $service['provider_id'], $service_id, $service['price'], $delivery_date, $notes]);

        $order_id = $pdo->lastInsertId();

        flash('success', 'Order placed successfully!');
        redirect('view.php?id=' . $order_id);

    } catch (PDOException $e) {
        flash('error', 'Error placing order: ' . $e->getMessage(), 'danger');
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 animate-slide-up">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Secure Checkout</h2>
                <p class="text-muted">Complete your purchase to start the project.</p>
            </div>

            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-light py-3 px-4 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">Order Summary</h5>
                </div>
                <div class="card-body p-5">
                    <div class="d-flex align-items-start mb-5">
                        <img src="/inkmybook/<?php echo htmlspecialchars($service['image']); ?>"
                            class="rounded-3 me-4 shadow-sm" style="width: 120px; height: 80px; object-fit: cover;">
                        <div>
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($service['title']); ?></h5>
                            <div class="d-flex align-items-center text-muted small">
                                <span class="me-3"><i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($service['provider_name']); ?></span>
                                <span><i class="fas fa-star text-warning me-1"></i> 5.0</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5 g-4">
                        <div class="col-md-6">
                            <div class="p-4 bg-light rounded-4 border border-light text-center h-100">
                                <small class="text-muted d-block text-uppercase fw-bold mb-2"
                                    style="font-size: 0.75rem;">Total Price</small>
                                <span
                                    class="fw-bold display-6 text-primary">$<?php echo number_format($service['price'], 2); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 bg-light rounded-4 border border-light text-center h-100">
                                <small class="text-muted d-block text-uppercase fw-bold mb-2"
                                    style="font-size: 0.75rem;">Delivery Time</small>
                                <span class="fw-bold display-6 text-dark"><?php echo $service['delivery_days']; ?> <span
                                        class="fs-5 text-muted">Days</span></span>
                            </div>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <div class="mb-5">
                            <label for="notes" class="form-label fw-bold">Additional Notes / Requirements</label>
                            <textarea class="form-control form-control-lg" id="notes" name="notes" rows="5"
                                placeholder="Describe your requirements in detail to the seller..."></textarea>
                            <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i> Please provide any
                                specific instructions, links, or file locations here.</div>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-success btn-lg py-3 fw-bold rounded-pill shadow-hover">
                                <i class="fas fa-lock me-2"></i> Confirm & Pay
                                $<?php echo number_format($service['price'], 2); ?>
                            </button>
                            <a href="/inkmybook/modules/services/detail.php?id=<?php echo $service_id; ?>"
                                class="btn btn-outline-secondary rounded-pill fw-bold">Cancel Order</a>
                        </div>

                        <div class="text-center mt-4">
                            <small class="text-muted"><i class="fas fa-shield-alt me-1"></i> SSL Secure Payment</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>