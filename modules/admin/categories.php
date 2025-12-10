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
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Category deleted successfully!');
        redirect('categories.php');
    } catch (PDOException $e) {
        flash('error', 'Error deleting category: ' . $e->getMessage(), 'danger');
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    if (empty($name)) {
        $errors[] = "Category name is required";
    }

    if (empty($errors)) {
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $id]);
                flash('success', 'Category updated successfully!');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                flash('success', 'Category added successfully!');
            }
            redirect('categories.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

// Fetch Category for Edit
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_category = $stmt->fetch();
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
                <a href="categories.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-list me-2"></i> Categories
                </a>
                <a href="subcategories.php" class="list-group-item list-group-item-action">
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
            <h2 class="fw-bold mb-4">Manage Categories</h2>

            <?php flash('success'); ?>
            <?php flash('error'); ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                    </h5>
                    <form action="" method="POST" class="row g-3">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                        <?php endif; ?>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="name" placeholder="Category Name"
                                value="<?php echo $edit_category['name'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit"
                                class="btn btn-primary w-100"><?php echo $edit_category ? 'Update' : 'Add Category'; ?></button>
                            <?php if ($edit_category): ?>
                                <a href="categories.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
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
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                                        <td>
                                            <a href="categories.php?edit=<?php echo $cat['id']; ?>"
                                                class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                            <a href="categories.php?delete=<?php echo $cat['id']; ?>"
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