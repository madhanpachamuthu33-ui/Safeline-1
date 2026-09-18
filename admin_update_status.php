<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = read_json_body();
$code   = trim($input['code'] ?? '');
$status = trim($input['status'] ?? '');

$allowedStatuses = ['Received', 'Reviewing', 'Resolved'];
if (!in_array($status, $allowedStatuses, true)) {
    json_response(['error' => 'Invalid status.'], 422);
}

$stmt = $pdo->prepare('SELECT id FROM reports WHERE code = ?');
$stmt->execute([$code]);
$report = $stmt->fetch();
if (!$report) {
    json_response(['error' => 'Report not found.'], 404);
}

$stmt = $pdo->prepare('UPDATE reports SET status = ? WHERE id = ?');
$stmt->execute([$status, $report['id']]);

$stmt = $pdo->prepare('SELECT id FROM report_status_history WHERE report_id = ? AND status = ?');
$stmt->execute([$report['id'], $status]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status) VALUES (?, ?)');
    $stmt->execute([$report['id'], $status]);
}

json_response(['success' => true]);
