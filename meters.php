<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$error = '';
$success = '';

// CREATE Meter (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    requireRole(['Admin', 'Editor']);
    $meter_number      = trim($_POST['meter_number']);
    $building_id       = (int)$_POST['building_id'];
    $installation_date = $_POST['installation_date'];
    $status            = $_POST['status'];

    if (!empty($meter_number) && $building_id > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO meters (meter_number, building_id, installation_date, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$meter_number, $building_id, $installation_date, $status]);
            $success = "Meter '$meter_number' registered.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "All fields are required.";
    }
}

// UPDATE Meter (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    requireRole(['Admin', 'Editor']);
    $meter_id          = (int)$_POST['meter_id'];
    $meter_number      = trim($_POST['meter_number']);
    $building_id       = (int)$_POST['building_id'];
    $installation_date = $_POST['installation_date'];
    $status            = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE meters SET meter_number = ?, building_id = ?, installation_date = ?, status = ? WHERE meter_id = ?");
        $stmt->execute([$meter_number, $building_id, $installation_date, $status, $meter_id]);
        $success = "Meter hardware profile updated.";
    } catch (PDOException $e) {
        $error = "Update Error: " . $e->getMessage();
    }
}

// DELETE Meter (Admin Only)
if (isset($_GET['delete_id'])) {
    requireRole('Admin');
    $id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM meters WHERE meter_id = ?");
        $stmt->execute([$id]);
        header("Location: meters.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Delete Error: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
$meters = $pdo->query("SELECT m.*, b.name as building_name, b.code as building_code FROM meters m JOIN buildings b ON m.building_id = b.building_id ORDER BY m.meter_id DESC")->fetchAll();
$buildings = $pdo->query("SELECT building_id, name, code FROM buildings ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Hardware Meters</h3>
        <p class="text-muted small mb-0">Manage energy sensor telemetry associations and online statuses</p>
    </div>
    <?php if (hasRole(['Admin', 'Editor'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMeterModal">
            <i class="bi bi-plus-lg me-1"></i> Register Hardware Meter
        </button>
    <?php endif; ?>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i> <?= $success ?: 'Meter record deleted successfully.' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Hardware Identifier</th>
                    <th>Linked Building</th>
                    <th>Installation Date</th>
                    <th>Operating Status</th>
                    <?php if (hasRole(['Admin', 'Editor'])): ?>
                        <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meters as $m): ?>
                <tr>
                    <td><code><?= htmlspecialchars($m['meter_number']) ?></code></td>
                    <td><?= htmlspecialchars($m['building_name']) ?> <small class="text-muted">(<?= htmlspecialchars($m['building_code']) ?>)</small></td>
                    <td><?= htmlspecialchars($m['installation_date']) ?></td>
                    <td>
                        <span class="badge bg-<?= $m['status'] === 'Online' ? 'success' : ($m['status'] === 'Fault' ? 'danger' : 'secondary') ?>">
                            <?= $m['status'] ?>
                        </span>
                    </td>
                    <?php if (hasRole(['Admin', 'Editor'])): ?>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editMeter<?= $m['meter_id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if (hasRole('Admin')): ?>
                            <a href="meters.php?delete_id=<?= $m['meter_id'] ?>" onclick="return confirm('Delete this meter and all its stored readings?');" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<?php if (hasRole(['Admin', 'Editor'])): ?>
<div class="modal fade" id="createMeterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register New Energy Meter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Meter Serial / Hardware ID</label>
                    <input type="text" name="meter_number" class="form-control" placeholder="e.g. MTR-DUET-205" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Installation Target Building</label>
                    <select name="building_id" class="form-select" required>
                        <option value="">Select Building...</option>
                        <?php foreach ($buildings as $b): ?>
                            <option value="<?= $b['building_id'] ?>"><?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Installation Date</label>
                    <input type="date" name="installation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Initial Status</label>
                    <select name="status" class="form-select">
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                        <option value="Fault">Fault</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save to Data Store (D3)</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modals (Outside Table Container) -->
<?php foreach ($meters as $m): ?>
<div class="modal fade" id="editMeter<?= $m['meter_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="meter_id" value="<?= $m['meter_id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Meter: <?= htmlspecialchars($m['meter_number']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Meter Serial / Identifier</label>
                    <input type="text" name="meter_number" class="form-control" value="<?= htmlspecialchars($m['meter_number']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assigned Building</label>
                    <select name="building_id" class="form-select" required>
                        <?php foreach ($buildings as $b): ?>
                            <option value="<?= $b['building_id'] ?>" <?= $b['building_id'] == $m['building_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Installation Date</label>
                    <input type="date" name="installation_date" class="form-control" value="<?= $m['installation_date'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Operating Status</label>
                    <select name="status" class="form-select">
                        <option value="Online" <?= $m['status'] === 'Online' ? 'selected' : '' ?>>Online</option>
                        <option value="Offline" <?= $m['status'] === 'Offline' ? 'selected' : '' ?>>Offline</option>
                        <option value="Fault" <?= $m['status'] === 'Fault' ? 'selected' : '' ?>>Fault</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>