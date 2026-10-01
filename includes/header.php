<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUET Energy Consumption Analysis System</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bs-body-font-family: 'Inter', system-ui, -apple-system, sans-serif;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-active-bg: #2563eb;
            --body-bg: #f8fafc;
            --card-border: #e2e8f0;
        }
        body {
            background-color: var(--body-bg);
            font-family: var(--bs-body-font-family);
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        /* Sidebar Styling */
        .sidebar {
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            border-right: 1px solid #1e293b;
        }
        .sidebar-brand {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #1e293b;
        }
        .nav-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin: 1.2rem 0 0.4rem 0.75rem;
        }
        .sidebar .nav-link {
            color: var(--sidebar-text);
            font-size: 0.88rem;
            font-weight: 500;
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            transition: all 0.15s ease-in-out;
        }
        .sidebar .nav-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--sidebar-active-bg);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        /* Card & Table Styling */
        .card {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
        }
        .stat-card {
            padding: 1.25rem;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        .stat-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.25rem;
        }
        .table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1rem;
        }
        .table tbody td {
            font-size: 0.88rem;
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.88rem;
            padding: 0.55rem 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 0.5rem 0.95rem;
        }
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
            <div class="sidebar-brand d-flex align-items-center mb-3">
                <div class="bg-primary rounded-3 p-2 text-white me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-lightning-charge-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-white fw-bold mb-0" style="letter-spacing: -0.02em;">DUET EMS</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Energy Analytics</small>
                </div>
            </div>

            <div class="nav-section-title">Core Operations</div>
            <ul class="nav flex-column mb-0">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="readings.php" class="nav-link <?= $current_page == 'readings.php' ? 'active' : '' ?>">
                        <i class="bi bi-activity"></i> Telemetry Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="alerts.php" class="nav-link <?= $current_page == 'alerts.php' ? 'active' : '' ?>">
                        <i class="bi bi-shield-exclamation"></i> Active Alerts
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Master Data</div>
            <ul class="nav flex-column mb-0">
                <li class="nav-item">
                    <a href="buildings.php" class="nav-link <?= $current_page == 'buildings.php' ? 'active' : '' ?>">
                        <i class="bi bi-buildings"></i> Buildings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="meters.php" class="nav-link <?= $current_page == 'meters.php' ? 'active' : '' ?>">
                        <i class="bi bi-cpu"></i> Energy Meters
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Reporting & System</div>
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-bar-graph"></i> Energy Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                        <i class="bi bi-chat-left-dots"></i> Messaging
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> User Roles
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item mt-2">
                    <a href="simulate.php" class="nav-link text-info bg-dark bg-opacity-50">
                        <i class="bi bi-broadcast"></i> IoT Simulator
                    </a>
                </li>
            </ul>

            <hr style="border-color: #1e293b;" class="my-3">

            <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background: #1e293b;">
                <div class="overflow-hidden me-2">
                    <span class="d-block text-white fw-semibold text-truncate" style="font-size: 0.84rem;"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size: 0.65rem;"><?= htmlspecialchars($_SESSION['role']) ?></span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger p-1 px-2" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </nav>

        <!-- Main Workspace -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
<?php endif; ?>