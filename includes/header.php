<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkMyBook - Hire Expert Freelancers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/inkmybook/assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="/inkmybook/assets/js/main.js"></script>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid ps-2 pe-lg-5">
            <a class="navbar-brand" href="/inkmybook/index.php">
                <img src="/inkmybook/assets/img/logo.png" alt="InkMyBook" height="132" class="me-n3">
                InkMyBook
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/inkmybook/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/inkmybook/modules/services/browse.php">Browse Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/inkmybook/modules/tasks/browse.php">Browse Jobs</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        // Redirect support users to their dashboard if they land here
                        // (Disabled to allow browsing)
                        ?>
                        <li class="nav-item ms-2">
                            <a class="nav-link position-relative" href="/inkmybook/modules/messaging/inbox.php">
                                <i class="far fa-comment-dots fa-lg"></i>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="msgBadge" style="display: none;">
                                    0
                                </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <div class="avatar-ring rounded-circle p-1 me-2">
                                    <img src="https://placehold.co/32" class="rounded-circle" alt="User">
                                </div>
                                <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-md border-0">
                                <?php if ($_SESSION['user_role'] == 'support'): ?>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/support_dashboard/index.php"><i
                                                class="fas fa-headset me-2 text-muted"></i> Support Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/user/dashboard.php"><i
                                                class="fas fa-tachometer-alt me-2 text-muted"></i> Dashboard</a></li>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/user/profile.php"><i
                                                class="fas fa-user me-2 text-muted"></i> Profile</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_role'] == 'seeker'): ?>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/tasks/my_tasks.php"><i
                                                class="fas fa-list me-2 text-muted"></i> My Jobs</a></li>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/orders/my_orders.php"><i
                                                class="fas fa-shopping-bag me-2 text-muted"></i> My Orders</a></li>
                                <?php elseif ($_SESSION['user_role'] == 'provider'): ?>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/services/my_gigs.php"><i
                                                class="fas fa-briefcase me-2 text-muted"></i> My Services</a></li>
                                    <li><a class="dropdown-item" href="/inkmybook/modules/orders/manage_orders.php"><i
                                                class="fas fa-tasks me-2 text-muted"></i> Manage Orders</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="/inkmybook/modules/auth/logout.php"><i
                                            class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link fw-bold" href="/inkmybook/modules/auth/login.php">Log In</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                                href="/inkmybook/modules/auth/register.php">Join Now</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">

        <?php if (isset($_SESSION['user_id'])): ?>
            <script>
                function checkUnreadMessages() {
                    fetch('/inkmybook/modules/messaging/api.php?action=check_new')
                        .then(response => response.json())
                        .then(data => {
                            const badge = document.getElementById('msgBadge');
                            if (data.status === 'success' && data.count > 0) {
                                badge.innerText = data.count;
                                badge.style.display = 'block';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                }
                setInterval(checkUnreadMessages, 5000);
                checkUnreadMessages();
            </script>
        <?php endif; ?>