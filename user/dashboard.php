<?php
// user/dashboard.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'My Dashboard';
$uid       = authId();
$db        = getDB();

// Stats
$total      = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE user_id = '$uid'"))['cnt'];
$open       = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE user_id = '$uid' AND status = 'Open'"))['cnt'];
$inprogress = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE user_id = '$uid' AND status = 'In Progress'"))['cnt'];
$resolved   = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE user_id = '$uid' AND status = 'Resolved'"))['cnt'];

// Recent 5 incidents
$recent = mysqli_query($db, "SELECT * FROM incidents WHERE user_id = '$uid' ORDER BY created_at DESC LIMIT 5");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-3">Welcome, <?= htmlspecialchars(authName()) ?></h4>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?= $total ?></h3>
                <p class="mb-0 text-muted">Total</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-danger"><?= $open ?></h3>
                <p class="mb-0 text-muted">Open</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning"><?= $inprogress ?></h3>
                <p class="mb-0 text-muted">In Progress</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success"><?= $resolved ?></h3>
                <p class="mb-0 text-muted">Resolved</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Incidents -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Recent Incidents</strong>
        <a href="<?= BASE_URL ?>/user/submit_incident.php" class="btn btn-sm btn-primary">
            <i class="fa fa-plus me-1"></i>New Incident
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            No incidents yet.
                            <a href="<?= BASE_URL ?>/user/submit_incident.php">Submit one</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= $row['category'] ?></td>
                        <td>
                            <?php
                            $p = $row['priority'];
                            $pc = $p === 'High' ? 'danger' : ($p === 'Medium' ? 'warning' : 'secondary');
                            ?>
                            <span class="badge bg-<?= $pc ?>"><?= $p ?></span>
                        </td>
                        <td>
                            <?php
                            $s = $row['status'];
                            $sc = $s === 'Resolved' ? 'success' : ($s === 'In Progress' ? 'warning' : 'danger');
                            ?>
                            <span class="badge bg-<?= $sc ?>"><?= $s ?></span>
                        </td>
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-end">
        <a href="<?= BASE_URL ?>/user/my_incidents.php">View All Incidents →</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>