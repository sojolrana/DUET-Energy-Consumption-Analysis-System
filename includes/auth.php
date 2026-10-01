<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function hasRole($roles): bool {
    if (!isLoggedIn()) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles, true);
    }
    return $_SESSION['role'] === $roles;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        http_response_code(403);
        echo "<div style='font-family: sans-serif; text-align: center; padding-top: 50px;'>
                <h1 style='color: #dc3545;'>403 Forbidden</h1>
                <p>Your current role (<strong>" . htmlspecialchars($_SESSION['role'] ?? 'Guest') . "</strong>) lacks permission to perform this action.</p>
                <a href='dashboard.php' style='color: #0d6efd;'>Return to Dashboard</a>
              </div>";
        exit;
    }
}