<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'provider') {
    redirect('/inkmybook/index.php');
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Verify ownership
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND provider_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        flash('success', 'Service deleted successfully!');
        redirect('my_gigs.php');
    } catch (PDOException $e) {
        flash('error', 'Error deleting service: ' . $e->getMessage(), 'danger');
    }
}

// Fetch Provider's Gigs
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name, sub.name as subcategory_name 
    FROM services s 
    JOIN subcategories sub ON s.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    WHERE s.provider_id = ? 
    ORDER BY s.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$services = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 animate-slide-up">
        <div>
            <h2 class="fw-bold mb-1">My Gigs</h2>
            <p class="text-muted">Manage your active services and offers.</p>
        </div>
        <a href="create.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-plus me-2"></i>Create
            New Gig</a>
    </div>

    <?php flash('success'); ?>
    <?php flash('error'); ?>

    <?php if (empty($services)): ?>
        <div class="text-center py-5 animate-slide-up delay-100">
            <div class="empty-state-icon mb-3">
                <i class="fas fa-folder-open text-muted opacity-25" style="font-size: 5rem;"></i>
            </div>
            <h4 class="fw-bold text-muted">No Gigs Found</h4>
            <p class="text-muted mb-4">You haven't created any services yet. Start selling today!</p>
            <a href="create.php" class="btn btn-outline-primary rounded-pill px-4">Create Your First Gig</a>
        </div>
    <?php else: ?>
        <div class="card table-card animate-slide-up delay-100">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Service</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Price</th>
                            <th class="py-3">Delivery</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <img src="/inkmybook/<?php echo htmlspecialchars($service['image']); ?>"
                                            class="rounded-3 me-3" alt="Service"
                                            style="width: 60px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 250px;">
                                                <?php echo htmlspecialchars($service['title']); ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="badge badge-soft-info rounded-pill">
                                        <?php echo htmlspecialchars($service['category_name']); ?>
                                    </span>
                                    <small class="text-muted d-block mt-1"
                                        style="font-size: 0.75rem;"><?php echo htmlspecialchars($service['subcategory_name']); ?></small>
                                </td>
                                <td class="py-3">
                                    <span
                                        class="fw-bold text-success">$<?php echo number_format($service['price'], 2); ?></span>
                                </td>
                                <td class="py-3">
                                    <span class="text-muted small"><i class="far fa-clock me-1"></i>
                                        <?php echo $service['delivery_days']; ?> Days</span>
                                </td>
                                <td class="text-end pe-4 py-3">
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?php echo $service['id']; ?>"
                                            class="btn btn-sm btn-light text-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detail.php?id=<?php echo $service['id']; ?>"
                                            class="btn btn-sm btn-light text-secondary" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="my_gigs.php?delete=<?php echo $service['id']; ?>"
                                            class="btn btn-sm btn-light text-danger"
                                            onclick="return confirm('Are you sure you want to delete this gig?')"
                                            data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
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