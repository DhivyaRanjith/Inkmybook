<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'seeker') {
    redirect('/inkmybook/index.php');
}

$errors = [];
$title = $description = $budget = $deadline = $category_id = $subcategory_id = '';

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch Subcategories (for JS filtering)
$subcategories = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll();
$subcategories_json = json_encode($subcategories) ?: '[]';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $description = sanitizeInput($_POST['description']);
    $budget = $_POST['budget'];
    $deadline = $_POST['deadline'];

    // Validation
    if (empty($title))
        $errors[] = "Title is required";
    if (empty($subcategory_id))
        $errors[] = "Subcategory is required";
    if (empty($budget))
        $errors[] = "Budget is required";
    if (empty($deadline))
        $errors[] = "Deadline is required";

    // File Upload (Optional)
    $attachment_path = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip'];
        $filename = $_FILES['attachment']['name'];
        $filesize = $_FILES['attachment']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file format. Allowed: JPG, PNG, PDF, DOC, ZIP.";
        }

        if ($filesize > 10 * 1024 * 1024) {
            $errors[] = "File size is too large. Max 10MB.";
        }

        if (empty($errors)) {
            $new_filename = uniqid() . '_task.' . $ext;
            $upload_dir = '../../uploads/tasks/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $new_filename)) {
                $attachment_path = 'uploads/tasks/' . $new_filename;
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (seeker_id, subcategory_id, title, description, budget, deadline, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subcategory_id, $title, $description, $budget, $deadline, $attachment_path]);
            flash('success', 'Job request posted successfully!');
            redirect('my_tasks.php');
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
                <h2 class="fw-bold">Post a Job Request</h2>
                <p class="text-muted">Get offers from top talent for your project.</p>
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
                            <label for="title" class="form-label fw-bold">Job Title</label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title"
                                value="<?php echo $title; ?>" placeholder="e.g. Need a logo design for my startup"
                                required>
                            <div class="form-text">Be specific so the right freelancers can find you.</div>
                        </div>

                        <!-- Category -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-bold">Category</label>
                                <select class="form-select form-select-lg" id="category_id" name="category_id" required
                                    onchange="filterSubcategories()">
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
                                placeholder="Describe your project requirements, goals, and any specific details..."
                                required><?php echo $description; ?></textarea>
                        </div>

                        <!-- Budget & Deadline -->
                        <div class="card bg-light border-0 rounded-3 mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Budget & Timeline</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="budget"
                                            class="form-label small text-muted text-uppercase fw-bold">Budget
                                            ($)</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white border-end-0 text-muted">$</span>
                                            <input type="number" class="form-control border-start-0 ps-0" id="budget"
                                                name="budget" value="<?php echo $budget; ?>" min="1" step="0.01"
                                                placeholder="0.00" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="deadline"
                                            class="form-label small text-muted text-uppercase fw-bold">Deadline</label>
                                        <input type="date" class="form-control form-control-lg" id="deadline"
                                            name="deadline" value="<?php echo $deadline; ?>"
                                            min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attachment -->
                        <div class="mb-5">
                            <label for="attachment" class="form-label fw-bold">Attachment (Optional)</label>
                            <div class="border-2 border-dashed border rounded-3 p-4 text-center bg-light"
                                style="border-style: dashed !important; border-color: #dee2e6 !important;">
                                <i class="fas fa-paperclip fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-2 small">Attach reference files or briefs</p>
                                <input type="file" class="form-control form-control-sm w-75 mx-auto" id="attachment"
                                    name="attachment">
                                <div class="form-text mt-2">Allowed: JPG, PNG, PDF, DOC, ZIP. Max 10MB.</div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow-hover">Post Job
                                Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const subcategories = <?php echo $subcategories_json; ?>;

    function filterSubcategories() {
        const categoryId = document.getElementById('category_id').value;
        const subcategorySelect = document.getElementById('subcategory_id');

        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        subcategorySelect.disabled = true;

        if (categoryId) {
            const filtered = subcategories.filter(sub => sub.category_id == categoryId);

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
</script>

<?php include '../../includes/footer.php'; ?>