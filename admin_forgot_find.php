<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = read_json_body();
$username = trim($input['username'] ?? '');

if ($username === '') {
    json_response(['error' => 'Enter your username.'], 422);
}

$stmt = $pdo->prepare('SELECT security_question FROM admins WHERE username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin) {
    json_response(['error' => 'No account found with that username.'], 404);
}

json_response(['question' => $admin['security_question']]);
