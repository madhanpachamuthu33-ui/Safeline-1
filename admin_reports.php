<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_admin();

$category = trim($_GET['category'] ?? '');
$severity = trim($_GET['severity'] ?? '');
$status   = trim($_GET['status'] ?? '');

$sql = 'SELECT * FROM reports WHERE 1=1';
$params = [];
if ($category !== '') { $sql .= ' AND category = ?'; $params[] = $category; }
if ($severity !== '') { $sql .= ' AND severity = ?'; $params[] = $severity; }
if ($status   !== '') { $sql .= ' AND status = ?';   $params[] = $status; }
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

json_response(['reports' => $reports]);
