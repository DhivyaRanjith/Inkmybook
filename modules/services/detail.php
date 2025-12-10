<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('browse.php');
}

$service_id = $_GET['id'];

// Fetch Service Details
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name, sub.name as subcategory_name, u.name as provider_name, u.email as provider_email, p.bio, p.skills, u.created_at as member_since, p.user_id as provider_id
    FROM services s 
    JOIN subcategories sub ON s.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    JOIN users u ON s.provider_id = u.id
    LEFT JOIN profiles p ON u.id = p.user_id
    WHERE s.id = ?
");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    redirect('browse.php');
}

// Fetch Reviews Stats
$stmt_reviews = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE service_id = ?");
$stmt_reviews->execute([$service_id]);
$review_stats = $stmt_reviews->fetch();
$avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
$total_reviews = $review_stats['total_reviews'];

include '../../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4 animate-slide-up">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="browse.php" class="text-decoration-none text-muted">Services</a></li>
            <li class="breadcrumb-item"><a href="browse.php?category=<?php echo $service['subcategory_id']; ?>"
                    class="text-decoration-none text-muted"><?php echo htmlspecialchars($service['category_name']); ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($service['subcategory_name']); ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <!-- Left Column: Details -->
        <div class="col-lg-8 animate-slide-up delay-100">
            <h1 class="fw-bold mb-4 display-5">
                <?php echo htmlspecialchars($service['title']); ?>
            </h1>

            <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
                <div class="avatar-ring rounded-circle me-3 p-1 bg-white">
                    <img src="https://placehold.co/60" class="rounded-circle" alt="Provider">
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <?php echo htmlspecialchars($service['provider_name']); ?>
                    </h6>
                    <div class="d-flex align-items-center mt-1">
                        <i class="fas fa-star text-warning small me-1"></i>
                        <span class="fw-bold small me-1"><?php echo $avg_rating > 0 ? $avg_rating : 'New'; ?></span>
                        <span class="text-muted small me-3">(<?php echo $total_reviews; ?> reviews)</span>
                        <span class="text-muted small border-start ps-3">Level 1 Seller</span>
                    </div>
                </div>
            </div>

            <div class="position-relative mb-5 rounded-4 overflow-hidden shadow-sm">
                <img src="/inkmybook/<?php echo htmlspecialchars($service['image']); ?>" class="img-fluid w-100"
                    alt="Service Image" style="object-fit: cover; max-height: 500px;">
            </div>

            <h3 class="fw-bold mb-4">About This Gig</h3>
            <div class="mb-5 text-muted lh-lg">
                <?php echo nl2br(htmlspecialchars($service['description'])); ?>
            </div>

            <!-- Reviews Section -->
            <div class="mb-5">
                <h3 class="fw-bold mb-4">Reviews</h3>
                <?php
                $stmt_list = $pdo->prepare("
                    SELECT r.*, u.name as reviewer_name 
                    FROM reviews r 
                    JOIN users u ON r.reviewer_id = u.id 
                    WHERE r.service_id = ? 
                    ORDER BY r.created_at DESC
                ");
                $stmt_list->execute([$service_id]);
                $reviews = $stmt_list->fetchAll();

                if (count($reviews) > 0):
                    foreach ($reviews as $review):
                        ?>
                        <div class="card border-0 shadow-sm mb-3 rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($review['reviewer_name']); ?></h6>
                                    <small class="text-muted"><?php echo time_elapsed_string($review['created_at']); ?></small>
                                </div>
                                <div class="text-warning small mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++)
                                        echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                    <?php
                    endforeach;
                else:
                    ?>
                    <div class="alert alert-light border text-center text-muted">No reviews yet for this service.</div>
                <?php endif; ?>
            </div>

            <h4 class="fw-bold mb-4">About The Seller</h4>
            <div class="card border-0 shadow-sm mb-5 rounded-4 bg-light">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <img src="https://placehold.co/100" class="rounded-circle me-4 shadow-sm" alt="Provider">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($service['provider_name']); ?>
                            </h5>
                            <p class="text-muted small mb-2">Member since
                                <?php echo date('M Y', strtotime($service['member_since'])); ?>
                            </p>
                            <button
                                onclick="contactSeller(<?php echo $service['provider_id']; ?>, 'service', <?php echo $service['id']; ?>)"
                                class="btn btn-sm btn-outline-dark rounded-pill px-3">Contact Me</button>
                        </div>
                    </div>
                    <p class="text-muted mb-4">
                        <?php echo nl2br(htmlspecialchars($service['bio'] ?? 'No bio available.')); ?>
                    </p>
                    <?php if (!empty($service['skills'])): ?>
                        <div class="border-top pt-3">
                            <strong class="d-block mb-2 text-dark">Skills</strong>
                            <?php
                            $skills = explode(',', $service['skills']);
                            foreach ($skills as $skill) {
                                echo '<span class="badge bg-white text-dark border me-2 mb-2 px-3 py-2 rounded-pill shadow-sm fw-normal">' . trim($skill) . '</span>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Pricing Card -->
        <div class="col-lg-4 animate-slide-up delay-200">
            <div class="card shadow-lg border-0 sticky-top rounded-4 overflow-hidden" style="top: 100px; z-index: 10;">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-muted">Standard</h5>
                        <h3 class="fw-bold mb-0 text-dark">
                            $<?php echo number_format($service['price']); ?></h3>
                    </div>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4 fw-medium">
                        <?php echo htmlspecialchars($service['title']); ?>
                    </p>

                    <div class="d-flex justify-content-between mb-4 fw-bold small text-dark">
                        <span><i class="far fa-clock me-2 text-primary"></i><?php echo $service['delivery_days']; ?>
                            Days Delivery</span>
                        <span><i class="fas fa-sync me-2 text-primary"></i><?php echo $service['revisions']; ?>
                            Revisions</span>
                    </div>

                    <ul class="list-unstyled mb-4 text-muted small">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Source
                            File</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> High
                            Resolution</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>
                            Commercial Use</li>
                    </ul>

                    <div class="d-grid gap-3">
                        <a href="../orders/create.php?service_id=<?php echo $service['id']; ?>"
                            class="btn btn-success btn-lg fw-bold rounded-pill shadow-sm">
                            Continue ($<?php echo number_format($service['price']); ?>)
                        </a>
                        <button
                            onclick="contactSeller(<?php echo $service['provider_id']; ?>, 'service', <?php echo $service['id']; ?>)"
                            class="btn btn-outline-secondary rounded-pill fw-bold">Contact
                            Seller</button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-3 border-top">
                    <small class="text-muted"><i class="fas fa-shield-alt me-1"></i> 100% Secure
                        Payment</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function contactSeller(userId, type, id) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = '/inkmybook/modules/auth/login.php';
            return;
        <?php endif; ?>

        const formData = new FormData();
        formData.append('action', 'start_conversation');
        formData.append('other_user_id', userId);
        formData.append('entity_type', type);
        formData.append('entity_id', id);

        fetch('/inkmybook/modules/messaging/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '/inkmybook/modules/messaging/inbox.php?conversation_id=' + data.conversation_id;
                } else {
                    alert('Error starting conversation: ' + data.message);
                }
            });
    }
</script>

<?php include '../../includes/footer.php'; ?>