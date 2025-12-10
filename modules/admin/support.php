<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Admin Check (Simplified)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // header("Location: /inkmybook/modules/auth/login.php");
    // exit;
}

include '../../includes/header.php';

// Fetch Tickets
$stmt = $pdo->query("
    SELECT t.*, u.name as user_name, u.email as user_email,
    (SELECT message FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.updated_at DESC
");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Support Tickets</h2>
        <span class="badge bg-primary rounded-pill"><?php echo count($tickets); ?> Total</span>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">ID</th>
                            <th class="py-3">User</th>
                            <th class="py-3">Last Message</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Last Updated</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No support tickets found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#<?php echo $ticket['id']; ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($ticket['user_email']); ?></small>
                                        </div>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo htmlspecialchars(substr($ticket['last_message'] ?? 'No messages', 0, 50)) . '...'; ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['status'] === 'open'): ?>
                                            <span class="badge bg-success rounded-pill">Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('M d, H:i', strtotime($ticket['updated_at'])); ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="support_ticket.php?id=<?php echo $ticket['id']; ?>"
                                            class="btn btn-sm btn-primary rounded-pill">View</a>
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

<?php include '../../includes/footer.php'; ?>