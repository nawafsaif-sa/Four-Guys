<?php
// Copy db.example.php to db.local.php and configure your own local connection.
$configPath = __DIR__ . '/db.local.php';
if (!is_file($configPath)) {
    http_response_code(503);
    exit('Database setup required. Copy Backend/db.example.php to Backend/db.local.php and follow README.md.');
}
$config = require $configPath;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    exit('Database connection failed. Check Backend/db.local.php and that MySQL is running.');
}
