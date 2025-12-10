<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

if (!isset($_GET['order_id'])) {
    die("Invalid Order ID");
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch Order Details
$stmt = $pdo->prepare("
    SELECT o.*, s.title as service_title, t.title as task_title, 
    u_buyer.name as buyer_name, u_buyer.email as buyer_email,
    u_provider.name as provider_name, u_provider.email as provider_email,
    p.txn_id, p.created_at as payment_date
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    LEFT JOIN tasks t ON o.task_id = t.id
    JOIN users u_buyer ON o.buyer_id = u_buyer.id
    JOIN users u_provider ON o.provider_id = u_provider.id
    LEFT JOIN payments p ON o.id = p.order_id AND p.status = 'success'
    WHERE o.id = ? AND (o.buyer_id = ? OR o.provider_id = ?)
");
$stmt->execute([$order_id, $user_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or access denied.");
}

$invoice_no = "INV-" . str_pad($order['id'], 6, "0", STR_PAD_LEFT);
$date = date('M d, Y', strtotime($order['created_at']));
$item_name = $order['service_title'] ?: $order['task_title'] ?: 'Custom Order';
$amount = $order['price'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_no; ?> - InkMyBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            color: #333;
            font-family: 'Inter', sans-serif;
        }

        .invoice-box {
            max-width: 800px;
            margin: 50px auto;
            padding: 50px;
            background: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .logo {
            font-weight: 800;
            font-size: 24px;
            color: #0d6efd;
            text-decoration: none;
        }

        .table-invoice th {
            background: #f8f9fa;
            font-weight: 600;
        }

        @media print {
            body {
                background: #fff;
            }

            .invoice-box {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-box">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="#" class="logo">InkMyBook.</a>
            <div class="text-end">
                <h2 class="fw-bold mb-1 text-uppercase text-muted opacity-25">Invoice</h2>
                <p class="mb-0 fw-bold text-dark">#<?php echo $invoice_no; ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h6 class="text-muted text-uppercase small fw-bold mb-3">Billed To:</h6>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($order['buyer_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($order['buyer_email']); ?></p>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted text-uppercase small fw-bold mb-3">Payment Details:</h6>
                <p class="mb-1"><span class="text-muted">Date:</span> <strong><?php echo $date; ?></strong></p>
                <p class="mb-1"><span class="text-muted">Transaction ID:</span>
                    <strong><?php echo $order['txn_id'] ?? 'N/A'; ?></strong></p>
                <p class="mb-0"><span class="text-muted">Status:</span> <span
                        class="badge bg-success bg-opacity-10 text-success">Paid</span></p>
            </div>
        </div>

        <table class="table table-invoice mb-5">
            <thead>
                <tr>
                    <th class="py-3 ps-4">Description</th>
                    <th class="py-3 text-end pe-4">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-4 ps-4">
                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item_name); ?></h6>
                        <small class="text-muted">Order #<?php echo $order['id']; ?></small>
                    </td>
                    <td class="py-4 text-end pe-4 fw-bold">$<?php echo number_format($amount, 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="pt-4 text-end fw-bold">Total</td>
                    <td class="pt-4 text-end pe-4 fw-bold fs-5 text-primary">$<?php echo number_format($amount, 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="border-top pt-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">Thank you for your business!</p>
                    <p class="text-muted small">If you have any questions, please contact support.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="fw-bold mb-0">InkMyBook Inc.</p>
                    <p class="text-muted small">123 Freelance Ave, Digital City</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold"><i
                    class="fas fa-print me-2"></i> Print Invoice</button>
            <a href="../orders/view.php?id=<?php echo $order_id; ?>"
                class="btn btn-outline-secondary rounded-pill px-4 fw-bold ms-2">Back to Order</a>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>