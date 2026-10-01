<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('Admin'); // Admin Only

$error = '';
$success = '';

// CREATE User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $role      = $_POST['role'];
    $password  = trim($_POST['password']);

    if (!empty($full_name) && !empty($email) && !empty($password)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $role, $hashed]);
            $success = "User account created for '$full_name'.";
        } catch (PDOException $e) {
            $error = "Email already registered or database error: " . $e->getMessage();
        }
    } else {
        $error = "All fields are required.";
    }
}

// UPDATE User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $user_id   = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $role      = $_POST['role'];
    $password  = trim($_POST['password']);

    try {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $role, $hashed, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $role, $user_id]);
        }
        $success = "User account credentials updated.";
    } catch (PDOException $e) {
        $error = "Update Error: " . $e->getMessage();
    }
}

// DELETE User
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($id === (int)$_SESSION['user_id']) {
        $error = "You cannot delete your own active administrative session.";
    } else {
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
        header("Location: users.php?msg=deleted");
        exit;
    }
}

require_once 'includes/header.php';
$users = $pdo->query("SELECT * FROM users ORDER BY user_id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">User Accounts & Roles</h3>
        <p class="text-muted small mb-0">RBAC permission configurations for Admin, Editor, and Viewer portals</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus me-1"></i> Add System User
    </button>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i> <?= $success ?: 'User removed from D1.' ?>
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
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Assigned Role</th>
                    <th>Created Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><code>#USR-<?= $u['user_id'] ?></code></td>
                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'Admin' ? 'danger' : ($u['role'] === 'Editor' ? 'primary' : 'secondary') ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td><small><?= $u['created_at'] ?></small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUser<?= $u['user_id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($u['user_id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="users.php?delete_id=<?= $u['user_id'] ?>" onclick="return confirm('Revoke and delete this account?');" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register New System User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="user@duet.ac.bd" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role Authorization</label>
                    <select name="role" class="form-select">
                        <option value="Viewer">Viewer (Campus Management)</option>
                        <option value="Editor">Editor (Staff / Operator)</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Initial Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modals (Outside Table Container) -->
<?php foreach ($users as $u): ?>
<div class="modal fade" id="editUser<?= $u['user_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">System Role</label>
                    <select name="role" class="form-select">
                        <option value="Viewer" <?= $u['role'] === 'Viewer' ? 'selected' : '' ?>>Viewer (Campus Management)</option>
                        <option value="Editor" <?= $u['role'] === 'Editor' ? 'selected' : '' ?>>Editor (Technician/Staff)</option>
                        <option value="Admin" <?= $u['role'] === 'Admin' ? 'selected' : '' ?>>Admin (Full Control)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <small class="text-muted">(Leave empty to retain existing)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>