<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = read_json_body();
$code = trim($input['code'] ?? '');

if ($code === '') {
    json_response(['error' => 'Report code is required.'], 422);
}

$stmt = $pdo->prepare('SELECT id, image_path FROM reports WHERE code = ?');
$stmt->execute([$code]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['error' => 'Report not found.'], 404);
}

// report_status_history rows are removed automatically via ON DELETE CASCADE
$stmt = $pdo->prepare('DELETE FROM reports WHERE id = ?');
$stmt->execute([$report['id']]);

// Clean up the uploaded photo, if any
if (!empty($report['image_path'])) {
    $filePath = __DIR__ . '/../' . $report['image_path'];
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

json_response(['success' => true]);
