<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$_SESSION['chatbot_history'] = [];
json_response(['ok' => true]);
