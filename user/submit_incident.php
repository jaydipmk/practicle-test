<?php
// user/submit_incident.php
require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

$pageTitle = 'Submit Incident';
$uid       = authId();
$db        = getDB();
$error     = '';
$success   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category']    ?? '');
    $priority    = trim($_POST['priority']    ?? '');

    $allowed_categories = ['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'];
    $allowed_priorities = ['Low','Medium','High'];

    if (empty($title) || empty($description) || empty($category) || empty($priority)) {
        $error = 'All fields are required.';

    } elseif (!in_array($category, $allowed_categories)) {
        $error = 'Invalid category.';

    } elseif (!in_array($priority, $allowed_priorities)) {
        $error = 'Invalid priority.';

    } else {
        // Handle file upload
        $evidencePath = null;

        if (!empty($_FILES['evidence']['name'])) {
            $file     = $_FILES['evidence'];
            $fileType = mime_content_type($file['tmp_name']);

            if ($file['size'] > MAX_FILE_SIZE) {
                $error = 'File size must be under 5MB.';

            } elseif (!in_array($fileType, ALLOWED_TYPES)) {
                $error = 'Only JPG, PNG, GIF, PDF files allowed.';

            } else {
                $ext          = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename     = 'evidence_' . time() . '_' . $uid . '.' . $ext;
                $uploadPath   = UPLOAD_DIR . $filename;

                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $evidencePath = $filename;
                } else {
                    $error = 'File upload failed.';
                }
            }
        }

        if (empty($error)) {
            $title       = mysqli_real_escape_string($db, $title);
            $description = mysqli_real_escape_string($db, $description);
            $category    = mysqli_real_escape_string($db, $category);
            $priority    = mysqli_real_escape_string($db, $priority);
            $evidenceVal = $evidencePath ? "'" . mysqli_real_escape_string($db, $evidencePath) . "'" : 'NULL';

            $sql = "INSERT INTO incidents
                        (user_id, title, description, category, priority, evidence_path)
                    VALUES
                        ('$uid', '$title', '$description', '$category', '$priority', $evidenceVal)";

            if (mysqli_query($db, $sql)) {
                $newId = (int)mysqli_insert_id($db);
                AuditLog::write($uid, 'Created Incident', 'incident', $newId, ['title' => $title]);
                $success = 'Incident submitted successfully!';
            } else {
                $error = 'Failed to submit. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card">
            <div class="card-header">
                <strong><i class="fa fa-circle-plus me-2"></i>Submit New Incident</strong>
            </div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                        <a href="my_incidents.php" class="ms-2">View My Incidents</a>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                               placeholder="Brief incident title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Describe the incident in detail" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                $cats = ['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'];
                                foreach ($cats as $cat):
                                    $sel = (($_POST['category'] ?? '') === $cat) ? 'selected' : '';
                                ?>
                                <option value="<?= $cat ?>" <?= $sel ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="">-- Select Priority --</option>
                                <?php
                                $pris = ['Low','Medium','High'];
                                foreach ($pris as $pri):
                                    $sel = (($_POST['priority'] ?? '') === $pri) ? 'selected' : '';
                                ?>
                                <option value="<?= $pri ?>" <?= $sel ?>><?= $pri ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Evidence <span class="text-muted">(optional — JPG, PNG, PDF, max 5MB)</span></label>
                        <input type="file" name="evidence" class="form-control"
                               accept=".jpg,.jpeg,.png,.gif,.pdf">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane me-1"></i>Submit Incident
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Cancel</a>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>