<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

$pageTitle = 'Assign Incidents';
$db        = getDB();

// Admin list
$adminList = mysqli_query($db, "
    SELECT id, name, role FROM users
    WHERE role IN ('admin','superadmin')
    ORDER BY name
");

// Unassigned incidents
$incidents = mysqli_query($db, "
    SELECT i.*, u.name as user_name
    FROM incidents i
    JOIN users u ON u.id = i.user_id
    WHERE i.assigned_to IS NULL
      AND i.status != 'Resolved'
    ORDER BY i.created_at DESC
");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h5 class="mb-3">Assign Incidents</h5>

<!-- Already Assigned Section -->
<?php
$assigned = mysqli_query($db, "
    SELECT i.*, u.name as user_name, a.name as assigned_name
    FROM incidents i
    JOIN users u ON u.id = i.user_id
    JOIN users a ON a.id = i.assigned_to
    WHERE i.assigned_to IS NOT NULL
      AND i.status != 'Resolved'
    ORDER BY i.created_at DESC
");
?>

<!-- Unassigned -->
<div class="card mb-4">
    <div class="card-header">
        <strong>Unassigned Incidents</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" id="unassignedTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Date</th>
                    <th>Assign To</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($incidents) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">
                        All incidents are assigned.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($incidents)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= $row['category'] ?></td>
                    <td>
                        <?php $pc = $row['priority'] === 'High' ? 'danger' : ($row['priority'] === 'Medium' ? 'warning' : 'secondary'); ?>
                        <span class="badge bg-<?= $pc ?>"><?= $row['priority'] ?></span>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <select class="form-select form-select-sm admin-select"
                                id="admin_<?= $row['id'] ?>" style="min-width:150px">
                            <option value="">-- Select Admin --</option>
                            <?php
                            mysqli_data_seek($adminList, 0);
                            while ($admin = mysqli_fetch_assoc($adminList)):
                            ?>
                                <option value="<?= $admin['id'] ?>">
                                    <?= htmlspecialchars($admin['name']) ?>
                                    (<?= ucfirst($admin['role']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary"
                                onclick="assignIncident(<?= $row['id'] ?>)">
                            <i class="fa fa-user-tag me-1"></i>Assign
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Already Assigned -->
<div class="card">
    <div class="card-header">
        <strong>Already Assigned (Active)</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" id="assignedTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($assigned) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">
                        No assigned incidents.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($assigned)): ?>
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
                        <?php $sc = $row['status'] === 'In Progress' ? 'warning' : 'danger'; ?>
                        <span class="badge bg-<?= $sc ?>"><?= $row['status'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['assigned_name']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning"
                                onclick="unassignIncident(<?= $row['id'] ?>)">
                            <i class="fa fa-xmark me-1"></i>Unassign
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$baseUrl = BASE_URL;

$extraJs = <<<JS
<script>
const BASE_URL = "{$baseUrl}";

$(document).ready(function () {
    $('#unassignedTable').DataTable({ pageLength: 10 });
    $('#assignedTable').DataTable({ pageLength: 10 });
});

function assignIncident(id) {
    var adminId = $('#admin_' + id).val();
    if (!adminId) {
        Swal.fire('Warning', 'Please select an admin first.', 'warning');
        return;
    }

    $.post(BASE_URL +'/ajax/assign_action.php', {
        action:    'assign',
        id:        id,
        assign_to: adminId
    }, function (res) {
        if (res.success) {
            Swal.fire('Assigned!', res.message, 'success')
                .then(function () { location.reload(); });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

function unassignIncident(id) {
    Swal.fire({
        title: 'Unassign this incident?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, unassign'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(BASE_URL +'/ajax/assign_action.php', {
                action: 'unassign',
                id:     id
            }, function (res) {
                if (res.success) {
                    Swal.fire('Done!', res.message, 'success')
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>