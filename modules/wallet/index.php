<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Only providers should have a wallet for now (or both, but withdrawals are for providers)
if ($user_role !== 'provider' && $user_role !== 'seeker') {
    // redirect('/inkmybook/modules/user/dashboard.php'); 
    // Let's allow seekers to see it too, maybe for refunds later.
}
// Handle Add Funds Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_funds'])) {
    $amount = (float) $_POST['amount'];

    if ($amount <= 0) {
        flash('error', 'Invalid amount.', 'danger');
    } elseif (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        // Upload Receipt
        $target_dir = "../../uploads/deposits/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('dep_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        $db_filepath = "uploads/deposits/" . $new_filename;

        // Allow certain file formats
        $allowed_types = ['jpg', 'png', 'jpeg', 'pdf'];
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, proof_file, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$user_id, $amount, $db_filepath]);
                    flash('success', 'Deposit request submitted. Admin will verify it shortly.');
                } catch (PDOException $e) {
                    flash('error', 'Database error: ' . $e->getMessage(), 'danger');
                }
            } else {
                flash('error', 'Failed to upload receipt.', 'danger');
            }
        } else {
            flash('error', 'Only JPG, JPEG, PNG, & PDF files are allowed.', 'danger');
        }
    } else {
        flash('error', 'Please upload a payment receipt.', 'danger');
    }
    redirect('index.php');
}

// Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw'])) {
    $amount = (float) $_POST['amount'];
    $payment_details = sanitizeInput($_POST['payment_details']);

    // Check Balance
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetchColumn();

    if ($amount > 0 && $amount <= $balance) {
        // Create Withdrawal Request
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, payment_details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $payment_details]);

        // Deduct from Wallet (or hold it? Let's deduct immediately to prevent double spend)
        // Ideally we should use transactions here.
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', 'Withdrawal Request')");
            $stmt->execute([$user_id, $amount]);

            $pdo->commit();
            flash('success', 'Withdrawal request submitted successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Transaction failed: ' . $e->getMessage(), 'danger');
        }
    } else {
        flash('error', 'Insufficient balance or invalid amount.', 'danger');
    }
    redirect('index.php');
}

// Handle Bank Details Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_bank_details'])) {

    // Validate inputs
    $account_holder_name = sanitizeInput($_POST['account_holder_name']);
    $account_number = sanitizeInput($_POST['account_number']);
    $bank_name = sanitizeInput($_POST['bank_name']);
    $branch_name = sanitizeInput($_POST['branch_name']);
    $ifsc_code = sanitizeInput($_POST['ifsc_code']);
    $swift_code = sanitizeInput($_POST['swift_code']);
    $bank_address = sanitizeInput($_POST['bank_address']);

    // Basic Validation (ensure all fields are filled)
    if (empty($account_holder_name) || empty($account_number) || empty($bank_name) || empty($branch_name) || empty($ifsc_code) || empty($swift_code) || empty($bank_address)) {
        flash('error', 'All bank details fields are mandatory.', 'danger');
    } else {
        try {
            // Check if details exist
            $check = $pdo->prepare("SELECT id FROM bank_details WHERE user_id = ?");
            $check->execute([$user_id]);

            if ($check->rowCount() > 0) {
                // Update - Force status back to pending for re-verification
                $sql = "UPDATE bank_details SET account_holder_name=?, account_number=?, bank_name=?, branch_name=?, ifsc_code=?, swift_code=?, bank_address=?, status='pending' WHERE user_id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$account_holder_name, $account_number, $bank_name, $branch_name, $ifsc_code, $swift_code, $bank_address, $user_id]);
                flash('success', 'Bank details updated. Please wait for admin verification.');
            } else {
                // Insert - Status defaults to pending
                $sql = "INSERT INTO bank_details (user_id, account_holder_name, account_number, bank_name, branch_name, ifsc_code, swift_code, bank_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $account_holder_name, $account_number, $bank_name, $branch_name, $ifsc_code, $swift_code, $bank_address]);
                flash('success', 'Bank details saved. Please wait for admin verification.');
            }
        } catch (PDOException $e) {
            flash('error', 'Database error: ' . $e->getMessage(), 'danger');
        }
    }
    // Refresh to show updated data
    redirect('index.php');
}

