<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Parameters
$limit = 9;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int) $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int) $_GET['max_price'] : '';
$delivery_time = isset($_GET['delivery_time']) && $_GET['delivery_time'] !== '' ? (int) $_GET['delivery_time'] : '';
$min_rating = isset($_GET['rating']) ? (int) $_GET['rating'] : 0;
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Build Query
$where = "WHERE 1=1";
$params = [];

if ($query) {
    $where .= " AND (s.title LIKE ? OR u.name LIKE ? OR p.skills LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

if ($category_id) {
    $where .= " AND sub.category_id = ?";
    $params[] = $category_id;
}

if ($min_price !== '') {
    $where .= " AND s.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== '') {
    $where .= " AND s.price <= ?";
    $params[] = $max_price;
}

if ($delivery_time !== '') {
    $where .= " AND s.delivery_days <= ?";
    $params[] = $delivery_time;
}

// Sorting
$order_clause = "s.created_at DESC";
if ($sort === 'price_asc')
    $order_clause = "s.price ASC";
if ($sort === 'price_desc')
    $order_clause = "s.price DESC";
// For rating, we'll handle it in the query

// Main Query
$sql = "
    SELECT s.*, c.name as category_name, sub.name as subcategory_name, u.name as provider_name, p.user_id as provider_id,
    (SELECT AVG(rating) FROM reviews WHERE service_id = s.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE service_id = s.id) as review_count
    FROM services s 
    JOIN subcategories sub ON s.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    JOIN users u ON s.provider_id = u.id
    LEFT JOIN profiles p ON u.id = p.user_id
    $where
";

// Apply Rating Filter (Having clause)
if ($min_rating > 0) {
    $sql = "SELECT * FROM ($sql) as results WHERE avg_rating >= $min_rating";
}

// Apply Sorting
if ($sort === 'rating') {
    $sql .= " ORDER BY avg_rating DESC, review_count DESC";
} else {
    $sql .= " ORDER BY $order_clause";
}

// Pagination
$sql_limit = "$sql LIMIT $start, $limit";

// Execute for Results
$stmt = $pdo->prepare($sql_limit);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Execute for Total Count (Approximate for complex queries)
// For simplicity, we'll run the query without limit to get count
// In production, this should be optimized
$stmt_count = $pdo->prepare($sql);
$stmt_count->execute($params);
$total_results = $stmt_count->rowCount();
$total_pages = ceil($total_results / $limit);

// Fetch Categories for Sidebar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0 rounded-4 sticky-top" style="top: 100px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i> Filters</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="GET" id="filterForm">
                        <?php if ($query): ?><input type="hidden" name="q"
                                value="<?php echo htmlspecialchars($query); ?>"><?php endif; ?>

                        <!-- Category -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Category</label>
                            <select class="form-select bg-light border-0" name="category" onchange="this.form.submit()">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Price Range ($)</label>
                            <div class="d-flex align-items-center">
                                <input type="number" class="form-control bg-light border-0" name="min_price"
                                    placeholder="Min" value="<?php echo $min_price; ?>">
                                <span class="mx-2 text-muted">-</span>
                                <input type="number" class="form-control bg-light border-0" name="max_price"
                                    placeholder="Max" value="<?php echo $max_price; ?>">
                            </div>
                        </div>

                        <!-- Delivery Time -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Delivery Time</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_time" value="" id="timeAny"
                                    <?php echo $delivery_time === '' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="timeAny">Any Time</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_time" value="1" id="time1"
                                    <?php echo $delivery_time == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="time1">Up to 24 hours</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_time" value="3" id="time3"
                                    <?php echo $delivery_time == 3 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="time3">Up to 3 days</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_time" value="7" id="time7"
                                    <?php echo $delivery_time == 7 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="time7">Up to 7 days</label>
                            </div>
                        </div>

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Rating</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" value="0" id="rateAny" <?php echo $min_rating == 0 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rateAny">Any Rating</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" value="4" id="rate4" <?php echo $min_rating == 4 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="rate4">4.0 & up</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill fw-bold">Apply Filters</button>
                            <a href="browse.php" class="btn btn-outline-secondary rounded-pill">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Search & Sort Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border">
                <h5 class="mb-0 fw-bold">
                    <?php echo $total_results; ?> Services Found
                    <?php if ($query): ?> for "<span
                            class="text-primary"><?php echo htmlspecialchars($query); ?></span>"<?php endif; ?>
                </h5>
                <div class="d-flex align-items-center">
                    <label class="me-2 small text-muted fw-bold">Sort by:</label>
                    <select class="form-select form-select-sm border-0 bg-light" name="sort"
                        onchange="document.getElementById('filterForm').submit();" form="filterForm"
                        style="width: 150px;">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High
                        </option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to
                            Low</option>
                    </select>
                </div>
            </div>

            <!-- Services Grid -->
            <?php if (empty($services)): ?>
                <div class="text-center py-5">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/no-search-result-3482167-2912153.png"
                        alt="No Results" style="width: 200px; opacity: 0.7;">
                    <h5 class="fw-bold mt-3 text-muted">No services found matching your criteria.</h5>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $service): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm border-0 service-card rounded-4 overflow-hidden hover-lift">
                                <a href="detail.php?id=<?php echo $service['id']; ?>" class="text-decoration-none text-dark">
                                    <div class="position-relative">
                                        <img src="/inkmybook/<?php echo htmlspecialchars($service['image']); ?>"
                                            class="card-img-top service-card-img"
                                            alt="<?php echo htmlspecialchars($service['title']); ?>"
                                            style="height: 180px; object-fit: cover;">
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-light text-dark shadow-sm rounded-pill">
                                                <i class="fas fa-clock me-1"></i> <?php echo $service['delivery_days']; ?>d
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2 text-primary fw-bold"
                                                style="width: 24px; height: 24px; font-size: 10px;">
                                                <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                            </div>
                                            <small
                                                class="text-muted text-truncate"><?php echo htmlspecialchars($service['provider_name']); ?></small>
                                        </div>
                                        <h6 class="card-title text-truncate mb-2 fw-bold"
                                            title="<?php echo htmlspecialchars($service['title']); ?>">
                                            <?php echo htmlspecialchars($service['title']); ?>
                                        </h6>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-star text-warning small me-1"></i>
                                            <span
                                                class="fw-bold small"><?php echo $service['avg_rating'] ? round($service['avg_rating'], 1) : 'New'; ?></span>
                                            <span class="text-muted small ms-1">(<?php echo $service['review_count']; ?>)</span>
                                        </div>
                                    </div>
                                    <div
                                        class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center px-3 pb-3 pt-0">
                                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Starting at</small>
                                        <span
                                            class="fw-bold fs-5 text-primary">$<?php echo number_format($service['price']); ?></span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link rounded-circle mx-1 border-0 shadow-sm <?php echo $page == $i ? 'bg-primary text-white' : 'bg-white text-dark'; ?>"
                                        href="?page=<?php echo $i; ?>&q=<?php echo urlencode($query); ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&delivery_time=<?php echo $delivery_time; ?>&rating=<?php echo $min_rating; ?>&sort=<?php echo $sort; ?>">
                                        <?php echo $i; ?>
                                    </a>
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