<?php
session_start();
require_once '../../config/db.php';

// Auth Check
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'support' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header("Location: index.php");
    exit;
}

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message = trim($_POST['reply']);
    $attachment = null;

    // Handle File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/support/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $attachment = 'uploads/support/' . $fileName;
        }
    }

    if (!empty($message) || $attachment) {
        $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, message, attachment) VALUES (?, 'support', ?, ?)");
        $stmt->execute([$ticket_id, $message, $attachment]);

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['id']; ?> - InkMyBook Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            width: 250px;
            background: white;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #eee;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .nav-link {
            color: #666;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link:hover {
            background: #f0f2f5;
            color: #f20091;
            font-weight: 600;
        }

        .brand-logo {
            padding: 1.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #f20091;
        }

        .chat-bubble {
            max-width: 75%;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            position: relative;
        }

        .chat-bubble.user {
            background: white;
            border: 1px solid #eee;
            margin-right: auto;
        }

        .chat-bubble.support {
            background: #f20091;
            color: white;
            margin-left: auto;
        }

        .chat-meta {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-bottom: 4px;
            display: block;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand-logo"><i class="fas fa-feather-alt me-2"></i>InkMyBook</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php?status=all"><i class="fas fa-inbox"></i> All Tickets</a>
            <a class="nav-link" href="index.php?status=open"><i class="fas fa-envelope-open"></i> Open</a>
            <a class="nav-link" href="index.php?status=closed"><i class="fas fa-check-circle"></i> Closed</a>
            <a class="nav-link mt-5 text-danger" href="../../modules/auth/logout.php"><i
                    class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="index.php" class="text-decoration-none text-muted mb-2 d-block"><i
                                class="fas fa-arrow-left"></i> Back to Dashboard</a>
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
                                <button type="submit" name="reopen_ticket"
                                    class="btn btn-outline-success rounded-pill">Reopen Ticket</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-body p-4" style="height: 500px; overflow-y: auto; background: #f8f9fa;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="d-flex w-100">
                                <div
                                    class="chat-bubble <?php echo $msg['sender_type'] === 'support' ? 'support' : 'user'; ?>">
                                    <small class="chat-meta">
                                        <?php echo $msg['sender_type'] === 'support' ? 'You' : htmlspecialchars($ticket['user_name']); ?>
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
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reply to User</label>
                                    <textarea name="reply" class="form-control" rows="3"
                                        placeholder="Type your reply here..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Attachment (Optional)</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-4"
                                    style="background-color: #f20091; border: none;">Send Reply</button>
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

</body>

</html>