<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'seeker') {
    redirect('/inkmybook/index.php');
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND seeker_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        flash('success', 'Task deleted successfully!');
        redirect('my_tasks.php');
    } catch (PDOException $e) {
        flash('error', 'Error deleting task: ' . $e->getMessage(), 'danger');
    }
}

// Fetch Seeker's Tasks
$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, sub.name as subcategory_name,
    (SELECT COUNT(*) FROM bids WHERE task_id = t.id) as bid_count
    FROM tasks t 
    JOIN subcategories sub ON t.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    WHERE t.seeker_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 animate-slide-up">
        <div>
            <h2 class="fw-bold mb-1">My Job Requests</h2>
            <p class="text-muted">Manage your posted jobs and view proposals.</p>
        </div>
        <a href="create.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-plus me-2"></i>Post
            New Job</a>
    </div>

    <?php flash('success'); ?>
    <?php flash('error'); ?>

    <?php if (empty($tasks)): ?>
        <div class="text-center py-5 animate-slide-up delay-100">
            <div class="empty-state-icon mb-3">
                <i class="fas fa-clipboard-list text-muted opacity-25" style="font-size: 5rem;"></i>
            </div>
            <h4 class="fw-bold text-muted">No Jobs Posted</h4>
            <p class="text-muted mb-4">You haven't posted any job requests yet.</p>
            <a href="create.php" class="btn btn-outline-primary rounded-pill px-4">Post Your First Job</a>
        </div>
    <?php else: ?>
        <div class="card table-card animate-slide-up delay-100">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Job Title</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Budget</th>
                            <th class="py-3">Deadline</th>
                            <th class="py-3">Proposals</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <a href="view.php?id=<?php echo $task['id']; ?>"
                                        class="fw-bold text-dark text-decoration-none text-truncate d-block"
                                        style="max-width: 250px;">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </a>
                                    <small class="text-muted">Posted
                                        <?php echo date('M d', strtotime($task['created_at'])); ?></small>
                                </td>
                                <td class="py-3">
                                    <span class="badge badge-soft-info rounded-pill">
                                        <?php echo htmlspecialchars($task['category_name']); ?>
                                    </span>
                                    <small class="text-muted d-block mt-1"
                                        style="font-size: 0.75rem;"><?php echo htmlspecialchars($task['subcategory_name']); ?></small>
                                </td>
                                <td class="py-3">
                                    <span class="fw-bold text-dark">$<?php echo number_format($task['budget'], 2); ?></span>
                                </td>
                                <td class="py-3">
                                    <span class="text-muted small"><i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('M d, Y', strtotime($task['deadline'])); ?></span>
                                </td>
                                <td class="py-3">
                                    <span
                                        class="badge bg-light text-dark border rounded-pill px-3"><?php echo $task['bid_count']; ?>
                                        Bids</span>
                                </td>
                                <td class="py-3">
                                    <?php
                                    $status_class = 'secondary';
                                    $status_icon = 'fa-circle';
                                    if ($task['status'] == 'open') {
                                        $status_class = 'success';
                                        $status_icon = 'fa-check-circle';
                                    }
                                    if ($task['status'] == 'assigned') {
                                        $status_class = 'primary';
                                        $status_icon = 'fa-user-check';
                                    }
                                    if ($task['status'] == 'completed') {
                                        $status_class = 'dark';
                                        $status_icon = 'fa-flag-checkered';
                                    }
                                    if ($task['status'] == 'cancelled') {
                                        $status_class = 'danger';
                                        $status_icon = 'fa-times-circle';
                                    }
                                    ?>
                                    <span class="badge badge-soft-<?php echo $status_class; ?> rounded-pill text-uppercase">
                                        <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo str_replace('_', ' ', $task['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4 py-3">
                                    <div class="btn-group">
                                        <a href="view.php?id=<?php echo $task['id']; ?>"
                                            class="btn btn-sm btn-light text-primary" data-bs-toggle="tooltip"
                                            title="View Proposals">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($task['status'] == 'open'): ?>
                                            <a href="my_tasks.php?delete=<?php echo $task['id']; ?>"
                                                class="btn btn-sm btn-light text-danger"
                                                onclick="return confirm('Are you sure you want to delete this job?')"
                                                data-bs-toggle="tooltip" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>