// Fetch Wallet Data
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();

// Fetch Transactions
$stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-lg border-0 rounded-4 bg-primary text-white mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4 opacity-75">Current Balance</h5>
                    <h1 class="display-4 fw-bold mb-4">$<?php echo number_format($balance, 2); ?></h1>
                    <button class="btn btn-light rounded-pill w-100 fw-bold text-primary" data-bs-toggle="modal"
                        data-bs-target="#withdrawModal">
                        <i class="fas fa-money-bill-wave me-2"></i> Withdraw Funds
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Withdrawal Status</h6>
                    <?php
                    $w_stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $w_stmt->execute([$user_id]);
                    $withdrawals = $w_stmt->fetchAll();

                    if (empty($withdrawals)) {
                        echo '<p class="text-muted small">No recent withdrawals.</p>';
                    } else {
                        foreach ($withdrawals as $w) {
                            $badge = match ($w['status']) {
                                'pending' => 'bg-warning',
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                            };
                            echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                            echo '<small class="text-muted">' . date('M d', strtotime($w['created_at'])) . '</small>';
                            echo '<span class="fw-bold">$' . number_format($w['amount'], 2) . '</span>';
                            echo '<span class="badge ' . $badge . ' rounded-pill" style="font-size: 0.6rem;">' . strtoupper($w['status']) . '</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Transaction History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">Date</th>
                                    <th class="py-3">Description</th>
                                    <th class="py-3">Type</th>
                                    <th class="pe-4 py-3 text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No transactions yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small">
                                                <?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                                            <td>
                                                <?php if ($t['type'] == 'credit'): ?>
                                                    <span
                                                        class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Credit</span>
                                                <?php else: ?>
                                                    <span
                                                        class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Debit</span>
                                                <?php endif; ?>
                                            </td>
                                            <td
                                                class="pe-4 text-end fw-bold <?php echo $t['type'] == 'credit' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $t['type'] == 'credit' ? '+' : '-'; ?>$<?php echo number_format($t['amount'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

       <!-- Bank Details Section -->
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Bank Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <input type="hidden" name="save_bank_details" value="1">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Account Holder Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="account_holder_name"
                                        value="<?php echo htmlspecialchars($bank_details['account_holder_name'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Account Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="account_number"
                                        value="<?php echo htmlspecialchars($bank_details['account_number'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Bank Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="bank_name"
                                        value="<?php echo htmlspecialchars($bank_details['bank_name'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Branch Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="branch_name"
                                        value="<?php echo htmlspecialchars($bank_details['branch_name'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">IFSC Code <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="ifsc_code"
                                        value="<?php echo htmlspecialchars($bank_details['ifsc_code'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">SWIFT Code <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="swift_code"
                                        value="<?php echo htmlspecialchars($bank_details['swift_code'] ?? ''); ?>"
                                        required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted">Bank Address <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" name="bank_address" rows="2" required <?php echo $is_bank_locked ? 'readonly disabled' : ''; ?>><?php echo htmlspecialchars($bank_details['bank_address'] ?? ''); ?></textarea>
                                </div>
                                <?php if (!$is_bank_locked): ?>
                                    <div class="col-12 text-end mt-4">
                                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">Save
                                            Bank Details</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Request Withdrawal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="" method="POST">
                    <input type="hidden" name="withdraw" value="1">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Amount ($)</label>
                        <input type="number" class="form-control form-control-lg" name="amount" min="10"
                            max="<?php echo $balance; ?>" step="0.01" required>
                        <div class="form-text">Minimum withdrawal is $10.00</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Payment Details (PayPal / Bank)</label>
                        <textarea class="form-control" name="payment_details" rows="3"
                            placeholder="e.g. PayPal Email or Bank Account Details" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">Submit
                        Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Funds Modal -->
    <div class="modal fade" id="addFundsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Funds to Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Since this is a demo, please upload a screenshot of your
                        payment transfer. Admin will verify and credit your wallet.
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_funds" value="1">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Amount ($)</label>
                            <input type="number" class="form-control form-control-lg" name="amount" min="1" step="0.01"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Upload Payment Receipt</label>
                            <input type="file" class="form-control" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                                required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold">Submit
                            Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>