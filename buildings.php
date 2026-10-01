<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$error = '';
$success = '';

// CREATE Building (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    requireRole(['Admin', 'Editor']);
    $name       = trim($_POST['name']);
    $code       = trim($_POST['code']);
    $floors     = (int)$_POST['floors'];
    $department = trim($_POST['department']);
    $location   = trim($_POST['location']);
    $status     = $_POST['status'];

    if (!empty($name) && !empty($code)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO buildings (name, code, floors, department, location, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $floors, $department, $location, $status]);
            $success = "Building '$name' successfully added.";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Building name and code are required.";
    }
}

// UPDATE Building (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    requireRole(['Admin', 'Editor']);
    $id         = (int)$_POST['building_id'];
    $name       = trim($_POST['name']);
    $code       = trim($_POST['code']);
    $floors     = (int)$_POST['floors'];
    $department = trim($_POST['department']);
    $location   = trim($_POST['location']);
    $status     = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE buildings SET name = ?, code = ?, floors = ?, department = ?, location = ?, status = ? WHERE building_id = ?");
        $stmt->execute([$name, $code, $floors, $department, $location, $status, $id]);
        $success = "Building details updated.";
    } catch (PDOException $e) {
        $error = "Update Error: " . $e->getMessage();
    }
}

// DELETE Building (Admin Only)
if (isset($_GET['delete_id'])) {
    requireRole('Admin');
    $id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM buildings WHERE building_id = ?");
        $stmt->execute([$id]);
        header("Location: buildings.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Delete Error: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
$buildings = $pdo->query("SELECT * FROM buildings ORDER BY building_id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Campus Buildings</h3>
        <p class="text-muted small mb-0">Manage registered campus infrastructure and facility statuses</p>
    </div>
    <?php if (hasRole(['Admin', 'Editor'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBuildingModal">
            <i class="bi bi-plus-lg me-1"></i> Add Building
        </button>
    <?php endif; ?>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i> <?= $success ?: 'Record removed successfully.' ?>
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
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Floors</th>
                    <th>Location</th>
                    <th>Status</th>
                    <?php if (hasRole(['Admin', 'Editor'])): ?>
                        <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buildings as $b): ?>
                <tr>
                    <td><code><?= htmlspecialchars($b['code']) ?></code></td>
                    <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['department']) ?></td>
                    <td><?= $b['floors'] ?></td>
                    <td><?= htmlspecialchars($b['location']) ?></td>
                    <td>
                        <span class="badge bg-<?= $b['status'] === 'Active' ? 'success' : ($b['status'] === 'Maintenance' ? 'warning' : 'secondary') ?>">
                            <?= $b['status'] ?>
                        </span>
                    </td>
                    <?php if (hasRole(['Admin', 'Editor'])): ?>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $b['building_id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if (hasRole('Admin')): ?>
                            <a href="buildings.php?delete_id=<?= $b['building_id'] ?>" onclick="return confirm('Delete this building and its linked meters?');" class="btn btn-sm btn-outline-danger">
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
<div class="modal fade" id="createBuildingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register Campus Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Building Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Mechanical Engineering Building" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unique Code</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. ME-03" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Allocated Department</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. Mechanical Engineering" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Floors</label>
                        <input type="number" name="floors" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location / Zone</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. West Campus" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Record (D2)</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modals (Outside Table Container) -->
<?php foreach ($buildings as $b): ?>
<div class="modal fade" id="editModal<?= $b['building_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="building_id" value="<?= $b['building_id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Building: <?= htmlspecialchars($b['code']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Building Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($b['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($b['code']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($b['department']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Floors</label>
                        <input type="number" name="floors" class="form-control" value="<?= $b['floors'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $b['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Maintenance" <?= $b['status'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            <option value="Inactive" <?= $b['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location / Zone</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($b['location']) ?>" required>
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