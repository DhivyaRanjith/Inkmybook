<?php
session_start();
require_once '../../config/db.php';

// Auth Check
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'support' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

// Fetch Tickets
$status_filter = $_GET['status'] ?? 'all';
$sql = "SELECT t.*, u.name as user_name, u.email as user_email,
        (SELECT message FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id";

if ($status_filter !== 'all') {
    $sql .= " WHERE t.status = '$status_filter'";
}
$sql .= " ORDER BY t.updated_at DESC";

$tickets = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard - InkMyBook</title>
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

        .nav-link:hover,
        .nav-link.active {
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

        .ticket-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand-logo"><i class="fas fa-feather-alt me-2"></i>InkMyBook</div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="index.php?status=all">
                <i class="fas fa-inbox"></i> All Tickets
            </a>
            <a class="nav-link <?php echo $status_filter === 'open' ? 'active' : ''; ?>" href="index.php?status=open">
                <i class="fas fa-envelope-open"></i> Open
            </a>
            <a class="nav-link <?php echo $status_filter === 'closed' ? 'active' : ''; ?>"
                href="index.php?status=closed">
                <i class="fas fa-check-circle"></i> Closed
            </a>
            <a class="nav-link mt-5 text-danger" href="../../modules/auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Support Tickets</h2>
            <span class="badge bg-secondary rounded-pill"><?php echo count($tickets); ?> Tickets</span>
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
                                <th class="py-3">Updated</th>
                                <th class="pe-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No tickets found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr onclick="window.location='ticket.php?id=<?php echo $ticket['id']; ?>'"
                                        style="cursor:pointer;">
                                        <td class="ps-4 fw-bold">#<?php echo $ticket['id']; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="fw-bold"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
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
                                            <a href="ticket.php?id=<?php echo $ticket['id']; ?>"
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

</body>

</html>