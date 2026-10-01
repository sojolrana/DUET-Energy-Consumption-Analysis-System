<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Fetch updated KPIs
$total_kwh   = (float)$pdo->query("SELECT COALESCE(SUM(energy_kwh), 0) FROM energy_readings")->fetchColumn();
$total_cost  = (float)$pdo->query("SELECT COALESCE(SUM(cost), 0) FROM energy_readings")->fetchColumn();
$co2_kg      = round($total_kwh * 0.527, 2);
$open_alerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'Active'")->fetchColumn();

// Fetch latest 10 readings for live trendline
$trend = $pdo->query("
    SELECT DATE_FORMAT(recorded_at, '%H:%i:%s') as t, energy_kwh 
    FROM energy_readings 
    ORDER BY reading_id DESC LIMIT 10
")->fetchAll();
$trend = array_reverse($trend);

echo json_encode([
    'total_kwh'   => number_format($total_kwh, 2),
    'total_cost'  => number_format($total_cost, 2),
    'co2_kg'      => number_format($co2_kg, 2),
    'open_alerts' => $open_alerts,
    'chart_labels'=> array_column($trend, 't'),
    'chart_values'=> array_column($trend, 'energy_kwh')
]);