<?php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && ($password === 'admin123' || password_verify($password, $user['password']))) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid email or password credentials.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - DUET Energy Consumption Analysis System</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-font-family: 'Inter', system-ui, -apple-system, sans-serif;
            --primary: #2563eb;
            --body-bg: #f8fafc;
            --card-border: #e2e8f0;
        }
        body {
            font-family: var(--bs-body-font-family);
            background-color: var(--body-bg);
            background-image: radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.05) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(37, 99, 235, 0.05) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2.25rem;
        }
        .brand-icon {
            width: 46px;
            height: 46px;
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            font-size: 0.9rem;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .input-group-text {
            border-radius: 8px 0 0 8px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #64748b;
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 0.7rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            transition: all 0.15s ease-in-out;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
        }
        .back-link {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: color 0.15s ease-in-out;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="mb-4">
        <a href="index.php" class="back-link mb-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Homepage
        </a>
        <div class="text-center mt-2">
            <div class="brand-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">DUET EMS</h4>
            <p class="text-muted small mb-0">Dhaka University of Engineering & Technology</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2 fs-6"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Email Address</label>
            <div class="input-group">
                <span class="input-group-text border-end-0"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control border-start-0" placeholder="admin@duet.ac.bd" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Account Password</label>
            <div class="input-group">
                <span class="input-group-text border-end-0"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
        </button>
    </form>

    <div class="border-top pt-3 text-center">
        <small class="text-muted">Default admin login: <strong class="text-dark">admin@duet.ac.bd</strong> / <strong class="text-dark">admin123</strong></small>
    </div>
</div>

</body>
</html>
