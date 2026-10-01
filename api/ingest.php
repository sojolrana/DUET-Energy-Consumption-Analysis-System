<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['meter_number'], $data['voltage'], $data['current'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid reading telemetry payload']);
    exit;
}

// 1. Verify Meter
$stmt = $pdo->prepare("SELECT m.meter_id, b.name as building_name FROM meters m JOIN buildings b ON m.building_id = b.building_id WHERE m.meter_number = ?");
$stmt->execute([$data['meter_number']]);
$meter = $stmt->fetch();

if (!$meter) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Meter hardware ID not recognized']);
    exit;
}

// 2. Calculate Power, Energy, & Tariff Cost (BDT: 9.05 per kWh)
$voltage   = (float)$data['voltage'];
$current   = (float)$data['current'];
$power_kw  = ($voltage * $current) / 1000.0;
$kwh       = isset($data['energy_kwh']) ? (float)$data['energy_kwh'] : ($power_kw * (5/60)); // Default 5 min window
$cost      = $kwh * 9.50; 
$recorded  = date('Y-m-d H:i:s');

// 3. Persist to D4 (Energy Readings)
$insert = $pdo->prepare("INSERT INTO energy_readings (meter_id, recorded_at, voltage, current, power, energy_kwh, cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert->execute([$meter['meter_id'], $recorded, $voltage, $current, $power_kw, $kwh, $cost]);

// 4. Threshold Alert Engine (Process 6.0 -> D5)
$alerts_created = [];
if ($voltage > 245.0) {
    $alt = $pdo->prepare("INSERT INTO alerts (title, type, location, time, status) VALUES (?, 'Overvoltage', ?, ?, 'Active')");
    $alt->execute(["High Voltage Spike detected: {$voltage}V", $meter['building_name'], $recorded]);
    $alerts_created[] = 'Overvoltage';
}
if ($current > 45.0) {
    $alt = $pdo->prepare("INSERT INTO alerts (title, type, location, time, status) VALUES (?, 'Overload', ?, ?, 'Active')");
    $alt->execute(["Sub-circuit Current Overload: {$current}A", $meter['building_name'], $recorded]);
    $alerts_created[] = 'Overload';
}

echo json_encode([
    'status'   => 'success',
    'reading'  => ['meter_id' => $meter['meter_id'], 'power_kw' => $power_kw, 'cost' => $cost],
    'alerts'   => $alerts_created
]);