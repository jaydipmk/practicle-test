<?php
// superadmin/dashboard.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('superadmin');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Super Admin Dashboard';
$db        = getDB();

// ── Stats ──
$totalUsers      = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$totalAdmins     = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'"))['cnt'];
$blockedUsers    = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM users WHERE is_blocked = 1"))['cnt'];
$totalIncidents  = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents"))['cnt'];
$openIncidents   = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE status = 'Open'"))['cnt'];
$resolvedCount   = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE status = 'Resolved'"))['cnt'];
$totalLogs       = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM audit_logs"))['cnt'];

// ── Recent Audit Logs ──
$recentLogs = mysqli_query($db, "
    SELECT al.*, u.name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 8
");

// ── Recent Users ──
$recentUsers = mysqli_query($db, "
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

// ── Incidents by role (who submitted most) ──
$topUsers = mysqli_query($db, "
    SELECT u.name, COUNT(i.id) as total
    FROM users u
    LEFT JOIN incidents i ON i.user_id = u.id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 5
");

$topUserNames  = [];
$topUserCounts = [];
while ($row = mysqli_fetch_assoc($topUsers)) {
    $topUserNames[]  = $row['name'];
    $topUserCounts[] = (int)$row['total'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h5 class="mb-3">Super Admin Dashboard</h5>

<!-- ── Stats ── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <h3 class="text-primary mb-0"><?= $totalUsers ?></h3>
                <small class="text-muted">Total Users</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <h3 class="text-warning mb-0"><?= $totalAdmins ?></h3>
                <small class="text-muted">Admins</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body py-3">
                <h3 class="text-danger mb-0"><?= $blockedUsers ?></h3>
                <small class="text-muted">Blocked</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-info">
            <div class="card-body py-3">
                <h3 class="text-info mb-0"><?= $totalIncidents ?></h3>
                <small class="text-muted">Incidents</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body py-3">
                <h3 class="text-danger mb-0"><?= $openIncidents ?></h3>
                <small class="text-muted">Open</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body py-3">
                <h3 class="text-secondary mb-0"><?= $totalLogs ?></h3>
                <small class="text-muted">Audit Logs</small>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts + Recent Users ── -->
<div class="row g-3 mb-4">

    <!-- Top Users Chart -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <strong>Top Users by Incidents</strong>
            </div>
            <div class="card-body">
                <canvas id="topUsersChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Registered Users -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recently Registered</strong>
                <a href="<?= BASE_URL ?>/superadmin/users.php" class="btn btn-sm btn-dark">
                    Manage Users
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recentUsers)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?php
                            $rc = $row['role'] === 'superadmin' ? 'danger' :
                                  ($row['role'] === 'admin' ? 'warning' : 'success');
                            ?>
                            <span class="badge bg-<?= $rc ?>"><?= ucfirst($row['role']) ?></span>
                        </td>
                        <td>
                            <?php if ((int)$row['is_blocked']): ?>
                                <span class="badge bg-danger">Blocked</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Audit Logs ── -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Recent Audit Logs</strong>
        <a href="<?= BASE_URL ?>/superadmin/audit_logs.php" class="btn btn-sm btn-dark">
            View All
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0 table-sm">
            <thead class="table-dark">
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recentLogs) === 0): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">No logs yet.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($recentLogs)): ?>
                <tr>
                    <td><?= $row['user_name'] ? htmlspecialchars($row['user_name']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php
                        $ac = 'secondary';
                        if (stripos($row['action'], 'login')   !== false) $ac = 'primary';
                        if (stripos($row['action'], 'created') !== false) $ac = 'success';
                        if (stripos($row['action'], 'updated') !== false) $ac = 'warning';
                        if (stripos($row['action'], 'deleted') !== false) $ac = 'danger';
                        if (stripos($row['action'], 'blocked') !== false) $ac = 'danger';
                        ?>
                        <span class="badge bg-<?= $ac ?>">
                            <?= htmlspecialchars($row['action']) ?>
                        </span>
                    </td>
                    <td>
                        <?= ucfirst($row['target_type']) ?>
                        <?= $row['target_id'] ? ' #' . $row['target_id'] : '' ?>
                    </td>
                    <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                    <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$topNamesJson  = json_encode($topUserNames);
$topCountsJson = json_encode($topUserCounts);

$extraJs = <<<JS
<script>
new Chart(document.getElementById('topUsersChart'), {
    type: 'bar',
    data: {
        labels: {$topNamesJson},
        datasets: [{
            label: 'Incidents',
            data: {$topCountsJson},
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales:  { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>