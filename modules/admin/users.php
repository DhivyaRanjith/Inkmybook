<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// requireAdmin();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'block') {
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'User blocked successfully.');
    } elseif ($action == 'unblock') {
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'User unblocked successfully.');
    } elseif ($action == 'delete') {
        // Delete logic (cascade delete might be needed or soft delete)
        // For now, let's just block instead of delete to preserve data integrity
        flash('error', 'Deletion not implemented. Please block the user instead.', 'warning');
    }
    redirect('users.php');
}

// Fetch Users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">User Management</h2>
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
                            <th class="py-3">Name</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Role</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span
                                        class="badge bg-light text-dark border"><?php echo ucfirst($user['role']); ?></span>
                                </td>
                                <td>
                                    <?php if ($user['is_blocked']): ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($user['is_blocked']): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit"
                                                class="btn btn-success btn-sm rounded-pill px-3">Unblock</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3"
                                                onclick="return confirm('Block this user?')">Block</button>
                                        </form>
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