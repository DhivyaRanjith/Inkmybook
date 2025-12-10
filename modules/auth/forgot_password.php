<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        // In a real app, send email with token. Here we simulate it.
        $message = "If an account exists for this email, a password reset link has been sent. (Simulation: Check your logs or contact admin)";
    } else {
        // Generic message for security
        $message = "If an account exists for this email, a password reset link has been sent.";
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="mb-4 text-primary">
                        <i class="fas fa-lock fa-3x"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Forgot Password?</h3>
                    <p class="text-muted mb-4">Enter your email and we'll send you a link to reset your password.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success small"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3 text-start">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com"
                                required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary rounded-pill fw-bold">Send Reset Link</button>
                        </div>
                    </form>
                    <a href="login.php" class="text-decoration-none small text-muted"><i
                            class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>