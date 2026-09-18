<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = read_json_body();
$username    = trim($input['username'] ?? '');
$answer      = trim($input['answer'] ?? '');
$newPassword = $input['new_password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify(strtolower($answer), $admin['security_answer_hash'])) {
    json_response(['error' => 'That answer is not correct.'], 401);
}
if (strlen($newPassword) < 4) {
    json_response(['error' => 'New password must be at least 4 characters.'], 422);
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
$stmt->execute([$newHash, $admin['id']]);

json_response(['success' => true]);
