<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$error   = '';
$success = '';

// 1. QUICK STATUS SWITCH ACTION (Admin, Editor)
if (isset($_GET['set_status'], $_GET['alert_id'])) {
    requireRole(['Admin', 'Editor']);
    $alert_id   = (int)$_GET['alert_id'];
    $new_status = $_GET['set_status'];

    if (in_array($new_status, ['Active', 'Acknowledged', 'Resolved'], true)) {
        $stmt = $pdo->prepare("UPDATE alerts SET status = ? WHERE alert_id = ?");
        $stmt->execute([$new_status, $alert_id]);
        header("Location: alerts.php?msg=status_updated");
        exit;
    }
}

// 2. FULL EDIT MODAL ACTION (Admin, Editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_alert') {
    requireRole(['Admin', 'Editor']);
    $alert_id = (int)$_POST['alert_id'];
    $title    = trim($_POST['title']);
    $location = trim($_POST['location']);
    $status   = $_POST['status'];

    if (!empty($title) && in_array($status, ['Active', 'Acknowledged', 'Resolved'], true)) {
        try {
            $stmt = $pdo->prepare("UPDATE alerts SET title = ?, location = ?, status = ? WHERE alert_id = ?");
            $stmt->execute([$title, $location, $status, $alert_id]);
            $success = "Alert incident updated successfully.";
        } catch (PDOException $e) {
            $error = "Update Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid parameters provided.";
    }
}

// 3. DELETE ALERT ACTION (Admin Only)
if (isset($_GET['delete_id'])) {
    requireRole('Admin');
    $alert_id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM alerts WHERE alert_id = ?")->execute([$alert_id]);
    header("Location: alerts.php?msg=deleted");
    exit;
}

// Status Counts for Filter Tabs
$count_all      = (int)$pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$count_active   = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Active'")->fetchColumn();
$count_ack      = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Acknowledged'")->fetchColumn();
$count_resolved = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Resolved'")->fetchColumn();

// Filter Query
$filter = $_GET['filter'] ?? 'all';
if (in_array($filter, ['Active', 'Acknowledged', 'Resolved'], true)) {
    $stmt = $pdo->prepare("SELECT * FROM alerts WHERE status = ? ORDER BY time DESC");
    $stmt->execute([$filter]);
    $alerts = $stmt->fetchAll();
} else {
    $alerts = $pdo->query("SELECT * FROM alerts ORDER BY time DESC")->fetchAll();
}

require_once 'includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-0">Threshold Incidents & Monitoring</h4>
        <p class="text-muted small mb-0">Track electrical overloads, voltage spikes, and manage resolution statuses</p>
    </div>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> 
        <?= $success ?: ($_GET['msg'] === 'status_updated' ? 'Resolution status updated successfully.' : 'Incident record purged from D5.') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="d-flex gap-2 mb-3">
    <a href="alerts.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
        All Incidents <span class="badge bg-secondary ms-1"><?= $count_all ?></span>
    </a>
    <a href="alerts.php?filter=Active" class="btn btn-sm <?= $filter === 'Active' ? 'btn-danger' : 'btn-outline-danger' ?>">
        Active <span class="badge bg-danger ms-1"><?= $count_active ?></span>
    </a>
    <a href="alerts.php?filter=Acknowledged" class="btn btn-sm <?= $filter === 'Acknowledged' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
        Acknowledged <span class="badge bg-warning text-dark ms-1"><?= $count_ack ?></span>
    </a>
    <a href="alerts.php?filter=Resolved" class="btn btn-sm <?= $filter === 'Resolved' ? 'btn-success' : 'btn-outline-success' ?>">
        Resolved <span class="badge bg-success ms-1"><?= $count_resolved ?></span>
    </a>
</div>

<!-- Alerts Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Alert Description</th>
                    <th>Anomaly Type</th>
                    <th>Facility Location</th>
                    <th>Incident Timestamp</th>
                    <th>Current Status</th>
                    <?php if (hasRole(['Admin', 'Editor'])): ?>
                        <th class="text-end">Change Status / Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No threshold incidents found in this filter category.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alerts as $a): ?>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= htmlspecialchars($a['title']) ?></strong>
                            <div class="small text-muted">ID: #ALT-<?= str_pad($a['alert_id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $a['type'] === 'Overvoltage' ? 'danger' : ($a['type'] === 'Overload' ? 'warning text-dark' : 'secondary') ?>">
                                <?= htmlspecialchars($a['type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($a['location']) ?></td>
                        <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($a['time'])) ?></small></td>
                        <td>
                            <?php if ($a['status'] === 'Active'): ?>
                                <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Active</span>
                            <?php elseif ($a['status'] === 'Acknowledged'): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-eye me-1"></i>Acknowledged</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                            <?php endif; ?>
                        </td>

                        <?php if (hasRole(['Admin', 'Editor'])): ?>
                        <td class="text-end">
                            <!-- Quick Status Switch Dropdown -->
                            <div class="btn-group me-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-arrow-repeat me-1"></i> Status
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><h6 class="dropdown-header">Switch Status To:</h6></li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center <?= $a['status'] === 'Active' ? 'active bg-light text-dark fw-bold' : 'text-danger' ?>" 
                                           href="alerts.php?set_status=Active&alert_id=<?= $a['alert_id'] ?>">
                                            <i class="bi bi-exclamation-circle me-2"></i> Active
                                            <?php if ($a['status'] === 'Active'): ?><i class="bi bi-check ms-auto"></i><?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center <?= $a['status'] === 'Acknowledged' ? 'active bg-light text-dark fw-bold' : 'text-warning' ?>" 
                                           href="alerts.php?set_status=Acknowledged&alert_id=<?= $a['alert_id'] ?>">
                                            <i class="bi bi-eye me-2"></i> Acknowledged
                                            <?php if ($a['status'] === 'Acknowledged'): ?><i class="bi bi-check ms-auto"></i><?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center <?= $a['status'] === 'Resolved' ? 'active bg-light text-dark fw-bold' : 'text-success' ?>" 
                                           href="alerts.php?set_status=Resolved&alert_id=<?= $a['alert_id'] ?>">
                                            <i class="bi bi-check-circle me-2"></i> Resolved
                                            <?php if ($a['status'] === 'Resolved'): ?><i class="bi bi-check ms-auto"></i><?php endif; ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Edit Details Modal Button -->
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editAlertModal<?= $a['alert_id'] ?>" title="Edit Alert Details">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <!-- Delete Incident (Admin Only) -->
                            <?php if (hasRole('Admin')): ?>
                                <a href="alerts.php?delete_id=<?= $a['alert_id'] ?>" onclick="return confirm('Permanently purge this incident record?');" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Alert Modals (Rendered outside the table container) -->
<?php if (hasRole(['Admin', 'Editor'])): ?>
    <?php foreach ($alerts as $a): ?>
    <div class="modal fade" id="editAlertModal<?= $a['alert_id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="update_alert">
                <input type="hidden" name="alert_id" value="<?= $a['alert_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Incident #ALT-<?= str_pad($a['alert_id'], 4, '0', STR_PAD_LEFT) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Incident Title / Details</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($a['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Location / Sub-Station</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($a['location']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Resolution Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $a['status'] === 'Active' ? 'selected' : '' ?>>Active (Unresolved Issue)</option>
                            <option value="Acknowledged" <?= $a['status'] === 'Acknowledged' ? 'selected' : '' ?>>Acknowledged (Inspection in Progress)</option>
                            <option value="Resolved" <?= $a['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved (Normal Operations Restored)</option>
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