<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

$input = read_json_body();
$username         = trim($input['username'] ?? '');
$password         = $input['password'] ?? '';
$securityQuestion = trim($input['security_question'] ?? '');
$securityAnswer   = trim($input['security_answer'] ?? '');

if ($username === '' || $password === '' || $securityQuestion === '' || $securityAnswer === '') {
    json_response(['error' => 'Please fill in all fields.'], 422);
}
if (strlen($password) < 4) {
    json_response(['error' => 'Password must be at least 4 characters.'], 422);
}

$stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    json_response(['error' => 'That username is already taken.'], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$answerHash   = password_hash(strtolower($securityAnswer), PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO admins (username, password_hash, security_question, security_answer_hash) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$username, $passwordHash, $securityQuestion, $answerHash]);

$_SESSION['admin_id'] = $pdo->lastInsertId();
$_SESSION['admin_username'] = $username;

json_response(['success' => true, 'username' => $username]);
