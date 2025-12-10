<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Admin Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // header("Location: /inkmybook/modules/auth/login.php");
    // exit;
}

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header("Location: support.php");
    exit;
}

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message = trim($_POST['reply']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, message) VALUES (?, 'support', ?)");
        $stmt->execute([$ticket_id, $message]);

        // Update ticket timestamp
        $pdo->prepare("UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$ticket_id]);
    }
}

// Handle Status Change
if (isset($_POST['close_ticket'])) {
    $pdo->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ?")->execute([$ticket_id]);
}
if (isset($_POST['reopen_ticket'])) {
    $pdo->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?")->execute([$ticket_id]);
}

// Fetch Ticket Details
$stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "Ticket not found.";
    exit;
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="support.php" class="text-decoration-none text-muted mb-2 d-block"><i
                            class="fas fa-arrow-left"></i> Back to Tickets</a>
                    <h2 class="fw-bold mb-0">Ticket #<?php echo $ticket['id']; ?></h2>
                    <span class="text-muted">User: <?php echo htmlspecialchars($ticket['user_name']); ?>
                        (<?php echo htmlspecialchars($ticket['user_email']); ?>)</span>
                </div>
                <div>
                    <?php if ($ticket['status'] === 'open'): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="close_ticket" class="btn btn-outline-danger rounded-pill">Close
                                Ticket</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="reopen_ticket" class="btn btn-outline-success rounded-pill">Reopen
                                Ticket</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4" style="height: 400px; overflow-y: auto; background: #f8f9fa;">
                    <?php foreach ($messages as $msg): ?>
                        <div
                            class="d-flex mb-3 <?php echo $msg['sender_type'] === 'support' ? 'justify-content-end' : ''; ?>">
                            <div class="p-3 rounded-3 shadow-sm <?php echo $msg['sender_type'] === 'support' ? 'bg-primary text-white' : 'bg-white text-dark'; ?>"
                                style="max-width: 75%;">
                                <small class="d-block opacity-75 mb-1" style="font-size: 0.75rem;">
                                    <?php echo $msg['sender_type'] === 'support' ? 'Support Agent' : htmlspecialchars($ticket['user_name']); ?>
                                    •
                                    <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                                </small>
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                <?php if ($msg['attachment']): ?>
                                    <div class="mt-2">
                                        <a href="/inkmybook/<?php echo $msg['attachment']; ?>" target="_blank"
                                            class="text-decoration-underline <?php echo $msg['sender_type'] === 'support' ? 'text-white' : 'text-primary'; ?>">View
                                            Attachment</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($ticket['status'] === 'open'): ?>
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Reply to User</label>
                                <textarea name="reply" class="form-control" rows="3" placeholder="Type your reply here..."
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Send Reply</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary text-center rounded-4">
                    This ticket is closed. Reopen it to send a reply.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>