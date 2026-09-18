<?php

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function read_json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generate_report_code(PDO $pdo) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = 'SL-';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare('SELECT id FROM reports WHERE code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

function require_admin() {
    if (empty($_SESSION['admin_id'])) {
        json_response(['error' => 'Not authenticated.'], 401);
    }
}
