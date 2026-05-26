<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
} catch (\Throwable $e) {
    // ignore in production
}

$isCloudRun = getenv('K_SERVICE') !== false;

$conn = mysqli_init();

if ($isCloudRun) {
    $instance = getenv('CLOUDSQL_CONNECTION_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');

    $socket = "/cloudsql/" . $instance;

    if (!mysqli_real_connect(
        $conn,
        null,
        $user,
        $pass,
        $db,
        null,
        $socket
    )) {
        die("Cloud Run DB connection failed: " . mysqli_connect_error());
    }

} else {
    $host = $_ENV['DB_HOST'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $db   = $_ENV['DB_NAME'];
    $port   = $_ENV['DB_PORT'];

    $useSSL = file_exists(__DIR__ . "/server-ca.pem");

    if (!mysqli_real_connect(
        $conn,
        $host,
        $user,
        $pass,
        $db,
        $port,
        null,
        $useSSL ? MYSQLI_CLIENT_SSL : 0
    )) {
        die("Local DB connection failed: " . mysqli_connect_error());
    }
}

/**
 * Final safety check
 */
if (!$conn) {
    die("Database connection not initialized");
}

return $conn;