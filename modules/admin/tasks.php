<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// requireAdmin();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Task deleted successfully.');
    }
    redirect('tasks.php');
}

// Fetch Tasks
$tasks = $pdo->query("
    SELECT t.*, u.name as seeker_name, c.name as category_name 
    FROM tasks t 
    JOIN users u ON t.seeker_id = u.id 
    JOIN subcategories sub ON t.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    ORDER BY t.created_at DESC
")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Task Management</h2>
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
                            <th class="py-3">Title</th>
                            <th class="py-3">Seeker</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Budget</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $task['id']; ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars(substr($task['title'], 0, 40)) . '...'; ?></div>
                                    <small
                                        class="text-muted"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($task['seeker_name']); ?></td>
                                <td><span
                                        class="badge bg-light text-dark border"><?php echo htmlspecialchars($task['category_name']); ?></span>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($task['budget'], 2); ?></td>
                                <td>
                                    <span
                                        class="badge bg-secondary rounded-pill"><?php echo ucfirst($task['status']); ?></span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="../tasks/view.php?id=<?php echo $task['id']; ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill me-1" target="_blank">View</a>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="return confirm('Delete this task?')">Delete</button>
                                    </form>
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