<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Admin Dashboard';
$db        = getDB();

// ── Stat Cards ──
$totalIncidents  = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents"))['cnt'];
$openIncidents   = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE status = 'Open'"))['cnt'];
$inProgress      = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE status = 'In Progress'"))['cnt'];
$resolved        = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM incidents WHERE status = 'Resolved'"))['cnt'];
$totalUsers      = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM users WHERE role = 'user'"))['cnt'];


$avgRes = mysqli_fetch_assoc(mysqli_query($db, "
    SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)), 1) as avg_hours
    FROM incidents
    WHERE status = 'Resolved' AND resolved_at IS NOT NULL
"))['avg_hours'];
$avgRes = $avgRes ?? 0;

// Pie Chart 
$pieData = [
    'Open'        => (int)$openIncidents,
    'In Progress' => (int)$inProgress,
    'Resolved'    => (int)$resolved,
];

// Bar Chart: Incidents by Category
$catResult = mysqli_query($db, "
    SELECT category, COUNT(*) as cnt
    FROM incidents
    GROUP BY category
    ORDER BY cnt DESC
");
$catLabels = [];
$catCounts = [];
while ($row = mysqli_fetch_assoc($catResult)) {
    $catLabels[] = $row['category'];
    $catCounts[] = (int)$row['cnt'];
}

//  Line Chart: Incidents per day
$lineResult = mysqli_query($db, "
    SELECT DATE(created_at) as day, COUNT(*) as cnt
    FROM incidents
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$lineDays   = [];
$lineCounts = [];
while ($row = mysqli_fetch_assoc($lineResult)) {
    $lineDays[]   = date('d M', strtotime($row['day']));
    $lineCounts[] = (int)$row['cnt'];
}

//  Recent Incidents 
$recentResult = mysqli_query($db, "
    SELECT i.*, u.name as user_name
    FROM incidents i
    JOIN users u ON u.id = i.user_id
    ORDER BY i.created_at DESC
    LIMIT 5
");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h5 class="mb-3">Admin Dashboard</h5>

<!--  Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <h3 class="text-primary mb-0"><?= $totalIncidents ?></h3>
                <small class="text-muted">Total</small>
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
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <h3 class="text-warning mb-0"><?= $inProgress ?></h3>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-success">
            <div class="card-body py-3">
                <h3 class="text-success mb-0"><?= $resolved ?></h3>
                <small class="text-muted">Resolved</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-info">
            <div class="card-body py-3">
                <h3 class="text-info mb-0"><?= $totalUsers ?></h3>
                <small class="text-muted">Users</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body py-3">
                <h3 class="text-secondary mb-0"><?= $avgRes ?>h</h3>
                <small class="text-muted">Avg Resolve</small>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts Row ── -->
<div class="row g-3 mb-4">

    <!-- Pie Chart -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><strong>Status Overview</strong></div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="pieChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><strong>Incidents by Category</strong></div>
            <div class="card-body">
                <canvas id="barChart" height="220"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- ── Line Chart ── -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Incidents Last 7 Days</strong></div>
            <div class="card-body">
                <canvas id="lineChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Incidents ── -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Recent Incidents</strong>
        <a href="<?= BASE_URL ?>/admin/incidents.php" class="btn btn-sm btn-dark">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recentResult) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">No incidents found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($recentResult)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= $row['category'] ?></td>
                    <td>
                        <?php $pc = $row['priority'] === 'High' ? 'danger' : ($row['priority'] === 'Medium' ? 'warning' : 'secondary'); ?>
                        <span class="badge bg-<?= $pc ?>"><?= $row['priority'] ?></span>
                    </td>
                    <td>
                        <?php $sc = $row['status'] === 'Resolved' ? 'success' : ($row['status'] === 'In Progress' ? 'warning' : 'danger'); ?>
                        <span class="badge bg-<?= $sc ?>"><?= $row['status'] ?></span>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Pass PHP data to JS
$pieDataJson  = json_encode(array_values($pieData));
$pieLabels    = json_encode(array_keys($pieData));
$catLabelsJson = json_encode($catLabels);
$catCountsJson = json_encode($catCounts);
$lineDaysJson  = json_encode($lineDays);
$lineCountsJson = json_encode($lineCounts);

$extraJs = <<<JS
<script>
// ── Pie Chart ──
new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
        labels: {$pieLabels},
        datasets: [{
            data: {$pieDataJson},
            backgroundColor: ['#dc3545','#ffc107','#198754'],
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom' } }
    }
});

// ── Bar Chart ──
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: {$catLabelsJson},
        datasets: [{
            label: 'Incidents',
            data: {$catCountsJson},
            backgroundColor: '#0d6efd',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// ── Line Chart ──
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: {$lineDaysJson},
        datasets: [{
            label: 'Incidents',
            data: {$lineCountsJson},
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>