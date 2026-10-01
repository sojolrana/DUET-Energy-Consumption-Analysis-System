<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$message = '';
if (isset($_POST['trigger_simulation'])) {
    $meters = $pdo->query("SELECT meter_number FROM meters WHERE status = 'Online'")->fetchAll();
    $count = 0;
    foreach ($meters as $m) {
        $voltage = rand(210, 250); // Occasionally trips > 245V alert
        $current = rand(10, 50);   // Occasionally trips > 45A overload
        $payload = json_encode([
            'meter_number' => $m['meter_number'],
            'voltage'      => $voltage,
            'current'      => $current,
            'energy_kwh'   => round(($voltage * $current * 0.08) / 1000, 3)
        ]);

        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/ingest.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        $count++;
    }
    $message = "Successfully pushed simulated telemetry readings for {$count} active meters.";
}
?>
<div class="container-fluid">
    <h3 class="fw-bold mb-3">IoT Energy Sensor Simulation Interface</h3>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm p-4">
        <p class="text-muted">Simulate telemetry packets sent by external hardware sensors.</p>
        <form method="POST">
            <button type="submit" name="trigger_simulation" class="btn btn-warning btn-lg">
                <i class="bi bi-broadcast me-2"></i> Emit Simulated Meter Pulses 
            </button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>