<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$id]);
    $withdrawal = $stmt->fetch();

    if ($withdrawal && $withdrawal['status'] == 'pending') {
        if ($action == 'approve') {
            $update = $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
            $update->execute([$id]);
            flash('success', 'Withdrawal approved.');
        } elseif ($action == 'reject') {
            // Refund the amount to wallet
            $pdo->beginTransaction();
            try {
                $update = $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
                $update->execute([$id]);

                $refund = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $refund->execute([$withdrawal['amount'], $withdrawal['user_id']]);

                $txn = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', 'Withdrawal Rejected - Refund')");
                $txn->execute([$withdrawal['user_id'], $withdrawal['amount']]);

                $pdo->commit();
                flash('success', 'Withdrawal rejected and refunded.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Error: ' . $e->getMessage(), 'danger');
            }
        }
    }
    redirect('withdrawals.php');
}

$withdrawals = $pdo->query("
    SELECT w.*, u.name, u.email 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.created_at DESC
")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Withdrawal Requests</h2>
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
                            <th class="py-3">User</th>
                            <th class="py-3">Amount</th>
                            <th class="py-3">Payment Details</th>
                            <th class="py-3">Date</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $w['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($w['name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($w['email']); ?></small>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($w['amount'], 2); ?></td>
                                <td><small><?php echo nl2br(htmlspecialchars($w['payment_details'])); ?></small></td>
                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($w['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $badge = match ($w['status']) {
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                    };
                                    ?>
                                    <span
                                        class="badge <?php echo $badge; ?> rounded-pill"><?php echo strtoupper($w['status']); ?></span>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($w['status'] == 'pending'): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $w['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3"
                                                onclick="return confirm('Approve this withdrawal?')"><i
                                                    class="fas fa-check"></i></button>
                                        </form>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $w['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3"
                                                onclick="return confirm('Reject and refund?')"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
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