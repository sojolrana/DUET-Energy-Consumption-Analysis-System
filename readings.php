<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$error = '';
$success = '';

// MANUAL READING CREATION (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_entry') {
    requireRole(['Admin', 'Editor']);
    $meter_id = (int)$_POST['meter_id'];
    $voltage  = (float)$_POST['voltage'];
    $current  = (float)$_POST['current'];
    $power    = ($voltage * $current) / 1000.0;
    $energy   = isset($_POST['energy_kwh']) && $_POST['energy_kwh'] !== '' ? (float)$_POST['energy_kwh'] : round($power * 0.25, 3);
    $cost     = $energy * 9.50;
    $time     = !empty($_POST['recorded_at']) ? $_POST['recorded_at'] : date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO energy_readings (meter_id, recorded_at, voltage, current, power, energy_kwh, cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$meter_id, $time, $voltage, $current, $power, $energy, $cost]);
        $success = "Manual reading entry logged.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// DELETE Reading (Admin Only)
if (isset($_GET['delete_id'])) {
    requireRole('Admin');
    $id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM energy_readings WHERE reading_id = ?")->execute([$id]);
    header("Location: readings.php?msg=deleted");
    exit;
}

// Filter Logic
$where = "1=1";
$params = [];
if (!empty($_GET['meter_id'])) {
    $where .= " AND r.meter_id = ?";
    $params[] = (int)$_GET['meter_id'];
}

require_once 'includes/header.php';
$stmt = $pdo->prepare("
    SELECT r.*, m.meter_number, b.name as building_name 
    FROM energy_readings r 
    JOIN meters m ON r.meter_id = m.meter_id 
    JOIN buildings b ON m.building_id = b.building_id 
    WHERE $where
    ORDER BY r.recorded_at DESC LIMIT 50
");
$stmt->execute($params);
$readings = $stmt->fetchAll();

$all_meters = $pdo->query("SELECT meter_id, meter_number FROM meters ORDER BY meter_number ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Telemetry Readings</h3>
        <p class="text-muted small mb-0">Raw sensor telemetry logs and calibrated energy conversions</p>
    </div>
    <?php if (hasRole(['Admin', 'Editor'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualReadingModal">
            <i class="bi bi-plus-lg me-1"></i> Log Manual Reading
        </button>
    <?php endif; ?>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i> <?= $success ?: 'Reading record removed from D4.' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Toolbar -->
<div class="card border-0 shadow-sm mb-4 p-3 bg-white">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <select name="meter_id" class="form-select">
                <option value="">All Energy Meters</option>
                <?php foreach ($all_meters as $m): ?>
                    <option value="<?= $m['meter_id'] ?>" <?= isset($_GET['meter_id']) && $_GET['meter_id'] == $m['meter_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['meter_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
        </div>
        <?php if (!empty($_GET['meter_id'])): ?>
            <div class="col-md-2">
                <a href="readings.php" class="btn btn-link text-muted">Clear Filter</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>Meter Hardware</th>
                    <th>Building Location</th>
                    <th>Voltage</th>
                    <th>Current</th>
                    <th>Active Power</th>
                    <th>Energy (kWh)</th>
                    <th>Tariff Cost (BDT)</th>
                    <?php if (hasRole('Admin')): ?>
                        <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($readings)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No telemetry records match your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($readings as $r): ?>
                    <tr>
                        <td><small><?= $r['recorded_at'] ?></small></td>
                        <td><code><?= htmlspecialchars($r['meter_number']) ?></code></td>
                        <td><?= htmlspecialchars($r['building_name']) ?></td>
                        <td><?= number_format($r['voltage'], 1) ?> V</td>
                        <td><?= number_format($r['current'], 2) ?> A</td>
                        <td><?= number_format($r['power'], 2) ?> kW</td>
                        <td><strong><?= number_format($r['energy_kwh'], 3) ?></strong></td>
                        <td>৳ <?= number_format($r['cost'], 2) ?></td>
                        <?php if (hasRole('Admin')): ?>
                        <td class="text-end">
                            <a href="readings.php?delete_id=<?= $r['reading_id'] ?>" onclick="return confirm('Purge reading record?');" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Manual Entry Modal -->
<?php if (hasRole(['Admin', 'Editor'])): ?>
<div class="modal fade" id="manualReadingModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="manual_entry">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Manual Energy Reading Input</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Meter</label>
                    <select name="meter_id" class="form-select" required>
                        <?php foreach ($all_meters as $m): ?>
                            <option value="<?= $m['meter_id'] ?>"><?= htmlspecialchars($m['meter_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Voltage (V)</label>
                        <input type="number" step="0.1" name="voltage" class="form-control" value="230.0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Current (A)</label>
                        <input type="number" step="0.01" name="current" class="form-control" placeholder="15.5" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Energy Consumed (kWh) <small class="text-muted">(Optional - auto calculated if blank)</small></label>
                    <input type="number" step="0.001" name="energy_kwh" class="form-control" placeholder="e.g. 1.250">
                </div>
                <div class="mb-3">
                    <label class="form-label">Timestamp</label>
                    <input type="datetime-local" name="recorded_at" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Telemetry (D4)</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>