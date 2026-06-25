<?php
// auth/login.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/audit.php';

session_start();

// Already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'superadmin')  header('Location: ' . BASE_URL . '/superadmin/dashboard.php');
    elseif ($role === 'admin')   header('Location: ' . BASE_URL . '/admin/dashboard.php');
    else                         header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $db       = getDB();
        $emailEsc = mysqli_real_escape_string($db, $email);
        $result   = mysqli_query($db, "SELECT * FROM users WHERE email = '$emailEsc' LIMIT 1");
        $user     = $result ? mysqli_fetch_assoc($result) : null;

        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ((int)$user['is_blocked'] === 1) {
            $error = 'Your account has been blocked. Contact administrator.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            // Set session
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            AuditLog::write((int)$user['id'], 'User Login', 'user', (int)$user['id']);

            if ($user['role'] === 'superadmin')     header('Location: ' . BASE_URL . '/superadmin/dashboard.php');
            elseif ($user['role'] === 'admin')       header('Location: ' . BASE_URL . '/admin/dashboard.php');
            else                                     header('Location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header text-center bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-shield-halved me-2"></i><?= APP_NAME ?>
                    </h5>
                </div>
                <div class="card-body p-4">

                    <h6 class="text-center text-muted mb-3">Sign in to your account</h6>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success">Registered successfully! Please login.</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['blocked'])): ?>
                        <div class="alert alert-danger">Your account has been blocked.</div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fa fa-right-to-bracket me-1"></i>Login
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        No account? <a href="register.php">Register</a>
                    </p>

                    <!-- Demo credentials -->
                    <div class="mt-3 p-2 bg-light rounded small">
                        <strong>Demo:</strong><br>
                        superadmin@system.com / Admin@1234<br>
                        admin@system.com / Admin@1234<br>
                        user@system.com / Admin@1234
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>