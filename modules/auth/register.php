<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/oauth.php';
require_once '../../includes/functions.php';

if (isLoggedIn()) {
    redirect('/inkmybook/index.php');
}

$errors = [];
$name = $username = $email = $phone = $role = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    file_put_contents('debug_register.log', "POST received\n", FILE_APPEND);

    $name = sanitizeInput($_POST['name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role']);

    // Validation
    if (empty($name))
        $errors[] = "Full Name is required";
    if (empty($username))
        $errors[] = "Username is required";
    if (empty($email))
        $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format";
    if (empty($phone))
        $errors[] = "Phone number is required";
    if (empty($password))
        $errors[] = "Password is required";
    if ($password !== $confirm_password)
        $errors[] = "Passwords do not match";
    if (empty($role) || !in_array($role, ['provider', 'seeker']))
        $errors[] = "Invalid account type selected";

    if (!empty($errors)) {
        file_put_contents('debug_register.log', "Validation errors: " . print_r($errors, true) . "\n", FILE_APPEND);
    }

    // Check if email or username exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email or Username is already registered";
            file_put_contents('debug_register.log', "Duplicate user found\n", FILE_APPEND);
        }
    }

    // Register User
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $username, $email, $phone, $hashed_password, $role])) {
                file_put_contents('debug_register.log', "Insert successful. Redirecting...\n", FILE_APPEND);
                flash('register_success', 'Registration successful! Please login.');
                redirect('login.php');
            } else {
                $errors[] = "Something went wrong. Please try again.";
                file_put_contents('debug_register.log', "Insert failed: " . print_r($stmt->errorInfo(), true) . "\n", FILE_APPEND);
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            file_put_contents('debug_register.log', "DB Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - InkMyBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/inkmybook/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            background-color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .register-container {
            min-height: 100vh;
        }

        .register-left {
            padding: 4rem;
            background: #fff;
            z-index: 2;
        }

        .register-right {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            position: relative;
            overflow: hidden;
        }

        .register-right h1,
        .register-right p,
        .register-right span {
            color: #fff !important;
        }

        /* Abstract Pattern on Right Side */
        .register-right::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(242, 0, 145, 0.15) 0%, rgba(0, 0, 0, 0) 60%);
            transform: scale(2);
            filter: blur(60px);
        }

        .register-right::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(0, 127, 237, 0.15) 0%, rgba(0, 0, 0, 0) 60%);
            filter: blur(60px);
        }

        .social-btn {
            width: 100%;
            height: 56px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            transition: all 0.2s ease;
            text-decoration: none;
            background: #fff;
        }

        .social-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            margin: 2rem 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider::before {
            margin-right: 1.5em;
        }

        .divider::after {
            margin-left: 1.5em;
        }

        .form-control {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 1rem;
            transition: all 0.2s ease;
            color: #334155;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(242, 0, 145, 0.1);
        }

        .account-type-card {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
            background: #fff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .account-type-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .account-type-input:checked+.account-type-card {
            border-color: var(--primary-color);
            background-color: rgba(242, 0, 145, 0.02);
            box-shadow: 0 4px 12px rgba(242, 0, 145, 0.1);
        }

        .account-type-input:checked+.account-type-card::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 12px;
            right: 12px;
            color: var(--primary-color);
            font-size: 1.1rem;
            background: rgba(242, 0, 145, 0.1);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .account-type-input {
            display: none;
        }

        .floating-label-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .floating-label-group label {
            position: absolute;
            top: 1rem;
            left: 1.2rem;
            color: #94a3b8;
            transition: all 0.2s ease;
            pointer-events: none;
            background: transparent;
            font-weight: 500;
        }

        .form-control:focus+label,
        .form-control:not(:placeholder-shown)+label {
            top: -0.7rem;
            left: 1rem;
            font-size: 0.8rem;
            background: #fff;
            padding: 0 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-control::placeholder {
            color: transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #d90082 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(242, 0, 145, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(242, 0, 145, 0.4);
            background: linear-gradient(135deg, #d90082 0%, var(--primary-color) 100%);
        }

        /* Animations */
        .animate-in {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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

    <div class="container-fluid register-container">
        <div class="row h-100">
            <!-- Left Side: Form -->
            <div class="col-lg-6 register-left d-flex flex-column justify-content-center">
                <div class="mx-auto" style="max-width: 550px; width: 100%;">
                    <div class="mb-5 animate-in">
                        <h2 class="fw-bold display-6 mb-2">Create Account</h2>
                        <p class="text-muted">Join the community of creators and professionals.</p>
                    </div>

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

                    <div class="divider animate-in delay-1">or register with email</div>

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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="floating-label-group">
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    <label for="name">Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="floating-label-group">
                                    <input type="text" class="form-control" name="username" id="username"
                                        placeholder="Username" value="<?php echo htmlspecialchars($username); ?>"
                                        required>
                                    <label for="username">Username</label>
                                </div>
                            </div>
                        </div>

                        <div class="floating-label-group">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Email Address"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                            <label for="email">Email Address</label>
                        </div>

                        <div class="floating-label-group">
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="Phone Number"
                                value="<?php echo htmlspecialchars($phone); ?>" required>
                            <label for="phone">Phone Number</label>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="floating-label-group">
                                    <input type="password" class="form-control" name="password" id="password"
                                        placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="floating-label-group">
                                    <input type="password" class="form-control" name="confirm_password"
                                        id="confirm_password" placeholder="Confirm Password" required>
                                    <label for="confirm_password">Confirm Password</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted mb-3 d-block">I want to...</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="w-100 h-100">
                                        <input type="radio" name="role" value="provider" class="account-type-input"
                                            <?php echo $role === 'provider' ? 'checked' : ''; ?> required>
                                        <div class="account-type-card text-center">
                                            <div class="mb-2 text-primary"><i class="fas fa-briefcase fa-2x"></i></div>
                                            <h6 class="fw-bold mb-1">Offer Services</h6>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">I'm a
                                                Freelancer</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="w-100 h-100">
                                        <input type="radio" name="role" value="seeker" class="account-type-input" <?php echo $role === 'seeker' ? 'checked' : ''; ?> required>
                                        <div class="account-type-card text-center">
                                            <div class="mb-2 text-secondary"><i class="fas fa-search fa-2x"></i></div>
                                            <h6 class="fw-bold mb-1">Hire Talent</h6>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">I'm a
                                                Client</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit"
                                class="btn btn-primary btn-lg fw-bold py-3 rounded-pill shadow-hover">Create
                                Account</button>
                        </div>
                    </form>

                    <div class="mt-4 text-center animate-in delay-3">
                        <p class="text-muted small">Already have an account? <a href="login.php"
                                class="text-primary fw-bold text-decoration-none">Sign In</a></p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Content -->
            <div class="col-lg-6 register-right d-none d-lg-flex">
                <div class="px-5 position-relative" style="z-index: 2;">
                    <h1 class="display-3 fw-bold mb-4">Turn your ideas into reality.</h1>
                    <p class="lead opacity-75 mb-5">Connect with world-class talent and get things done. The marketplace
                        for professional services.</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-check text-primary"></i>
                                </div>
                                <span class="fw-medium">Verified Professionals</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-check text-primary"></i>
                                </div>
                                <span class="fw-medium">Secure Payments</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-check text-primary"></i>
                                </div>
                                <span class="fw-medium">24/7 Support</span>
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