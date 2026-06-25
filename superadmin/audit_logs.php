<?php
// superadmin/audit_logs.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('superadmin');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Audit Logs';
$db        = getDB();

// Filters
$filterAction = $_GET['action'] ?? '';
$filterTarget = $_GET['target'] ?? '';
$filterDate   = $_GET['date']   ?? '';

$where = "WHERE 1=1";

if (!empty($filterAction)) {
    $fa     = mysqli_real_escape_string($db, $filterAction);
    $where .= " AND al.action LIKE '%$fa%'";
}
if (!empty($filterTarget)) {
    $ft     = mysqli_real_escape_string($db, $filterTarget);
    $where .= " AND al.target_type = '$ft'";
}
if (!empty($filterDate)) {
    $fd     = mysqli_real_escape_string($db, $filterDate);
    $where .= " AND DATE(al.created_at) = '$fd'";
}

$logs = mysqli_query($db, "
    SELECT al.*, u.name as user_name, u.role as user_role
    FROM audit_logs al
    LEFT JOIN users u ON u.id = al.user_id
    $where
    ORDER BY al.created_at DESC
    LIMIT 500
");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Audit Logs</h5>
    <span class="text-muted small">Showing latest 500 records</span>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">Search Action</label>
                <input type="text" name="action" class="form-control form-control-sm"
                       placeholder="e.g. Login, Created..."
                       value="<?= htmlspecialchars($filterAction) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Target Type</label>
                <select name="target" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="incident" <?= $filterTarget === 'incident' ? 'selected':'' ?>>Incident</option>
                    <option value="user"     <?= $filterTarget === 'user'     ? 'selected':'' ?>>User</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filterDate) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="audit_logs.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0 table-sm" id="logsTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Target ID</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($logs) === 0): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">No logs found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($logs)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <?php if ($row['user_name']): ?>
                            <?= htmlspecialchars($row['user_name']) ?>
                        <?php else: ?>
                            <span class="text-muted">Deleted User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['user_role']): ?>
                            <?php
                            $rc = $row['user_role'] === 'superadmin' ? 'danger' :
                                  ($row['user_role'] === 'admin' ? 'warning' : 'success');
                            ?>
                            <span class="badge bg-<?= $rc ?>">
                                <?= ucfirst($row['user_role']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $actionColor = 'secondary';
                        if (stripos($row['action'], 'login')    !== false) $actionColor = 'primary';
                        if (stripos($row['action'], 'created')  !== false) $actionColor = 'success';
                        if (stripos($row['action'], 'updated')  !== false) $actionColor = 'warning';
                        if (stripos($row['action'], 'deleted')  !== false) $actionColor = 'danger';
                        if (stripos($row['action'], 'blocked')  !== false) $actionColor = 'danger';
                        if (stripos($row['action'], 'exported') !== false) $actionColor = 'info';
                        ?>
                        <span class="badge bg-<?= $actionColor ?>">
                            <?= htmlspecialchars($row['action']) ?>
                        </span>
                    </td>
                    <td><?= ucfirst($row['target_type']) ?></td>
                    <td>
                        <?= $row['target_id']
                            ? '<span class="badge bg-light text-dark border">#' . $row['target_id'] . '</span>'
                            : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td>
                        <?php if ($row['details']): ?>
                            <?php $details = json_decode($row['details'], true); ?>
                            <?php if (is_array($details)): ?>
                                <?php foreach ($details as $key => $val): ?>
                                    <small class="text-muted">
                                        <strong><?= htmlspecialchars($key) ?>:</strong>
                                        <?= htmlspecialchars(is_array($val) ? implode(', ', $val) : $val) ?>
                                    </small><br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small><?= htmlspecialchars($row['details']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                    <td><?= date('d M Y H:i:s', strtotime($row['created_at'])) ?></td>
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
    $('#logsTable').DataTable({
        order: [[8, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: [6] }]
    });
});
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>