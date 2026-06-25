<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Handle export requests
$exportType = $_GET['type'] ?? '';

if ($exportType === 'csv' || $exportType === 'pdf') {

    // Filters
    $filterStatus   = $_GET['status']   ?? '';
    $filterCategory = $_GET['category'] ?? '';

    $where = "WHERE 1=1";
    if (!empty($filterStatus))
        $where .= " AND i.status = '"   . mysqli_real_escape_string(getDB(), $filterStatus)   . "'";
    if (!empty($filterCategory))
        $where .= " AND i.category = '" . mysqli_real_escape_string(getDB(), $filterCategory) . "'";

    $db     = getDB();
    $result = mysqli_query($db, "
        SELECT i.id, i.title, i.category, i.priority, i.status,
               u.name as submitted_by,
               a.name as assigned_to,
               i.created_at, i.resolved_at
        FROM incidents i
        JOIN users u ON u.id = i.user_id
        LEFT JOIN users a ON a.id = i.assigned_to
        $where
        ORDER BY i.created_at DESC
    ");

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    // CSV Export
    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="incidents_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Title','Category','Priority','Status','Submitted By','Assigned To','Created At','Resolved At']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'],
                $row['title'],
                $row['category'],
                $row['priority'],
                $row['status'],
                $row['submitted_by'],
                $row['assigned_to'] ?? '—',
                $row['created_at'],
                $row['resolved_at'] ?? '—',
            ]);
        }

        fclose($out);

        AuditLog::write(authId(), 'Exported CSV', 'incident', null, ['count' => count($rows)]);
        exit;
    }

    // PDF Export
    if ($exportType === 'pdf') {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator(APP_NAME);
        $pdf->SetTitle('Incident Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, APP_NAME . ' — Incident Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'Generated: ' . date('d M Y H:i'), 0, 1, 'C');
        $pdf->Ln(4);

        // Table header
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);

        $cols = [
            'ID'           => 10,
            'Title'        => 55,
            'Category'     => 35,
            'Priority'     => 20,
            'Status'       => 25,
            'Submitted By' => 35,
            'Assigned To'  => 35,
            'Date'         => 27,
        ];

        foreach ($cols as $col => $width) {
            $pdf->Cell($width, 7, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Table rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;

        foreach ($rows as $row) {
            $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
            $pdf->Cell(10, 6, $row['id'],                          1, 0, 'C', $fill);
            $pdf->Cell(55, 6, $row['title'],                       1, 0, 'L', $fill);
            $pdf->Cell(35, 6, $row['category'],                    1, 0, 'L', $fill);
            $pdf->Cell(20, 6, $row['priority'],                    1, 0, 'C', $fill);
            $pdf->Cell(25, 6, $row['status'],                      1, 0, 'C', $fill);
            $pdf->Cell(35, 6, $row['submitted_by'],                1, 0, 'L', $fill);
            $pdf->Cell(35, 6, $row['assigned_to'] ?? '—',          1, 0, 'L', $fill);
            $pdf->Cell(27, 6, date('d M Y', strtotime($row['created_at'])), 1, 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }

        AuditLog::write(authId(), 'Exported PDF', 'incident', null, ['count' => count($rows)]);

        $pdf->Output('incidents_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }
}

// Show Export Page
$pageTitle = 'Export Incidents';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h5 class="mb-3">Export Incidents</h5>

<div class="card">
    <div class="card-header"><strong>Filter & Export</strong></div>
    <div class="card-body">

        <form method="GET" id="exportForm">

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach (['Open','In Progress','Resolved'] as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach (['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'] as $c): ?>
                            <option value="<?= $c ?>"><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" name="type" value="csv"
                        class="btn btn-success">
                    <i class="fa fa-file-csv me-2"></i>Export as CSV
                </button>
                <button type="submit" name="type" value="pdf"
                        class="btn btn-danger">
                    <i class="fa fa-file-pdf me-2"></i>Export as PDF
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>