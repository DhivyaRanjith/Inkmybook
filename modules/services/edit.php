<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'provider') {
    redirect('/inkmybook/index.php');
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND provider_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$service = $stmt->fetch();

if (!$service) {
    flash('error', 'Service not found or access denied.', 'danger');
    redirect('my_gigs.php');
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $subcategory_id = $_POST['subcategory_id'];
    $price = $_POST['price'];
    $delivery_days = $_POST['delivery_days'];
    $revisions = $_POST['revisions'];
    $description = $_POST['description'];

    // Handle Image Upload
    $image_path = $service['image']; // Default to existing
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../../assets/uploads/' . $new_filename);
            $image_path = 'assets/uploads/' . $new_filename;
        }
    }

    $stmt = $pdo->prepare("UPDATE services SET title=?, subcategory_id=?, price=?, delivery_days=?, revisions=?, description=?, image=? WHERE id=?");
    $stmt->execute([$title, $subcategory_id, $price, $delivery_days, $revisions, $description, $image_path, $id]);

    flash('success', 'Service updated successfully!');
    redirect('my_gigs.php');
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <h3 class="fw-bold mb-4">Edit Gig</h3>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gig Title</label>
                            <input type="text" name="title" class="form-control"
                                value="<?php echo htmlspecialchars($service['title']); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category</label>
                                <select class="form-select" id="category_select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Subcategory</label>
                                <select name="subcategory_id" class="form-select" id="subcategory_select" required>
                                    <option value="<?php echo $service['subcategory_id']; ?>" selected>Current
                                        Subcategory</option>
                                    <!-- Populated via JS -->
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Price ($)</label>
                                <input type="number" name="price" class="form-control"
                                    value="<?php echo $service['price']; ?>" min="5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Delivery (Days)</label>
                                <input type="number" name="delivery_days" class="form-control"
                                    value="<?php echo $service['delivery_days']; ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Revisions</label>
                                <input type="number" name="revisions" class="form-control"
                                    value="<?php echo $service['revisions']; ?>" min="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="6"
                                required><?php echo htmlspecialchars($service['description']); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Gig Image</label>
                            <input type="file" name="image" class="form-control mb-2">
                            <small class="text-muted">Current: <a href="/inkmybook/<?php echo $service['image']; ?>"
                                    target="_blank">View Image</a></small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="my_gigs.php" class="btn btn-outline-secondary rounded-pill">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Update Gig</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('category_select').addEventListener('change', function () {
        const catId = this.value;
        const subSelect = document.getElementById('subcategory_select');
        subSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`../../api/get_subcategories.php?category_id=${catId}`)
            .then(response => response.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">Select Subcategory</option>';
                data.forEach(sub => {
                    subSelect.innerHTML += `<option value="${sub.id}">${sub.name}</option>`;
                });
            });
    });
</script>

<?php include '../../includes/footer.php'; ?>