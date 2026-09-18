<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
if ($code === '') {
    json_response(['error' => 'Enter a tracking code.'], 422);
}
if (strpos($code, 'SL-') !== 0) {
    $code = 'SL-' . $code;
}

$stmt = $pdo->prepare('SELECT * FROM reports WHERE code = ?');
$stmt->execute([$code]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['error' => 'No report found for that code.'], 404);
}

$stmt = $pdo->prepare('SELECT status, changed_at FROM report_status_history WHERE report_id = ? ORDER BY changed_at ASC');
$stmt->execute([$report['id']]);
$history = $stmt->fetchAll();

json_response(['report' => $report, 'history' => $history]);
