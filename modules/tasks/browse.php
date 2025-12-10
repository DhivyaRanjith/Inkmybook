<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filter by Category
$category_filter = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$where_clause = "WHERE t.status = 'open'";
$params = [];

if ($category_filter > 0) {
    $where_clause .= " AND sub.category_id = ?";
    $params[] = $category_filter;
}

// Count Total
$sql_count = "SELECT COUNT(*) FROM tasks t JOIN subcategories sub ON t.subcategory_id = sub.id $where_clause";
$stmt = $pdo->prepare($sql_count);
$stmt->execute($params);
$total_results = $stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch Tasks
$sql = "
    SELECT t.*, c.name as category_name, sub.name as subcategory_name, u.name as seeker_name,
    (SELECT COUNT(*) FROM bids WHERE task_id = t.id) as bid_count
    FROM tasks t 
    JOIN subcategories sub ON t.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    JOIN users u ON t.seeker_id = u.id
    $where_clause
    ORDER BY t.created_at DESC 
    LIMIT $start, $limit
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Fetch Categories for Sidebar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4 animate-slide-up">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    <i class="fas fa-filter me-2 text-primary"></i> Categories
                </div>
                <div class="list-group list-group-flush">
                    <a href="browse.php"
                        class="list-group-item list-group-item-action py-3 <?php echo $category_filter == 0 ? 'active fw-bold' : ''; ?>">
                        All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="browse.php?category=<?php echo $cat['id']; ?>"
                            class="list-group-item list-group-item-action py-3 <?php echo $category_filter == $cat['id'] ? 'active fw-bold' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 animate-slide-up delay-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Browse Job Requests</h2>
                <span class="text-muted small"><?php echo $total_results; ?> jobs found</span>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="text-center py-5 bg-light rounded-4">
                    <i class="fas fa-search text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-muted">No jobs found</h5>
                    <p class="text-muted mb-0">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="list-group shadow-sm border-0 rounded-4 overflow-hidden">
                    <?php foreach ($tasks as $task): ?>
                        <a href="view.php?id=<?php echo $task['id']; ?>"
                            class="list-group-item list-group-item-action p-4 border-bottom service-card-hover transition-all">
                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></h5>
                                    <div class="text-muted small mb-2">
                                        <span
                                            class="badge badge-soft-info rounded-pill me-2"><?php echo htmlspecialchars($task['category_name']); ?></span>
                                        <span class="text-muted"><i class="far fa-clock me-1"></i> Posted
                                            <?php echo date('M d', strtotime($task['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span
                                        class="fw-bold fs-5 text-primary">$<?php echo number_format($task['budget']); ?></span>
                                    <small class="d-block text-muted">Fixed Price</small>
                                </div>
                            </div>

                            <p class="mb-3 text-muted text-truncate" style="max-width: 90%;">
                                <?php echo htmlspecialchars(substr($task['description'], 0, 200)) . '...'; ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-ring rounded-circle me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold"
                                        style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        <?php echo strtoupper(substr($task['seeker_name'], 0, 1)); ?>
                                    </div>
                                    <small
                                        class="text-muted fw-medium"><?php echo htmlspecialchars($task['seeker_name']); ?></small>
                                </div>
                                <span class="badge bg-light text-dark border rounded-pill px-3">
                                    <i class="fas fa-gavel me-1 text-secondary"></i> <?php echo $task['bid_count']; ?> Proposals
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;"
                                        href="?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>