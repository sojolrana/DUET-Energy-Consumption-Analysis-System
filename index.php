<?php
session_start();
require_once 'config/db.php';

// Fetch live statistics for the public landing view
$total_buildings = (int)$pdo->query("SELECT COUNT(*) FROM buildings WHERE status = 'Active'")->fetchColumn();
$total_meters    = (int)$pdo->query("SELECT COUNT(*) FROM meters WHERE status = 'Online'")->fetchColumn();
$total_kwh       = (float)$pdo->query("SELECT COALESCE(SUM(energy_kwh), 0) FROM energy_readings")->fetchColumn();
$total_alerts    = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Resolved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUET Energy Consumption Analysis System (DUET EMS)</title>
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
            --dark-navy: #0f172a;
            --body-bg: #ffffff;
            --card-border: #e2e8f0;
        }
        body {
            font-family: var(--bs-body-font-family);
            background-color: var(--body-bg);
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #f1f5f9;
        }
        .navbar-brand .brand-logo {
            width: 38px;
            height: 38px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .nav-link {
            font-weight: 500;
            color: #475569;
            font-size: 0.92rem;
            padding: 0.5rem 0.9rem !important;
            transition: color 0.15s ease-in-out;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .hero-section {
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 45%),
                        linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 5.5rem 0 4rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        .stat-banner {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            margin-top: -3rem;
            padding: 1.75rem;
        }
        .feature-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.75rem;
            height: 100%;
            transition: all 0.2s ease-in-out;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.06);
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1.25rem;
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        .footer {
            background-color: var(--dark-navy);
            color: #94a3b8;
            padding: 3.5rem 0 2rem 0;
        }
        /* Override Bootstrap text-muted specifically inside the dark footer */
        .footer .text-muted {
            color: #94a3b8 !important;
        }
        .footer hr {
            border-color: #334155 !important;
            opacity: 1;
        }
        .footer .footer-link {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }
        .footer .footer-link:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <div class="brand-logo me-2">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div>
                <span class="fw-bold text-dark fs-5 d-block leading-tight">DUET EMS</span>
                <small class="text-muted d-none d-sm-block" style="font-size: 0.7rem; letter-spacing: 0.02em;">Energy Consumption Analysis System</small>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#overview">Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Architecture</a></li>
                <li class="nav-item"><a class="nav-link" href="#metrics">Campus Live Stats</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About DUET</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-grid-1x2 me-1"></i> Open Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Staff / Admin Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section" id="overview">
    <div class="container text-center">
        <div class="hero-badge">
            <i class="bi bi-shield-check me-2"></i> Dhaka University of Engineering & Technology (DUET)
        </div>
        <h1 class="display-5 fw-bold text-dark mb-3" style="letter-spacing: -0.03em;">
            Smart Campus Energy Consumption <br class="d-none d-md-block">
            <span class="text-primary">& Power Quality Telemetry System</span>
        </h1>
        <p class="lead text-secondary mx-auto mb-4" style="max-width: 680px; font-size: 1.05rem;">
            A centralized IoT energy analytics platform providing real-time electrical sub-metering, automated threshold protection, power factor audits, and executive cost reporting across campus infrastructure.
        </p>
        <div class="d-flex justify-content-center gap-3 mb-5">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-speedometer2 me-2"></i> Access Operations Panel
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Portal
                </a>
            <?php endif; ?>
            <a href="#features" class="btn btn-outline-secondary btn-lg px-4">Explore Architecture</a>
        </div>
    </div>
</section>

<!-- Live Telemetry Strip -->
<div class="container" id="metrics">
    <div class="stat-banner">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3 border-end">
                <span class="text-muted small fw-semibold d-block mb-1">Active Buildings (D2)</span>
                <h3 class="fw-bold text-dark mb-0"><?= $total_buildings ?> <span class="fs-6 fw-normal text-muted">Facilities</span></h3>
            </div>
            <div class="col-6 col-md-3 border-end-md">
                <span class="text-muted small fw-semibold d-block mb-1">Online Meters (D3)</span>
                <h3 class="fw-bold text-primary mb-0"><?= $total_meters ?> <span class="fs-6 fw-normal text-muted">Nodes</span></h3>
            </div>
            <div class="col-6 col-md-3 border-end">
                <span class="text-muted small fw-semibold d-block mb-1">Energy Audited (D4)</span>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($total_kwh, 1) ?> <span class="fs-6 fw-normal text-muted">kWh</span></h3>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted small fw-semibold d-block mb-1">Resolved Alerts (D5)</span>
                <h3 class="fw-bold text-success mb-0"><?= $total_alerts ?> <span class="fs-6 fw-normal text-muted">Incidents</span></h3>
            </div>
        </div>
    </div>
</div>

<!-- Architecture & System Pillars -->
<section class="py-5 mt-4" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark mb-2">Core System Architecture</h2>
            <p class="text-muted small">Constructed strictly according to the DUET EMS 8-Process Data Flow Architecture (Processes 1.0 - 8.0)</p>
        </div>

        <div class="row g-4">
            <!-- 1. Real-time Telemetry Ingestion -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-broadcast-pin"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Process 4.0: IoT Ingestion</h5>
                    <p class="text-muted small mb-0">REST endpoints continuously ingest multi-parameter voltage, current, and active power loads directly into the readings store (D4).</p>
                </div>
            </div>

            <!-- 2. Analytics & Visual Auditing -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Process 5.0: Analytics Engine</h5>
                    <p class="text-muted small mb-0">Aggregates high-frequency sensor readings into load profiles, dynamic BDT utility billing tariffs, and campus CO₂ carbon footprint metrics.</p>
                </div>
            </div>

            <!-- 3. Automated Threshold Protection -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Process 6.0: Incident Monitoring</h5>
                    <p class="text-muted small mb-0">Real-time threshold engine triggers alerts (D5) for overvoltage spikes (&gt;245V) and circuit overload conditions (&gt;45A).</p>
                </div>
            </div>

            <!-- 4. Infrastructure & Meter Master Data -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Process 2.0 & 3.0: Master Data</h5>
                    <p class="text-muted small mb-0">Comprehensive management of DUET academic faculties, residence halls, and hardware meter serial associations (D2 & D3).</p>
                </div>
            </div>

            <!-- 5. Executive Reports & Exports -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Store D6: Management Audits</h5>
                    <p class="text-muted small mb-0">Generates downloadable CSV sheets and formatted printable audit sheets tailored for DUET Campus Management.</p>
                </div>
            </div>

            <!-- 6. Role-Based Access Control -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-person-lock"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Process 1.0 & 7.0: RBAC Security</h5>
                    <p class="text-muted small mb-0">Three-tiered authorization framework separating Admin full control, Staff/Technician editing, and Viewer auditing rights.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5 bg-light border-top border-bottom" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="text-primary fw-bold small text-uppercase">Institutional Deployment</span>
                <h3 class="fw-bold text-dark mt-1 mb-3">Dhaka University of Engineering & Technology</h3>
                <p class="text-secondary small mb-3">
                    DUET EMS empowers university engineers, department heads, and campus administration to optimize electrical efficiency, eliminate energy waste in laboratories and residential halls, and support green campus sustainability initiatives.
                </p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center text-dark small fw-semibold">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i> Sub-metering Integration
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center text-dark small fw-semibold">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i> Zero-loss Auditing
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center text-dark small fw-semibold">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i> Peak Load Forecasting
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center text-dark small fw-semibold">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i> Carbon Accounting
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3">Campus Sub-Stations Monitored</h5>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span><i class="bi bi-building me-2 text-primary"></i>Old Academic Building (OAB)</span>
                            <span class="badge bg-success-subtle text-success">Online</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span><i class="bi bi-building me-2 text-primary"></i>New Academic Building (NAB)</span>
                            <span class="badge bg-success-subtle text-success">Online</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span><i class="bi bi-building me-2 text-primary"></i>Dr. F. R. Khan Residence Hall</span>
                            <span class="badge bg-success-subtle text-success">Online</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-3">
                    <div class="brand-logo me-2">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <span class="fw-bold text-white fs-5">DUET EMS</span>
                </div>
                <p class="small text-muted mb-0" style="max-width: 450px;">
                    The DUET Energy Consumption Analysis System is an engineering information system designed for real-time monitoring and reporting of university electrical infrastructure.
                </p>
            </div>
            <div class="col-lg-3 col-6">
                <h6 class="text-white fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.05em;">Modules</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="login.php" class="footer-link">Telemetry Ingestion</a></li>
                    <li class="mb-2"><a href="login.php" class="footer-link">Demand Profiles</a></li>
                    <li class="mb-2"><a href="login.php" class="footer-link">Incident Engine</a></li>
                    <li class="mb-2"><a href="login.php" class="footer-link">Executive Audits</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-6">
                <h6 class="text-white fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.05em;">Portal Access</h6>
                <p class="small text-muted mb-3">Access restricted to authorized DUET personnel and engineers.</p>
                <a href="login.php" class="btn btn-sm btn-outline-light px-3">
                    <i class="bi bi-lock me-1"></i> Sign In to Portal
                </a>
            </div>
        </div>
        <hr class="my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
            <div>&copy; <?= date('Y') ?> Dhaka University of Engineering & Technology (DUET). All rights reserved.</div>
            <div class="mt-2 mt-md-0">DUET Energy Consumption Analysis System</div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>