<?php
// user/notifications.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Notifications';
$uid       = authId();
$db        = getDB();

// Mark all as read when page opens
mysqli_query($db, "UPDATE notifications SET is_read = 1 WHERE user_id = '$uid'");

// Fetch all notifications
$notifications = mysqli_query($db, "
    SELECT n.*, i.title as incident_title
    FROM notifications n
    JOIN incidents i ON i.id = n.incident_id
    WHERE n.user_id = '$uid'
    ORDER BY n.created_at DESC
");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Notifications</h5>
    <?php if (mysqli_num_rows($notifications) > 0): ?>
        <button class="btn btn-sm btn-outline-danger" id="clearAllBtn">
            <i class="fa fa-trash me-1"></i>Clear All
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush" id="notifList">
            <?php if (mysqli_num_rows($notifications) === 0): ?>
                <li class="list-group-item text-center text-muted py-4">
                    <i class="fa fa-bell-slash fa-2x mb-2 d-block"></i>
                    No notifications yet.
                </li>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($notifications)): ?>
                <li class="list-group-item" id="notif-<?= $row['id'] ?>">
                    <div class="d-flex justify-content-between">
                        <div>
                            <i class="fa fa-bell text-primary me-2"></i>
                            <strong><?= htmlspecialchars($row['incident_title']) ?></strong><br>
                            <span class="text-muted ms-4"><?= htmlspecialchars($row['message']) ?></span>
                        </div>
                        <small class="text-muted text-nowrap ms-3">
                            <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                        </small>
                    </div>
                </li>
                <?php endwhile; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php
$extraJs = <<<JS
<script>
// Clear all notifications
$('#clearAllBtn').on('click', function () {
    Swal.fire({
        title: 'Clear all notifications?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear all',
        confirmButtonColor: '#dc3545'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('<?= BASE_URL ?>/ajax/clear_notifications.php', function (res) {
                if (res.success) {
                    $('#notifList').html(
                        '<li class="list-group-item text-center text-muted py-4">' +
                        '<i class="fa fa-bell-slash fa-2x mb-2 d-block"></i>No notifications yet.</li>'
                    );
                    $('#clearAllBtn').hide();
                }
            }, 'json');
        }
    });
});

// Real-time polling every 5 seconds
function pollNotifications() {
    $.get('<?= BASE_URL ?>/ajax/get_notifications.php', function (res) {
        if (res.success && res.count > 0) {
            // Update navbar badge if exists
            $('.badge.bg-danger').text(res.count).show();
        } else {
            $('.badge.bg-danger').hide();
        }
    }, 'json');
}

setInterval(pollNotifications, 5000);
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>