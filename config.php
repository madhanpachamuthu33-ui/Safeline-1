<?php
// Load variables from a .env file in the project root (KEY=VALUE per line),
// so you don't need to use `export` in the shell — just create a .env file
// (copy .env.example to .env and fill in your key). Real environment
// variables set another way still take priority.
$envPath = __DIR__ . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'"); // trim whitespace and surrounding quotes
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

// Database connection settings — edit these to match your local MySQL setup.
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'safeline';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed. Check config.php settings.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
