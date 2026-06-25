<?php
// user/my_incidents.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'My Incidents';
$uid       = authId();
$db        = getDB();

// Filters
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';

$where = "WHERE user_id = '$uid'";
if (!empty($filterStatus))   $where .= " AND status   = '" . mysqli_real_escape_string($db, $filterStatus) . "'";
if (!empty($filterCategory)) $where .= " AND category = '" . mysqli_real_escape_string($db, $filterCategory) . "'";

$incidents = mysqli_query($db, "SELECT * FROM incidents $where ORDER BY created_at DESC");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">My Incidents</h5>
    <a href="submit_incident.php" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>New Incident
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Open','In Progress','Resolved'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'] as $c): ?>
                        <option value="<?= $c ?>" <?= $filterCategory === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="my_incidents.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" id="incidentTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Evidence</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($incidents) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">No incidents found.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($incidents)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= $row['category'] ?></td>
                        <td>
                            <?php $pc = $row['priority'] === 'High' ? 'danger' : ($row['priority'] === 'Medium' ? 'warning' : 'secondary'); ?>
                            <span class="badge bg-<?= $pc ?>"><?= $row['priority'] ?></span>
                        </td>
                        <td>
                            <?php $sc = $row['status'] === 'Resolved' ? 'success' : ($row['status'] === 'In Progress' ? 'warning' : 'danger'); ?>
                            <span class="badge bg-<?= $sc ?>"><?= $row['status'] ?></span>
                        </td>
                        <td>
                            <?php if ($row['evidence_path']): ?>
                                <a href="<?= UPLOAD_URL . $row['evidence_path'] ?>" target="_blank">
                                    <i class="fa fa-paperclip"></i> View
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
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
$extraJs = <<<JS
<script>
$(document).ready(function () {
    $('#incidentTable').DataTable({
        order: [[6, 'desc']],
        pageLength: 10
    });
});
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>