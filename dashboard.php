<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// KPI Aggregations (D4, D5)[cite: 1]
$total_kwh   = (float)$pdo->query("SELECT COALESCE(SUM(energy_kwh), 0) FROM energy_readings")->fetchColumn();
$total_cost  = (float)$pdo->query("SELECT COALESCE(SUM(cost), 0) FROM energy_readings")->fetchColumn();
$co2_kg      = round($total_kwh * 0.527, 2);
$open_alerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Active'")->fetchColumn();

// Daily Consumption Graph Data
$trend_data = $pdo->query("
    SELECT DATE_FORMAT(recorded_at, '%H:00') as hr, SUM(energy_kwh) as kwh 
    FROM energy_readings 
    GROUP BY hr 
    ORDER BY hr DESC LIMIT 12
")->fetchAll();
$trend_data = array_reverse($trend_data);

// Building Comparison Data
$bld_data = $pdo->query("
    SELECT b.name, COALESCE(SUM(r.energy_kwh), 0) as kwh
    FROM buildings b
    LEFT JOIN meters m ON b.building_id = m.building_id
    LEFT JOIN energy_readings r ON m.meter_id = r.meter_id
    GROUP BY b.building_id
")->fetchAll();

// Recent 5 Readings
$recent_readings = $pdo->query("
    SELECT r.*, m.meter_number, b.name as building_name 
    FROM energy_readings r 
    JOIN meters m ON r.meter_id = m.meter_id 
    JOIN buildings b ON m.building_id = b.building_id 
    ORDER BY r.recorded_at DESC LIMIT 5
")->fetchAll();
?>

<!-- Header Title Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-0">Energy Consumption Analytics</h4>
        <p class="text-muted small mb-0">Real-time telemetry and resource usage breakdown across DUET campus</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="reports.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i> Audit Reports
        </a>
        <a href="simulate.php" class="btn btn-primary btn-sm">
            <i class="bi bi-broadcast me-1"></i> Trigger Ingest
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Total kWh -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Total Consumption</span>
                    <h3 class="fw-bold text-dark mb-0" id="kpi-kwh"><?= number_format($total_kwh, 2) ?> <span class="fs-6 fw-normal text-muted">kWh</span></h3>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-lightning-charge"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Active</span> campus load
            </div>
        </div>
    </div>

    <!-- Calculated Cost -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Tariff Expense</span>
                    <h3 class="fw-bold text-dark mb-0" id="kpi-cost">৳ <?= number_format($total_cost, 2) ?></h3>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <span class="text-muted">Rate:</span> ৳ 9.50 per kWh
            </div>
        </div>
    </div>

    <!-- Carbon Footprint -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">CO₂ Footprint</span>
                    <h3 class="fw-bold text-dark mb-0" id="kpi-co2"><?= number_format($co2_kg, 2) ?> <span class="fs-6 fw-normal text-muted">kg</span></h3>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-cloud-haze2"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <span class="text-muted">Factor:</span> 0.527 kg / kWh
            </div>
        </div>
    </div>

    <!-- Active Alerts -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Threshold Incidents</span>
                    <h3 class="fw-bold text-danger mb-0" id="kpi-alerts"><?= $open_alerts ?> <span class="fs-6 fw-normal text-muted">Pending</span></h3>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-diamond"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <a href="alerts.php" class="text-danger fw-semibold text-decoration-none">Inspect incidents &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <!-- Trendline -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Energy Demand Profile (kWh)</h6>
                <span class="badge bg-light text-secondary border">Auto-synced</span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="demandChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Facility Breakdown -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-success me-2"></i>Usage by Facility</h6>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="buildingChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Ingested Telemetry Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Latest Ingested Telemetry</h6>
        <a href="readings.php" class="btn btn-link btn-sm text-decoration-none p-0">View All Logs &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Meter Hardware</th>
                    <th>Campus Facility</th>
                    <th>Voltage</th>
                    <th>Current</th>
                    <th>Demand Power</th>
                    <th>Energy (kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_readings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No telemetry logged yet. Emit test readings via the IoT Simulator.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_readings as $r): ?>
                    <tr>
                        <td><small class="text-muted"><?= $r['recorded_at'] ?></small></td>
                        <td><code><?= htmlspecialchars($r['meter_number']) ?></code></td>
                        <td><strong><?= htmlspecialchars($r['building_name']) ?></strong></td>
                        <td><?= number_format($r['voltage'], 1) ?> V</td>
                        <td><?= number_format($r['current'], 2) ?> A</td>
                        <td><?= number_format($r['power'], 2) ?> kW</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?= number_format($r['energy_kwh'], 3) ?> kWh</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Line Chart Setup
const ctxDemand = document.getElementById('demandChart').getContext('2d');
const demandChart = new Chart(ctxDemand, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($trend_data, 'hr')) ?>,
        datasets: [{
            label: 'kWh Consumed',
            data: <?= json_encode(array_column($trend_data, 'kwh')) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#2563eb'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});

// Doughnut Chart Setup
new Chart(document.getElementById('buildingChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($bld_data, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($bld_data, 'kwh')) ?>,
            backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } }
        },
        cutout: '70%'
    }
});

// Real-Time Background Synchronization Polling
setInterval(() => {
    fetch('api/live_stats.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('kpi-kwh').innerHTML = data.total_kwh + ' <span class="fs-6 fw-normal text-muted">kWh</span>';
            document.getElementById('kpi-cost').textContent = '৳ ' + data.total_cost;
            document.getElementById('kpi-co2').innerHTML = data.co2_kg + ' <span class="fs-6 fw-normal text-muted">kg</span>';
            document.getElementById('kpi-alerts').innerHTML = data.open_alerts + ' <span class="fs-6 fw-normal text-muted">Pending</span>';

            if (data.chart_labels.length > 0) {
                demandChart.data.labels = data.chart_labels;
                demandChart.data.datasets[0].data = data.chart_values;
                demandChart.update('none');
            }
        })
        .catch(err => console.error("Telemetry sync error:", err));
}, 5000);
</script>

<?php require_once 'includes/footer.php'; ?>