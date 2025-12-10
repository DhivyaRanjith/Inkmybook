<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/inkmybook/index.php');
}

$errors = [];
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Subcategory deleted successfully!');
        redirect('subcategories.php');
    } catch (PDOException $e) {
        flash('error', 'Error deleting subcategory: ' . $e->getMessage(), 'danger');
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $category_id = $_POST['category_id'];
    $slug = strtolower(str_replace(' ', '-', $name));
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    if (empty($name))
        $errors[] = "Subcategory name is required";
    if (empty($category_id))
        $errors[] = "Parent Category is required";

    if (empty($errors)) {
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE subcategories SET name = ?, slug = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $category_id, $id]);
                flash('success', 'Subcategory updated successfully!');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO subcategories (name, slug, category_id) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $category_id]);
                flash('success', 'Subcategory added successfully!');
            }
            redirect('subcategories.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Categories for Dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch Subcategories with Category Name
$sql = "SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY s.id DESC";
$subcategories = $pdo->query($sql)->fetchAll();

// Fetch Subcategory for Edit
$edit_subcategory = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_subcategory = $stmt->fetch();
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group shadow-sm border-0 rounded-3 mb-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="categories.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-list me-2"></i> Categories
                </a>
                <a href="subcategories.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-layer-group me-2"></i> Subcategories
                </a>
                <a href="users.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a href="services.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-briefcase me-2"></i> Services
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <h2 class="fw-bold mb-4">Manage Subcategories</h2>

            <?php flash('success'); ?>
            <?php flash('error'); ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <?php echo $edit_subcategory ? 'Edit Subcategory' : 'Add New Subcategory'; ?></h5>
                    <form action="" method="POST" class="row g-3">
                        <?php if ($edit_subcategory): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_subcategory['id']; ?>">
                        <?php endif; ?>

                        <div class="col-md-4">
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_subcategory && $edit_subcategory['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <input type="text" class="form-control" name="name" placeholder="Subcategory Name"
                                value="<?php echo $edit_subcategory['name'] ?? ''; ?>" required>
                        </div>

                        <div class="col-md-3">
                            <button type="submit"
                                class="btn btn-primary w-100"><?php echo $edit_subcategory ? 'Update' : 'Add'; ?></button>
                            <?php if ($edit_subcategory): ?>
                                <a href="subcategories.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    <th>Slug</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subcategories as $sub): ?>
                                    <tr>
                                        <td><?php echo $sub['id']; ?></td>
                                        <td><span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($sub['category_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                        <td><?php echo htmlspecialchars($sub['slug']); ?></td>
                                        <td>
                                            <a href="subcategories.php?edit=<?php echo $sub['id']; ?>"
                                                class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                            <a href="subcategories.php?delete=<?php echo $sub['id']; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>