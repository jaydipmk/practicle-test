<?php
// auth/register.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/audit.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db       = getDB();
        $emailEsc = mysqli_real_escape_string($db, $email);
        $check    = mysqli_query($db, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");

        if (mysqli_num_rows($check) > 0) {
            $error = 'Email already registered.';
        } else {
            $nameEsc = mysqli_real_escape_string($db, $name);
            $hash    = password_hash($password, PASSWORD_BCRYPT);
            $hashEsc = mysqli_real_escape_string($db, $hash);

            $sql = "INSERT INTO users (name, email, password, role)
                    VALUES ('$nameEsc', '$emailEsc', '$hashEsc', 'user')";

            if (mysqli_query($db, $sql)) {
                $newId = (int)mysqli_insert_id($db);
                AuditLog::write($newId, 'User Registered', 'user', $newId);
                header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header text-center bg-dark text-white">
                    <h5 class="mb-0">Register</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Register</button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        Already registered? <a href="login.php">Login</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>