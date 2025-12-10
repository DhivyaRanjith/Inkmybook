<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'provider') {
    redirect('/inkmybook/index.php');
}

$errors = [];
$title = $description = $price = $delivery_days = $revisions = $category_id = $subcategory_id = '';

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch Subcategories (for JS filtering)
$subcategories = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories_json = json_encode($subcategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $description = sanitizeInput($_POST['description']);
    $price = $_POST['price'];
    $delivery_days = $_POST['delivery_days'];
    $revisions = $_POST['revisions'];

    // Validation
    if (empty($title))
        $errors[] = "Title is required";
    if (empty($subcategory_id))
        $errors[] = "Subcategory is required";
    if (empty($price))
        $errors[] = "Price is required";
    if (empty($delivery_days))
        $errors[] = "Delivery days is required";

    // Image Upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetype = $_FILES['image']['type'];
        $filesize = $_FILES['image']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file format. Only JPG, PNG, WEBP allowed.";
        }

        if ($filesize > 5 * 1024 * 1024) {
            $errors[] = "File size is too large. Max 5MB.";
        }

        if (empty($errors)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_dir = '../../uploads/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_path = 'uploads/' . $new_filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        $errors[] = "Service image is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO services (provider_id, subcategory_id, title, description, price, delivery_days, revisions, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subcategory_id, $title, $description, $price, $delivery_days, $revisions, $image_path]);
            flash('success', 'Service created successfully!');
            redirect('my_gigs.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 animate-slide-up">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Create a New Gig</h2>
                <p class="text-muted">Showcase your talent and start earning.</p>
            </div>

            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-body p-5">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Gig Title</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted">I will</span>
                                <input type="text" class="form-control border-start-0 ps-0" id="title" name="title"
                                    value="<?php echo $title; ?>" placeholder="do something I'm really good at"
                                    required>
                            </div>
                            <div class="form-text">Keep it short and catchy.</div>
                        </div>

                        <!-- Category -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-bold">Category</label>
                                <select class="form-select form-select-lg" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subcategory_id" class="form-label fw-bold">Subcategory</label>
                                <select class="form-select form-select-lg" id="subcategory_id" name="subcategory_id"
                                    required disabled>
                                    <option value="">Select Subcategory</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="6"
                                placeholder="Describe your service in detail..."
                                required><?php echo $description; ?></textarea>
                        </div>

                        <!-- Pricing & Delivery -->
                        <div class="card bg-light border-0 rounded-3 mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Pricing & Delivery</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="price"
                                            class="form-label small text-muted text-uppercase fw-bold">Price ($)</label>
                                        <input type="number" class="form-control form-control-lg" id="price"
                                            name="price" value="<?php echo $price; ?>" min="1" step="0.01"
                                            placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="delivery_days"
                                            class="form-label small text-muted text-uppercase fw-bold">Delivery
                                            (Days)</label>
                                        <input type="number" class="form-control form-control-lg" id="delivery_days"
                                            name="delivery_days" value="<?php echo $delivery_days; ?>" min="1"
                                            placeholder="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="revisions"
                                            class="form-label small text-muted text-uppercase fw-bold">Revisions</label>
                                        <input type="number" class="form-control form-control-lg" id="revisions"
                                            name="revisions" value="<?php echo $revisions; ?>" min="0" placeholder="0"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="mb-5">
                            <label for="image" class="form-label fw-bold">Gig Image</label>
                            <div class="border-2 border-dashed border rounded-3 p-5 text-center bg-light"
                                style="border-style: dashed !important; border-color: #dee2e6 !important;">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Drag & drop a photo or browse</p>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*"
                                    required>
                                <div class="form-text mt-2">Recommended size: 1280x769 px. Max 5MB.</div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow-hover">Publish
                                Gig</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const allSubcategories = <?php echo $subcategories_json; ?>;
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        function filterSubcategories() {
            const categoryId = categorySelect.value;

            // Reset
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            subcategorySelect.disabled = true;

            if (categoryId) {
                // Filter subcategories
                const filtered = allSubcategories.filter(sub => sub.category_id == categoryId);

                if (filtered.length > 0) {
                    filtered.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subcategorySelect.appendChild(option);
                    });
                    subcategorySelect.disabled = false;
                }
            }
        }

        // Attach event listener
        if (categorySelect) {
            categorySelect.addEventListener('change', filterSubcategories);

            // Trigger on load if value exists
            if (categorySelect.value) {
                filterSubcategories();
                const selectedSubId = "<?php echo $subcategory_id; ?>";
                if (selectedSubId) {
                    setTimeout(() => {
                        subcategorySelect.value = selectedSubId;
                    }, 100);
                }
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>