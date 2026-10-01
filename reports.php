<?php
require_once 'config/db.php';

// Handle CSV Export Action (Process 5.0 / Campus Management Export)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $period = isset($_GET['period']) ? $_GET['period'] : 'All-Time';
    $filename = "DUET_EMS_Energy_Report_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($output, ['Building Code', 'Building Name', 'Department', 'Total Energy (kWh)', 'Tariff Rate (BDT/kWh)', 'Total Cost (BDT)', 'CO2 Emission (kg)']);

    $stmt = $pdo->query("
        SELECT 
            b.code,
            b.name,
            b.department,
            COALESCE(SUM(r.energy_kwh), 0) AS total_kwh,
            COALESCE(SUM(r.cost), 0) AS total_cost
        FROM buildings b
        LEFT JOIN meters m ON b.building_id = m.building_id
        LEFT JOIN energy_readings r ON m.meter_id = r.meter_id
        GROUP BY b.building_id
        ORDER BY total_kwh DESC
    ");

    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $co2 = round($row['total_kwh'] * 0.527, 2);
        fputcsv($output, [
            $row['code'],
            $row['name'],
            $row['department'],
            number_format($row['total_kwh'], 2, '.', ''),
            '9.50',
            number_format($row['total_cost'], 2, '.', ''),
            $co2
        ]);
    }

    // Persist log entry into D6 (Reports)
    $report_name = "Campus Energy & Cost Breakdown (" . $period . ")";
    $log_stmt = $pdo->prepare("INSERT INTO reports (report_name, type, period, status) VALUES (?, 'CSV Export', ?, 'Completed')");
    $log_stmt->execute([$report_name, $period]);

    fclose($output);
    exit;
}

// Handle Custom Report Generation (Process 5.0 -> Store D6)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_name = trim($_POST['report_name']);
    $type        = trim($_POST['type']);
    $period      = trim($_POST['period']);

    if (!empty($report_name) && !empty($type) && !empty($period)) {
        $stmt = $pdo->prepare("INSERT INTO reports (report_name, type, period, status) VALUES (?, ?, ?, 'Generated')");
        $stmt->execute([$report_name, $type, $period]);
        header("Location: reports.php?success=1");
        exit;
    }
}

// Handle Report Deletion
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: reports.php");
    exit;
}

require_once 'includes/header.php';

// Quick Aggregates for Top Cards
$total_kwh   = (float)$pdo->query("SELECT COALESCE(SUM(energy_kwh), 0) FROM energy_readings")->fetchColumn();
$total_cost  = (float)$pdo->query("SELECT COALESCE(SUM(cost), 0) FROM energy_readings")->fetchColumn();
$total_co2   = round($total_kwh * 0.527, 2);
$reports_log = $pdo->query("SELECT * FROM reports ORDER BY generated_date DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Analytics & Reports</h3>
        <p class="text-muted small mb-0">Generate, view, and export energy audits for Campus Management</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
            <i class="bi bi-plus-circle me-1"></i> Log Custom Audit
        </button>
        <a href="print_report.php" target="_blank" class="btn btn-secondary">
            <i class="bi bi-printer me-1"></i> Executive Printable Report
        </a>
        <a href="reports.php?export=csv&period=All-Time" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Data (CSV)
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> New consumption audit record successfully saved .
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                    <i class="bi bi-lightning-charge fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Campus Cumulative Energy</span>
                    <h4 class="fw-bold mb-0"><?= number_format($total_kwh, 2) ?> <span class="fs-6 fw-normal">kWh</span></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                    <i class="bi bi-currency-dollar fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Calculated Tariff Expense</span>
                    <h4 class="fw-bold mb-0">৳ <?= number_format($total_cost, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 me-3">
                    <i class="bi bi-tree fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Carbon Footprint</span>
                    <h4 class="fw-bold mb-0"><?= number_format($total_co2, 2) ?> <span class="fs-6 fw-normal">kg CO₂</span></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Saved Reports Table (Store D6) -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-folder2-open me-2 text-primary"></i>Generated Report Archive (D6)</h6>
        <span class="badge bg-light text-secondary"><?= count($reports_log) ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Report ID</th>
                    <th>Report Title</th>
                    <th>Audit Type</th>
                    <th>Reporting Period</th>
                    <th>Generation Timestamp</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports_log)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No reports generated yet. Click "Export Data" or "Log Custom Audit" to create one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports_log as $r): ?>
                    <tr>
                        <td><code>#REP-<?= str_pad($r['report_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                        <td><strong><?= htmlspecialchars($r['report_name']) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= $r['type'] === 'CSV Export' ? 'success' : 'primary' ?> bg-opacity-10 text-<?= $r['type'] === 'CSV Export' ? 'success' : 'primary' ?> border">
                                <?= htmlspecialchars($r['type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['period']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($r['generated_date'])) ?></td>
                        <td>
                            <span class="badge bg-success"><?= htmlspecialchars($r['status']) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="print_report.php" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Executive View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <a href="reports.php?delete_id=<?= $r['report_id'] ?>" onclick="return confirm('Delete this report record from D6?');" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Generate / Log Custom Report -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Log Custom Energy Audit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Report Title</label>
                    <input type="text" name="report_name" class="form-control" placeholder="e.g. Q3 Campus Demand Analysis" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Audit Type</label>
                    <select name="type" class="form-select" required>
                        <option value="Energy & Cost Summary">Energy & Cost Summary</option>
                        <option value="Building Comparison">Building Comparison</option>
                        <option value="Carbon Footprint Audit">Carbon Footprint Audit</option>
                        <option value="Executive Management Brief">Executive Management Brief</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reporting Period</label>
                    <select name="period" class="form-select" required>
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Annual">Annual</option>
                        <option value="Current Academic Term">Current Academic Term</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="generate_report" class="btn btn-primary">Save to Data Store (D6)</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>