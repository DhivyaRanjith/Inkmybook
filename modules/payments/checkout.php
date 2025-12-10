<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if (!isset($_GET['order_id'])) {
    flash('error', 'Invalid order.', 'danger');
    redirect('/inkmybook/modules/orders/my_orders.php');
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch Order
$stmt = $pdo->prepare("SELECT o.*, s.title, s.price FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'pending'");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found or already paid.', 'danger');
    redirect('/inkmybook/modules/orders/my_orders.php');
}

$amount = $order['price'];

include '../../includes/header.php';
?>

<style>
    .payment-method-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .payment-method-header {
        background-color: #fff;
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .payment-method-header:hover {
        background-color: #f8f9fa;
    }

    .payment-method-header.active {
        background-color: #f0f7ff;
        border-left: 4px solid #0d6efd;
    }

    .payment-method-body {
        padding: 20px;
        background-color: #fff;
        display: none;
    }

    .payment-method-body.show {
        display: block;
    }

    .summary-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background-color: #fff;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 0.95rem;
    }

    .summary-total {
        border-top: 1px solid #e0e0e0;
        padding-top: 15px;
        margin-top: 15px;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .secure-badge {
        font-size: 0.8rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
</style>

<div class="container py-5">
    <div class="row g-4">
        <!-- Left Column: Payment Methods -->
        <div class="col-lg-8">
            <h4 class="mb-4 fw-bold">Select Payment Method</h4>

            <div class="payment-method-card mb-3">
                <!-- PayU Option -->
                <div class="payment-method-header active">
                    <div class="d-flex align-items-center">
                        <input class="form-check-input me-3" type="radio" name="payment_method" id="method_payu"
                            checked>
                        <label class="form-check-label fw-bold w-100" for="method_payu">
                            PayU
                            <span class="float-end text-muted small fw-normal">Credit/Debit Card, NetBanking, UPI</span>
                        </label>
                    </div>
                </div>
                <div class="payment-method-body show">
                    <p class="text-muted mb-0">You will be redirected to PayU to complete your payment securely.</p>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary -->
        <div class="col-lg-4">
            <div class="summary-card p-4 shadow-sm">
                <h5 class="fw-bold mb-4">Payment summary <span class="float-end text-muted small fw-normal">(INR)</span>
                </h5>

                <div class="summary-row">
                    <span class="text-muted text-truncate"
                        style="max-width: 60%;"><?php echo htmlspecialchars($order['title']); ?></span>
                    <span class="fw-bold">₹<?php echo number_format($amount, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-bold">₹<?php echo number_format($amount, 2); ?></span>
                </div>

                <div class="summary-row summary-total text-dark">
                    <span>Total due</span>
                    <span>₹<?php echo number_format($amount, 2); ?></span>
                </div>

                <button id="payu-button" class="btn btn-primary w-100 py-3 mt-4 rounded-1 fw-bold fs-5"
                    onclick="alert('PayU Integration Pending')">
                    Pay with PayU ₹<?php echo number_format($amount, 2); ?>
                </button>

                <p class="text-muted small mt-3 lh-sm" style="font-size: 0.75rem;">
                    By continuing, you agree to our <a href="#" class="text-decoration-none">Terms & Conditions</a>.
                </p>

                <div class="mt-4 d-flex justify-content-center gap-3 align-items-center">
                    <div class="secure-badge">
                        <i class="fas fa-shield-alt fa-lg"></i>
                        <div class="d-flex flex-column lh-1">
                            <span class="fw-bold">Secure</span>
                            <span style="font-size: 0.6rem;">SSL Encryption</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>