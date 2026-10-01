<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$summary = $pdo->query("
    SELECT 
        b.name, 
        b.code,
        b.department,
        COUNT(DISTINCT m.meter_id) as total_meters,
        COALESCE(SUM(r.energy_kwh), 0) as total_kwh,
        COALESCE(SUM(r.cost), 0) as total_cost
    FROM buildings b
    LEFT JOIN meters m ON b.building_id = m.building_id
    LEFT JOIN energy_readings r ON m.meter_id = r.meter_id
    GROUP BY b.building_id
")->fetchAll();

$grand_kwh = array_sum(array_column($summary, 'total_kwh'));
$grand_cost = array_sum(array_column($summary, 'total_cost'));
$grand_co2 = round($grand_kwh * 0.527, 2);

// Log to D6 Reports
$stmt = $pdo->prepare("INSERT INTO reports (report_name, type, period) VALUES ('Formal Campus Energy Audit', 'Executive Summary', 'Current Academic Term')");
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DUET Energy Audit Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 12pt; }
        }
    </style>
</head>
<body class="bg-white p-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Dhaka University of Engineering & Technology (DUET)</h2>
            <p class="text-muted mb-0">Campus Energy Consumption & Environmental Impact Report</p>
        </div>
        <div class="text-end">
            <button onclick="window.print()" class="btn btn-primary no-print mb-2"><i class="bi bi-printer"></i> Print / Save PDF</button>
            <div class="small text-muted">Generated: <?= date('d M Y, H:i') ?></div>
            <div class="small text-muted">Auditor: <?= htmlspecialchars($_SESSION['full_name']) ?></div>
        </div>
    </div>

    <!-- Aggregate Stats -->
    <div class="row mb-4">
        <div class="col-4">
            <div class="border rounded p-3 text-center">
                <div class="text-muted small">Campus Total Usage</div>
                <div class="fs-4 fw-bold"><?= number_format($grand_kwh, 2) ?> kWh</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-3 text-center">
                <div class="text-muted small">Estimated Utility Cost</div>
                <div class="fs-4 fw-bold">৳ <?= number_format($grand_cost, 2) ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-3 text-center">
                <div class="text-muted small">Carbon Footprint</div>
                <div class="fs-4 fw-bold"><?= number_format($grand_co2, 2) ?> kg CO₂</div>
            </div>
        </div>
    </div>

    <!-- Facility Breakdown Table -->
    <h5 class="fw-bold mb-3">Facility Breakdown</h5>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Building Name</th>
                <th>Department</th>
                <th>Meters</th>
                <th>Energy (kWh)</th>
                <th>Cost (BDT)</th>
                <th>CO₂ Impact (kg)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($summary as $row): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['code']) ?></strong></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= $row['total_meters'] ?></td>
                <td><?= number_format($row['total_kwh'], 2) ?></td>
                <td>৳ <?= number_format($row['total_cost'], 2) ?></td>
                <td><?= number_format($row['total_kwh'] * 0.527, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 d-flex justify-content-between text-center">
        <div style="width: 200px; border-top: 1px solid #333;" class="pt-2 small">Prepared by (Staff)</div>
        <div style="width: 200px; border-top: 1px solid #333;" class="pt-2 small">Campus Management Officer</div>
    </div>
</body>
</html>