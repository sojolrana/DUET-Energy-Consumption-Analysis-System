<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$success = '';
$error   = '';

// 1. CREATE / BROADCAST MESSAGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $subject = trim($_POST['subject']);
    $body    = trim($_POST['body']);
    $sender  = $_SESSION['full_name'] . " (" . $_SESSION['role'] . ")";

    if (!empty($subject) && !empty($body)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender, subject, body, status) VALUES (?, ?, ?, 'Unread')");
            $stmt->execute([$sender, $subject, $body]);
            $success = "Operational message posted to Store D7.";
        } catch (PDOException $e) {
            $error = "Message Error: " . $e->getMessage();
        }
    } else {
        $error = "Subject and body cannot be empty.";
    }
}

// 2. UPDATE / EDIT MESSAGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id      = (int)$_POST['message_id'];
    $subject = trim($_POST['subject']);
    $body    = trim($_POST['body']);

    if (!empty($subject) && !empty($body)) {
        // Fetch message to verify ownership unless user is Admin
        $checkStmt = $pdo->prepare("SELECT sender FROM messages WHERE message_id = ?");
        $checkStmt->execute([$id]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $canEdit = hasRole('Admin') || (strpos($existing['sender'], $_SESSION['full_name']) === 0);
            if ($canEdit) {
                try {
                    $stmt = $pdo->prepare("UPDATE messages SET subject = ?, body = ? WHERE message_id = ?");
                    $stmt->execute([$subject, $body, $id]);
                    $success = "Message updated successfully.";
                } catch (PDOException $e) {
                    $error = "Update failed: " . $e->getMessage();
                }
            } else {
                $error = "Unauthorized: You can only edit messages you originated.";
            }
        } else {
            $error = "Message not found.";
        }
    } else {
        $error = "Subject and body cannot be empty.";
    }
}

// 3. TOGGLE READ STATUS
if (isset($_GET['toggle_read'])) {
    $id = (int)$_GET['toggle_read'];
    $stmt = $pdo->prepare("UPDATE messages SET status = IF(status='Unread', 'Read', 'Unread') WHERE message_id = ?");
    $stmt->execute([$id]);
    header("Location: messages.php");
    exit;
}

// 4. DELETE MESSAGE (Admin Only)
if (isset($_GET['delete_id'])) {
    requireRole('Admin');
    $id = (int)$_GET['delete_id'];
    try {
        $pdo->prepare("DELETE FROM messages WHERE message_id = ?")->execute([$id]);
        header("Location: messages.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
$messages = $pdo->query("SELECT * FROM messages ORDER BY timestamp DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Operations Dispatch & Messaging</h3>
        <p class="text-muted small mb-0">Campus engineer coordination, log bulletins, and internal dispatch updates</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
        <i class="bi bi-pencil-square me-1"></i> Post Broadcast
    </button>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= $success ?: 'Message successfully removed from D7.' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        <?php if (empty($messages)): ?>
            <div class="p-4 text-center text-muted">No operational messages found in Store D7.</div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
            <?php 
                $canEdit = hasRole('Admin') || (strpos($m['sender'], $_SESSION['full_name']) === 0);
            ?>
            <div class="list-group-item p-3 <?= $m['status'] === 'Unread' ? 'bg-light border-start border-4 border-primary' : '' ?>">
                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                    <h6 class="mb-0 fw-bold text-dark">
                        <?= htmlspecialchars($m['subject']) ?>
                        <?php if ($m['status'] === 'Unread'): ?>
                            <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">New</span>
                        <?php endif; ?>
                    </h6>
                    <small class="text-muted"><?= date('d M Y, h:i A', strtotime($m['timestamp'])) ?></small>
                </div>
                <p class="mb-3 text-secondary" style="font-size: 0.9rem; line-height: 1.5;"><?= nl2br(htmlspecialchars($m['body'])) ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Broadcast by: <strong class="text-dark"><?= htmlspecialchars($m['sender']) ?></strong></small>
                    <div class="d-flex gap-1">
                        <!-- Mark Read/Unread -->
                        <a href="messages.php?toggle_read=<?= $m['message_id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-envelope-<?= $m['status'] === 'Unread' ? 'open' : 'fill' ?>"></i> 
                            Mark <?= $m['status'] === 'Unread' ? 'Read' : 'Unread' ?>
                        </a>

                        <!-- Edit Button (Owner or Admin) -->
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMsgModal<?= $m['message_id'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        <?php endif; ?>

                        <!-- Delete Button (Admin Only) -->
                        <?php if (hasRole('Admin')): ?>
                            <a href="messages.php?delete_id=<?= $m['message_id'] ?>" onclick="return confirm('Delete this dispatch message?');" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Compose Modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="send">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Compose Dispatch Broadcast</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Scheduled Sub-station Calibration" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Dispatch Body</label>
                    <textarea name="body" class="form-control" rows="4" placeholder="Enter message payload..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Broadcast to D7</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modals (Cleanly placed outside the list container) -->
<?php foreach ($messages as $m): ?>
<?php if (hasRole('Admin') || (strpos($m['sender'], $_SESSION['full_name']) === 0)): ?>
<div class="modal fade" id="editMsgModal<?= $m['message_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Broadcast #MSG-<?= $m['message_id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Subject</label>
                    <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($m['subject']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Dispatch Body</label>
                    <textarea name="body" class="form-control" rows="5" required><?= htmlspecialchars($m['body']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>