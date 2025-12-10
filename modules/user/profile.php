<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$errors = [];
$success = '';

// Handle Profile Photo Upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['profile_picture']['name'];
    $filetype = $_FILES['profile_picture']['type'];
    $filesize = $_FILES['profile_picture']['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $errors[] = "Invalid file format. Only JPG, PNG, WEBP allowed.";
    }

    if ($filesize > 5 * 1024 * 1024) {
        $errors[] = "File size is too large. Max 5MB.";
    }

    if (empty($errors)) {
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_dir = '../../uploads/profiles/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
            $image_path = 'uploads/profiles/' . $new_filename;

            // Update User Table
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$image_path, $user_id]);

            // Update Session (optional, if you store it there)
            $_SESSION['user_avatar'] = $image_path;

            $success = "Profile photo updated successfully!";
        } else {
            $errors[] = "Failed to upload image.";
        }
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_FILES['profile_picture'])) {
    $bio = sanitizeInput($_POST['bio']);
    $contact_details = sanitizeInput($_POST['contact_details']);

    // Provider specific fields
    $skills = isset($_POST['skills']) ? sanitizeInput($_POST['skills']) : '';
    $portfolio = isset($_POST['portfolio']) ? sanitizeInput($_POST['portfolio']) : '';
    $social_links = isset($_POST['social_links']) ? sanitizeInput($_POST['social_links']) : '';

    // Seeker specific fields
    $organization = isset($_POST['organization']) ? sanitizeInput($_POST['organization']) : '';

    try {
        // Check if profile exists
        $check = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
        $check->execute([$user_id]);

        if ($check->rowCount() > 0) {
            // Update
            $sql = "UPDATE profiles SET bio = ?, contact_details = ?, skills = ?, portfolio = ?, social_links = ?, organization = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bio, $contact_details, $skills, $portfolio, $social_links, $organization, $user_id]);
        } else {
            // Insert
            $sql = "INSERT INTO profiles (user_id, bio, contact_details, skills, portfolio, social_links, organization) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $bio, $contact_details, $skills, $portfolio, $social_links, $organization]);
        }

        $success = "Profile updated successfully!";
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Fetch current user and profile data
try {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4 text-center">
                <div class="card-body p-4">
                    <div class="avatar-ring rounded-circle p-1 mx-auto mb-3" style="width: 100px; height: 100px;">
                        <?php
                        $avatar = !empty($user['profile_picture']) ? '../../' . $user['profile_picture'] : 'https://placehold.co/100';
                        ?>
                        <img src="<?php echo htmlspecialchars($avatar); ?>"
                            class="rounded-circle w-100 h-100 object-fit-cover" alt="User">
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small mb-3 text-capitalize"><?php echo $user_role; ?></p>
                    <div class="d-grid">
                        <form id="photoForm" action="" method="POST" enctype="multipart/form-data">
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*"
                                style="display: none;" onchange="document.getElementById('photoForm').submit();">
                            <button type="button" class="btn btn-outline-primary rounded-pill btn-sm"
                                onclick="document.getElementById('profile_picture').click();">Change Photo</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="list-group list-group-flush rounded-3">
                    <a href="dashboard.php" class="list-group-item list-group-item-action py-3"><i
                            class="fas fa-tachometer-alt me-3 text-muted"></i> Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action py-3 active"><i
                            class="fas fa-user me-3"></i> Edit Profile</a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action py-3 text-danger"><i
                            class="fas fa-sign-out-alt me-3"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold">Edit Profile</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio / Overview</label>
                            <textarea class="form-control" id="bio" name="bio"
                                rows="4"><?php echo $profile['bio'] ?? ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="contact_details" class="form-label">Contact Details</label>
                            <input type="text" class="form-control" id="contact_details" name="contact_details"
                                value="<?php echo $profile['contact_details'] ?? ''; ?>">
                        </div>

                        <?php if ($user_role === 'provider'): ?>
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Provider Details</h5>
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills (comma separated)</label>
                                <input type="text" class="form-control" id="skills" name="skills"
                                    value="<?php echo $profile['skills'] ?? ''; ?>"
                                    placeholder="e.g. Book Editing, Web Design, PHP">
                            </div>
                            <div class="mb-3">
                                <label for="portfolio" class="form-label">Portfolio URL</label>
                                <input type="url" class="form-control" id="portfolio" name="portfolio"
                                    value="<?php echo $profile['portfolio'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="social_links" class="form-label">Social Links (JSON or text)</label>
                                <input type="text" class="form-control" id="social_links" name="social_links"
                                    value="<?php echo $profile['social_links'] ?? ''; ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ($user_role === 'seeker'): ?>
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Seeker Details</h5>
                            <div class="mb-3">
                                <label for="organization" class="form-label">Organization Name</label>
                                <input type="text" class="form-control" id="organization" name="organization"
                                    value="<?php echo $profile['organization'] ?? ''; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($user_role === 'provider'): ?>
                <!-- Reviews Section -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold">My Reviews</h4>
                        <?php
                        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE provider_id = ?");
                        $stmt->execute([$user_id]);
                        $stats = $stmt->fetch();
                        $avg_rating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
                        $total_reviews = $stats['total_reviews'];
                        ?>
                        <div class="text-warning">
                            <span class="fw-bold text-dark me-1"><?php echo $avg_rating; ?></span>
                            <i class="fas fa-star"></i>
                            <span class="text-muted small ms-1">(<?php echo $total_reviews; ?>)</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        $stmt = $pdo->prepare("
                        SELECT r.*, u.name as reviewer_name 
                        FROM reviews r 
                        JOIN users u ON r.reviewer_id = u.id 
                        WHERE r.provider_id = ? 
                        ORDER BY r.created_at DESC
                    ");
                        $stmt->execute([$user_id]);
                        $reviews = $stmt->fetchAll();

                        if (count($reviews) > 0):
                            foreach ($reviews as $review):
                                ?>
                                <div class="border-bottom pb-3 mb-3 last-no-border">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($review['reviewer_name']); ?></h6>
                                        <small
                                            class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="text-warning small mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++)
                                            echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </div>
                                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                                <?php
                            endforeach;
                        else:
                            ?>
                            <p class="text-muted text-center mb-0">No reviews yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>