<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('browse.php');
}

$task_id = $_GET['id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Handle Bid Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_bid'])) {
    requireLogin();
    if ($user_role !== 'provider') {
        flash('error', 'Only providers can place bids.', 'danger');
    } else {
        $amount = $_POST['amount'];
        $message = sanitizeInput($_POST['message']);

        // Check if already bid
        $check = $pdo->prepare("SELECT id FROM bids WHERE task_id = ? AND provider_id = ?");
        $check->execute([$task_id, $user_id]);

        if ($check->rowCount() > 0) {
            flash('error', 'You have already placed a bid on this task.', 'warning');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO bids (task_id, provider_id, amount, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$task_id, $user_id, $amount, $message]);
                flash('success', 'Bid placed successfully!');
            } catch (PDOException $e) {
                flash('error', 'Error placing bid: ' . $e->getMessage(), 'danger');
            }
        }
    }
    redirect("view.php?id=$task_id");
}

// Handle Assign Provider (Seeker Only)
if (isset($_GET['assign']) && $user_role === 'seeker') {
    $bid_id = $_GET['assign'];
    try {
        // Verify task ownership
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND seeker_id = ?");
        $stmt->execute([$task_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            // Get Bid Details
            $bid_stmt = $pdo->prepare("SELECT * FROM bids WHERE id = ?");
            $bid_stmt->execute([$bid_id]);
            $bid = $bid_stmt->fetch();

            if ($bid) {
                // Update Task Status
                $update = $pdo->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ?");
                $update->execute([$task_id]);

                // Create Order (Module 5 integration)
                $order_stmt = $pdo->prepare("INSERT INTO orders (buyer_id, provider_id, task_id, amount, status, delivery_date) VALUES (?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY))");
                $order_stmt->execute([$user_id, $bid['provider_id'], $task_id, $bid['amount']]);

                flash('success', 'Provider assigned and order created!');
            }
        }
    } catch (PDOException $e) {
        flash('error', 'Error assigning provider: ' . $e->getMessage(), 'danger');
    }
    redirect("view.php?id=$task_id");
}

// Fetch Task Details
$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, sub.name as subcategory_name, u.name as seeker_name, u.created_at as member_since
    FROM tasks t 
    JOIN subcategories sub ON t.subcategory_id = sub.id 
    JOIN categories c ON sub.category_id = c.id 
    JOIN users u ON t.seeker_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    redirect('browse.php');
}

// Fetch Bids
$bids_stmt = $pdo->prepare("
    SELECT b.*, u.name as provider_name, p.user_id as provider_id
    FROM bids b 
    JOIN users u ON b.provider_id = u.id 
    LEFT JOIN profiles p ON u.id = p.user_id
    WHERE b.task_id = ? 
    ORDER BY b.created_at DESC
");
$bids_stmt->execute([$task_id]);
$bids = $bids_stmt->fetchAll();

// Calculate Stats
$bid_count = count($bids);
$avg_bid = 0;
if ($bid_count > 0) {
    $total_bid_amount = array_sum(array_column($bids, 'amount'));
    $avg_bid = $total_bid_amount / $bid_count;
}

include '../../includes/header.php';
?>

<style>
    .nav-tabs .nav-link {
        color: #495057;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background: none;
    }

    .client-verification i {
        width: 20px;
        text-align: center;
    }
</style>

<div class="bg-white border-bottom mb-4">
    <div class="container pt-5">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($task['title']); ?></h2>
                <div class="d-flex align-items-center gap-2">
                    <?php
                    $status_class = 'secondary';
                    if ($task['status'] == 'open')
                        $status_class = 'success';
                    if ($task['status'] == 'assigned')
                        $status_class = 'primary';
                    ?>
                    <span
                        class="badge bg-<?php echo $status_class; ?>-subtle text-<?php echo $status_class; ?> px-3 py-1 rounded-pill text-uppercase small fw-bold"><?php echo $task['status']; ?></span>
                </div>
            </div>
            <div class="d-flex gap-5 text-end">
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Bids</div>
                    <div class="h4 fw-bold mb-0"><?php echo $bid_count; ?></div>
                </div>
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Avg Bid (INR)</div>
                    <div class="h4 fw-bold mb-0">₹<?php echo number_format($avg_bid); ?></div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details"
                    type="button" role="tab">Details</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals"
                    type="button" role="tab">Proposals</button>
            </li>
        </ul>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <div class="tab-content" id="myTabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold mb-0">Project Details</h4>
                                <div class="fw-bold fs-5">₹<?php echo number_format($task['budget']); ?> INR</div>
                            </div>

                            <p class="text-muted lh-lg mb-4">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?></p>

                            <h6 class="fw-bold mb-3">Skills Required</h6>
                            <div class="d-flex gap-2 flex-wrap mb-4">
                                <span
                                    class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?php echo htmlspecialchars($task['category_name']); ?></span>
                                <span
                                    class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?php echo htmlspecialchars($task['subcategory_name']); ?></span>
                            </div>

                            <?php if (!empty($task['attachment'])): ?>
                                <hr>
                                <h6 class="fw-bold mb-3">Attachments</h6>
                                <a href="/inkmybook/<?php echo htmlspecialchars($task['attachment']); ?>"
                                    class="text-decoration-none" target="_blank">
                                    <i class="fas fa-paperclip me-2"></i> Download Attachment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Proposals Tab -->
                <div class="tab-pane fade" id="proposals" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-4">Proposals (<?php echo $bid_count; ?>)</h4>

                            <?php if ($user_role === 'provider' && $task['status'] === 'open'): ?>
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold mb-3">Place a Bid on this Project</h5>
                                        <form action="" method="POST">
                                            <input type="hidden" name="place_bid" value="1">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Bid Amount (INR)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text border-0 bg-white">₹</span>
                                                        <input type="number" class="form-control border-0" name="amount"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Delivery in (Days)</label>
                                                    <input type="number" class="form-control border-0" placeholder="7"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Describe your proposal</label>
                                                <textarea class="form-control border-0" name="message" rows="4"
                                                    required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary fw-bold px-4">Place Bid</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($bids as $bid): ?>
                                <div class="border-bottom py-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center fw-bold text-secondary"
                                                style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($bid['provider_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($bid['provider_name']); ?>
                                                </div>
                                                <div class="small text-muted">India</div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">₹<?php echo number_format($bid['amount']); ?> INR</div>
                                            <div class="small text-muted">
                                                <?php echo date('d M, Y', strtotime($bid['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($bid['message'])); ?></p>

                                    <?php if ($user_role === 'seeker' && $task['seeker_id'] == $user_id && $task['status'] == 'open'): ?>
                                        <a href="view.php?id=<?php echo $task['id']; ?>&assign=<?php echo $bid['id']; ?>"
                                            class="btn btn-sm btn-outline-success rounded-pill fw-bold"
                                            onclick="return confirm('Hire this freelancer?')">
                                            Hire Freelancer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Client Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">About the Client</h6>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-map-marker-alt text-muted"></i>
                        <span>India</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-user text-muted"></i>
                        <div class="text-warning small">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="text-muted ms-1 text-dark">4.9 (20 reviews)</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="fas fa-clock text-muted"></i>
                        <span class="small text-muted">Member since
                            <?php echo date('M d, Y', strtotime($task['member_since'])); ?></span>
                    </div>

                    <h6 class="fw-bold mb-3">Client Verification</h6>
                    <div class="client-verification d-flex flex-column gap-2 text-muted small">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-id-card text-success"></i> Identity verified
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-credit-card text-success"></i> Payment verified
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-envelope text-success"></i> Email verified
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-phone text-success"></i> Phone verified
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>