<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

$pageTitle = 'All Incidents';
$db        = getDB();

// Filters
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$sortBy         = $_GET['sort']     ?? 'created_at';
$sortOrder      = $_GET['order']    ?? 'DESC';

// Whitelist sort columns
$allowedSort = ['id','title','category','priority','status','created_at'];
if (!in_array($sortBy, $allowedSort)) $sortBy = 'created_at';
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

$where = "WHERE 1=1";
if (!empty($filterStatus))
    $where .= " AND i.status   = '" . mysqli_real_escape_string($db, $filterStatus) . "'";
if (!empty($filterCategory))
    $where .= " AND i.category = '" . mysqli_real_escape_string($db, $filterCategory) . "'";
if (!empty($filterPriority))
    $where .= " AND i.priority = '" . mysqli_real_escape_string($db, $filterPriority) . "'";

$incidents = mysqli_query($db, "
    SELECT i.*, u.name as user_name,
           a.name as assigned_name
    FROM incidents i
    JOIN users u ON u.id = i.user_id
    LEFT JOIN users a ON a.id = i.assigned_to
    $where
    ORDER BY i.$sortBy $sortOrder
");

// Admin list for assign dropdown
$adminList = mysqli_query($db, "SELECT id, name FROM users WHERE role IN ('admin','superadmin') ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">All Incidents</h5>
    <a href="<?= BASE_URL ?>/admin/export.php" class="btn btn-sm btn-success">
        <i class="fa fa-file-export me-1"></i>Export
    </a>
</div>

<!-- ── Filters ── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Open','In Progress','Resolved'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'] as $c): ?>
                        <option value="<?= $c ?>" <?= $filterCategory === $c ? 'selected':'' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Low','Medium','High'] as $p): ?>
                        <option value="<?= $p ?>" <?= $filterPriority === $p ? 'selected':'' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Sort By</label>
                <select name="sort" class="form-select form-select-sm">
                    <?php foreach (['created_at'=>'Date','priority'=>'Priority','status'=>'Status'] as $val=>$label): ?>
                        <option value="<?= $val ?>" <?= $sortBy === $val ? 'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Order</label>
                <select name="order" class="form-select form-select-sm">
                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected':'' ?>>Newest First</option>
                    <option value="ASC"  <?= $sortOrder === 'ASC'  ? 'selected':'' ?>>Oldest First</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="incidents.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="card mb-2" id="bulkBar" style="display:none!important">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span id="selectedCount" class="fw-semibold text-muted"></span>
        <button class="btn btn-sm btn-success" onclick="bulkAction('resolve')">
            <i class="fa fa-check me-1"></i>Mark Resolved
        </button>
        <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
            <i class="fa fa-trash me-1"></i>Delete Selected
        </button>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <select id="bulkAssignAdmin" class="form-select form-select-sm" style="width:180px">
                <option value="">-- Assign to Admin --</option>
                <?php
                // Reset pointer
                mysqli_data_seek($adminList, 0);
                while ($a = mysqli_fetch_assoc($adminList)):
                ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <button class="btn btn-sm btn-primary" onclick="bulkAction('assign')">
                <i class="fa fa-user-tag me-1"></i>Assign
            </button>
        </div>
    </div>
</div>

<!-- ── Table ── -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" id="incidentTable">
            <thead class="table-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>#</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($incidents) === 0): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">No incidents found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($incidents)): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="row-check" value="<?= $row['id'] ?>">
                    </td>
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
                    <td><?= $row['assigned_name'] ? htmlspecialchars($row['assigned_name']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary"
                                onclick="openEdit(<?= $row['id'] ?>,'<?= addslashes($row['title']) ?>','<?= $row['status'] ?>','<?= $row['priority'] ?>','<?= $row['category'] ?>')">
                            <i class="fa fa-pen"></i>
                        </button>
                        <?php if (hasRole('superadmin')): ?>
                        <button class="btn btn-sm btn-danger ms-1"
                                onclick="deleteIncident(<?= $row['id'] ?>)">
                            <i class="fa fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Edit Modal ── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" id="editTitle" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="editStatus" class="form-select">
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select id="editPriority" class="form-select">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select id="editCategory" class="form-select">
                        <?php foreach (['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'] as $c): ?>
                            <option value="<?= $c ?>"><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEdit()">
                    <i class="fa fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$baseUrl = BASE_URL;

$extraJs = <<<JS
<script>

const BASE_URL = "{$baseUrl}";

$(document).ready(function () {
    $('#incidentTable').DataTable({
        order: [[8, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 9] }],
        pageLength: 10
    });
});

// ── Select All ──
$('#selectAll').on('change', function () {
    $('.row-check').prop('checked', this.checked);
    toggleBulkBar();
});

$(document).on('change', '.row-check', function () {
    toggleBulkBar();
    $('#selectAll').prop('checked', $('.row-check:not(:checked)').length === 0);
});

function toggleBulkBar() {
    var count = $('.row-check:checked').length;
    if (count > 0) {
        $('#bulkBar').show();
        $('#selectedCount').text(count + ' selected');
    } else {
        $('#bulkBar').hide();
    }
}

function getSelected() {
    var ids = [];
    $('.row-check:checked').each(function () {
        ids.push($(this).val());
    });
    return ids;
}

// ── Bulk Actions ──
function bulkAction(action) {
    var ids = getSelected();
    if (ids.length === 0) {
        Swal.fire('Warning', 'Please select at least one incident.', 'warning');
        return;
    }

    var assignTo = '';
    if (action === 'assign') {
        assignTo = $('#bulkAssignAdmin').val();
        if (!assignTo) {
            Swal.fire('Warning', 'Please select an admin to assign.', 'warning');
            return;
        }
    }

    var confirmMsg = action === 'delete'
        ? 'Delete selected incidents permanently?'
        : action === 'resolve'
            ? 'Mark selected incidents as Resolved?'
            : 'Assign selected incidents?';

    Swal.fire({
        title: 'Are you sure?',
        text: confirmMsg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'delete' ? '#dc3545' : '#0d6efd',
        confirmButtonText: 'Yes, proceed'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/ajax/bulk_action.php', {
                action:    action,
                ids:       ids,
                assign_to: assignTo
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

// ── Open Edit Modal ──
function openEdit(id, title, status, priority, category) {
    $('#editId').val(id);
    $('#editTitle').val(title);
    $('#editStatus').val(status);
    $('#editPriority').val(priority);
    $('#editCategory').val(category);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ── Save Edit ──
function saveEdit() {
    $.post(BASE_URL + '/ajax/update_incident.php', {
        id:       $('#editId').val(),
        status:   $('#editStatus').val(),
        priority: $('#editPriority').val(),
        category: $('#editCategory').val()
    }, function (res) {
        if (res.success) {
            Swal.fire('Updated!', res.message, 'success')
                .then(function () { location.reload(); });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

// ── Delete Single ──
function deleteIncident(id) {
    Swal.fire({
        title: 'Delete Incident?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(BASE_URL +'/ajax/bulk_action.php', {
                action: 'delete',
                ids:    [id]
            }, function (res) {
                if (res.success) {
                    Swal.fire('Deleted!', res.message, 'success')
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