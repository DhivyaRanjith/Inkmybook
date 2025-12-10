<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/oauth.php';
require_once '../../includes/functions.php';

if (isLoggedIn()) {
    redirect('/inkmybook/index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email))
        $errors[] = "Email is required";
    if (empty($password))
        $errors[] = "Password is required";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'blocked') {
                    $errors[] = "Your account has been blocked. Please contact support.";
                } else {
                    // Login Success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];

                    flash('login_success', 'Welcome back, ' . $user['name'] . '!');

                    // Role-based redirection
                    if ($user['role'] === 'provider') {
                        redirect('/inkmybook/modules/user/dashboard.php');
                    } elseif ($user['role'] === 'seeker') {
                        redirect('/inkmybook/modules/user/dashboard.php');
                    } elseif ($user['role'] === 'admin') {
                        redirect('/inkmybook/modules/admin/dashboard.php');
                    } elseif ($user['role'] === 'support') {
                        redirect('/inkmybook/modules/support_dashboard/index.php');
                    } else {
                        redirect('/inkmybook/index.php');
                    }
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InkMyBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/inkmybook/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            background-color: #fff;
        }

        .login-container {
            min-height: 100vh;
        }

        .login-left {
            padding: 4rem;
            background: #fff;
            z-index: 2;
        }

        .login-right {
            background: linear-gradient(135deg, #1f2836 0%, #111 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        .login-right h1,
        .login-right span {
            color: #fff !important;
        }

        /* Abstract Pattern on Right Side */
        .login-right::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(242, 0, 145, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
            transform: scale(2);
        }

        .login-right::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(0, 127, 237, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
        }

        .social-btn {
            width: 100%;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            transition: all 0.3s;
            text-decoration: none;
            background: #fff;
        }

        .social-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #6c757d;
            margin: 2rem 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }

        .divider::before {
            margin-right: 1em;
        }

        .divider::after {
            margin-left: 1em;
        }

        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background-color: #fcfcfc;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(242, 0, 145, 0.1);
        }

        .floating-label-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .floating-label-group label {
            position: absolute;
            top: 0.8rem;
            left: 1rem;
            color: #6c757d;
            transition: all 0.2s;
            pointer-events: none;
            background: transparent;
        }

        .form-control:focus+label,
        .form-control:not(:placeholder-shown)+label {
            top: -0.6rem;
            left: 0.8rem;
            font-size: 0.75rem;
            background: #fff;
            padding: 0 0.4rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-control::placeholder {
            color: transparent;
        }

        /* Animations */
        .animate-in {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid login-container">
        <div class="row h-100">
            <!-- Left Side: Form -->
            <div class="col-lg-6 login-left d-flex flex-column justify-content-center">
                <div class="mx-auto" style="max-width: 450px; width: 100%;">
                    <div class="mb-5 animate-in">
                        <h2 class="fw-bold display-6 mb-2">Welcome Back</h2>
                        <p class="text-muted">Please enter your details to sign in.</p>
                    </div>

                    <?php flash('register_success'); ?>

                    <!-- Social Login -->
                    <div class="row g-3 mb-4 animate-in delay-1">
                        <div class="col-6">
                            <a href="../../modules/auth/oauth_init.php?provider=google" class="social-btn">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                        fill="#4285F4" />
                                    <path
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                        fill="#34A853" />
                                    <path
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.84z"
                                        fill="#FBBC05" />
                                    <path
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                        fill="#EA4335" />
                                </svg>
                                Google
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="../../modules/auth/oauth_init.php?provider=facebook" class="social-btn">
                                <i class="fab fa-facebook-f me-2" style="color: #1877F2;"></i> Facebook
                            </a>
                        </div>
                    </div>

                    <div class="divider animate-in delay-1">or sign in with email</div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger animate-in">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="animate-in delay-2">
                        <div class="floating-label-group">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Email Address"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                            <label for="email">Email Address</label>
                        </div>

                        <div class="floating-label-group">
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label small text-muted" for="remember">Remember me</label>
                            </div>
                            <a href="#" class="small text-primary fw-bold text-decoration-none">Forgot Password?</a>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary btn-lg fw-bold py-3 rounded-pill shadow-hover">Sign In</button>
                        </div>
                    </form>

                    <div class="mt-4 text-center animate-in delay-3">
                        <p class="text-muted small">Don't have an account? <a href="register.php"
                                class="text-primary fw-bold text-decoration-none">Create Account</a></p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Content -->
            <div class="col-lg-6 login-right d-none d-lg-flex">
                <div class="px-5 position-relative" style="z-index: 2;">
                    <h1 class="display-3 fw-bold mb-4">Welcome back!</h1>
                    <p class="lead opacity-75 mb-5">Pick up where you left off. Your next big project is waiting.</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-rocket text-primary"></i>
                                </div>
                                <span class="fw-medium">Fast & Efficient</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-shield-alt text-primary"></i>
                                </div>
                                <span class="fw-medium">Secure Platform</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="<?php echo BASE_URL; ?>assets/js/cursor.js"></script>
</body>

</html>