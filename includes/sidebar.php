<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentRole = authRole();
$currentName = authName();
$currentId   = authId();

// Unread notifications count
$notifCount = 0;
if ($currentRole === 'user') {
    $db  = getDB();
    $uid = $currentId;
    $res = mysqli_query($db, "SELECT COUNT(*) as cnt FROM notifications
                               WHERE user_id = '$uid' AND is_read = 0");
    $row = mysqli_fetch_assoc($res);
    $notifCount = (int)$row['cnt'];
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand fw-bold" href="#">
        <i class="fa fa-shield-halved me-2"></i><?= APP_NAME ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">

        <ul class="navbar-nav me-auto mt-2 mt-lg-0">

            <?php if ($currentRole === 'user'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/user/dashboard.php">
                        <i class="fa fa-gauge me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'submit_incident.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/user/submit_incident.php">
                        <i class="fa fa-circle-plus me-1"></i>Submit Incident
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'my_incidents.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/user/my_incidents.php">
                        <i class="fa fa-list me-1"></i>My Incidents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'notifications.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/user/notifications.php">
                        <i class="fa fa-bell me-1"></i>Notifications
                        <?php if ($notifCount > 0): ?>
                            <span class="badge bg-danger"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($currentRole === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/dashboard.php">
                        <i class="fa fa-gauge me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'incidents.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/incidents.php">
                        <i class="fa fa-triangle-exclamation me-1"></i>All Incidents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'assign.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/assign.php">
                        <i class="fa fa-user-tag me-1"></i>Assign
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'export.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/export.php">
                        <i class="fa fa-file-export me-1"></i>Export
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($currentRole === 'superadmin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/superadmin/dashboard.php">
                        <i class="fa fa-gauge me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/superadmin/users.php">
                        <i class="fa fa-users me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'incidents.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/incidents.php">
                        <i class="fa fa-triangle-exclamation me-1"></i>Incidents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'export.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/export.php">
                        <i class="fa fa-file-export me-1"></i>Export
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/superadmin/audit_logs.php">
                        <i class="fa fa-clock-rotate-left me-1"></i>Audit Logs
                    </a>
                </li>
            <?php endif; ?>

        </ul>

        <!-- Right side -->
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <span class="nav-link text-white-50">
                    <i class="fa fa-user me-1"></i><?= htmlspecialchars($currentName) ?>
                    <span class="badge
                        <?= $currentRole === 'superadmin' ? 'bg-danger' : ($currentRole === 'admin' ? 'bg-warning text-dark' : 'bg-success') ?>
                        ms-1">
                        <?= ucfirst($currentRole) ?>
                    </span>
                </span>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                    <i class="fa fa-right-from-bracket me-1"></i>Logout
                </a>
            </li>
        </ul>

    </div>
</nav>

<!-- Page alerts -->
<div class="container-fluid mt-2">
<?php if (isset($_GET['unauthorized'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fa fa-lock me-1"></i> Not authorized to access that page.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['expired'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <i class="fa fa-clock me-1"></i> Session expired. Please login again.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['blocked'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa fa-ban me-1"></i> Your account has been blocked.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
</div>

<!-- Page content start -->
<div class="container-fluid mt-3">