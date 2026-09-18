<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (!empty($_SESSION['admin_id'])) {
    json_response(['logged_in' => true, 'username' => $_SESSION['admin_username']]);
}
json_response(['logged_in' => false]);
