<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

// This endpoint now receives multipart/form-data (text fields + optional file),
// not JSON, so we read from $_POST / $_FILES directly.
$category    = trim($_POST['category'] ?? '');
$severity    = trim($_POST['severity'] ?? '');
$location    = trim($_POST['location'] ?? '');
$occurredAt  = trim($_POST['occurred_at'] ?? '');
$description = trim($_POST['description'] ?? '');
$contactInfo = trim($_POST['contact_info'] ?? '');

$allowedCategories = ['Safety Incident', 'Harassment / Bullying', 'Drug / Substance Use', 'Facility / Maintenance', 'Academic Integrity', 'Other'];
$allowedSeverities = ['Low', 'Medium', 'High', 'Critical'];

if (!in_array($category, $allowedCategories, true)) {
    json_response(['error' => 'Please select a valid category.'], 422);
}
if (!in_array($severity, $allowedSeverities, true)) {
    json_response(['error' => 'Please select a severity level.'], 422);
}
if ($description === '') {
    json_response(['error' => 'Please describe what happened.'], 422);
}

// ---- Optional photo upload ----
$imagePath = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Photo upload failed. Please try again or submit without a photo.'], 422);
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxBytes) {
        json_response(['error' => 'Photo is too large. Max size is 5MB.'], 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowedMimes[$mime])) {
        json_response(['error' => 'Only JPG, PNG, WEBP, or GIF photos are allowed.'], 422);
    }

    $ext = $allowedMimes[$mime];
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $filename = 'rpt_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        json_response(['error' => 'Could not save the photo. Please try again.'], 500);
    }

    $imagePath = 'uploads/' . $filename;
}

$code = generate_report_code($pdo);

$stmt = $pdo->prepare(
    'INSERT INTO reports (code, category, severity, location, occurred_at, description, contact_info, image_path, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $code,
    $category,
    $severity,
    $location !== '' ? $location : null,
    $occurredAt !== '' ? $occurredAt : null,
    $description,
    $contactInfo !== '' ? $contactInfo : null,
    $imagePath,
    'Received',
]);
$reportId = $pdo->lastInsertId();

$stmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status) VALUES (?, ?)');
$stmt->execute([$reportId, 'Received']);

$stmt = $pdo->prepare('SELECT code, category, severity, image_path, created_at FROM reports WHERE id = ?');
$stmt->execute([$reportId]);
$report = $stmt->fetch();

json_response(['success' => true, 'report' => $report]);
