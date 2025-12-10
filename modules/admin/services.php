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
        // Soft delete or hard delete? Let's do hard delete for now but check dependencies
        // Ideally we should have is_active flag.
        // For now, let's just delete from services table.
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'Service deleted successfully.');
        } catch (PDOException $e) {
            flash('error', 'Cannot delete service. It might be linked to orders.', 'danger');
        }
    }
    redirect('services.php');
}

// Fetch Services
$services = $pdo->query("
    SELECT s.*, u.name as provider_name, c.name as category_name 
    FROM services s 
    JOIN users u ON s.provider_id = u.id 
    JOIN subcategories sub ON s.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    ORDER BY s.created_at DESC
")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Service Management</h2>
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
                            <th class="py-3">Provider</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Price</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $service['id']; ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars(substr($service['title'], 0, 40)) . '...'; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($service['provider_name']); ?></td>
                                <td><span
                                        class="badge bg-light text-dark border"><?php echo htmlspecialchars($service['category_name']); ?></span>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($service['price'], 2); ?></td>
                                <td class="pe-4 text-end">
                                    <a href="../services/detail.php?id=<?php echo $service['id']; ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill me-1" target="_blank">View</a>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="return confirm('Delete this service?')">Delete</button>
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