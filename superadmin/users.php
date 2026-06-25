<?php
// superadmin/users.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('superadmin');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'User Management';
$db        = getDB();

$users = mysqli_query($db, "
    SELECT u.*,
           COUNT(i.id) as total_incidents
    FROM users u
    LEFT JOIN incidents i ON i.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">User Management</h5>
    <button class="btn btn-primary btn-sm" onclick="openAddModal()">
        <i class="fa fa-user-plus me-1"></i>Add User
    </button>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" id="usersTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Incidents</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($users)): ?>
            <tr id="userRow_<?= $row['id'] ?>">
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php
                    $rc = $row['role'] === 'superadmin' ? 'danger' : ($row['role'] === 'admin' ? 'warning' : 'success');
                    ?>
                    <span class="badge bg-<?= $rc ?>"><?= ucfirst($row['role']) ?></span>
                </td>
                <td><?= $row['total_incidents'] ?></td>
                <td>
                    <?php if ((int)$row['is_blocked']): ?>
                        <span class="badge bg-danger">Blocked</span>
                    <?php else: ?>
                        <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <!-- Edit -->
                    <button class="btn btn-sm btn-primary"
                            onclick="openEditModal(
                                <?= $row['id'] ?>,
                                '<?= addslashes($row['name']) ?>',
                                '<?= addslashes($row['email']) ?>',
                                '<?= $row['role'] ?>'
                            )">
                        <i class="fa fa-pen"></i>
                    </button>

                    <!-- Block / Unblock -->
                    <?php if ((int)$row['id'] !== authId()): ?>
                        <?php if ((int)$row['is_blocked']): ?>
                        <button class="btn btn-sm btn-success ms-1"
                                onclick="toggleBlock(<?= $row['id'] ?>, 'unblock')">
                            <i class="fa fa-unlock"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-warning ms-1"
                                onclick="toggleBlock(<?= $row['id'] ?>, 'block')">
                            <i class="fa fa-ban"></i>
                        </button>
                        <?php endif; ?>

                        <!-- Delete -->
                        <button class="btn btn-sm btn-danger ms-1"
                                onclick="deleteUser(<?= $row['id'] ?>)">
                            <i class="fa fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Add User Modal ── -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="addName" class="form-control" placeholder="John Doe">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" id="addEmail" class="form-control" placeholder="john@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" id="addPassword" class="form-control" placeholder="Min 6 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select id="addRole" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addUser()">
                    <i class="fa fa-user-plus me-1"></i>Add User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit User Modal ── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="editName" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" id="editEmail" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password
                        <small class="text-muted">(leave blank to keep current)</small>
                    </label>
                    <input type="password" id="editPassword" class="form-control" placeholder="Optional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select id="editRole" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
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
    $('#usersTable').DataTable({ pageLength: 10 });
});

// ── Open Add Modal ──
function openAddModal() {
    $('#addName').val('');
    $('#addEmail').val('');
    $('#addPassword').val('');
    $('#addRole').val('user');
    new bootstrap.Modal(document.getElementById('addModal')).show();
}

// ── Add User ──
function addUser() {
    var name     = $('#addName').val().trim();
    var email    = $('#addEmail').val().trim();
    var password = $('#addPassword').val().trim();
    var role     = $('#addRole').val();

    if (!name || !email || !password) {
        Swal.fire('Warning', 'All fields are required.', 'warning');
        return;
    }

    $.post(BASE_URL +'/ajax/user_action.php', {
        action:   'add',
        name:     name,
        email:    email,
        password: password,
        role:     role
    }, function (res) {
        if (res.success) {
            Swal.fire('Added!', res.message, 'success')
                .then(function () { location.reload(); });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

// ── Open Edit Modal ──
function openEditModal(id, name, email, role) {
    $('#editId').val(id);
    $('#editName').val(name);
    $('#editEmail').val(email);
    $('#editPassword').val('');
    $('#editRole').val(role);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ── Save Edit ──
function saveEdit() {
    var id       = $('#editId').val();
    var name     = $('#editName').val().trim();
    var email    = $('#editEmail').val().trim();
    var password = $('#editPassword').val().trim();
    var role     = $('#editRole').val();

    if (!name || !email) {
        Swal.fire('Warning', 'Name and email are required.', 'warning');
        return;
    }

    $.post(BASE_URL +'/ajax/user_action.php', {
        action:   'edit',
        id:       id,
        name:     name,
        email:    email,
        password: password,
        role:     role
    }, function (res) {
        if (res.success) {
            Swal.fire('Updated!', res.message, 'success')
                .then(function () { location.reload(); });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

// ── Block / Unblock ──
function toggleBlock(id, action) {
    var msg = action === 'block' ? 'Block this user?' : 'Unblock this user?';
    Swal.fire({
        title: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'block' ? '#ffc107' : '#198754',
        confirmButtonText: 'Yes'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(BASE_URL +'/ajax/user_action.php', {
                action: action,
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

// ── Delete User ──
function deleteUser(id) {
    Swal.fire({
        title: 'Delete this user?',
        text: 'All their incidents will also be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(BASE_URL +'/ajax/user_action.php', {
                action: 'delete',
                id:     id
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