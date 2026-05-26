<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Throwable $e) {
    // ignore if not available in production
}

/**
 * Detect environment
 */
$isCloudRun = getenv('K_SERVICE') !== false;

if ($isCloudRun) {

    // ✅ CLOUD RUN (Cloud SQL socket)
    $instance = getenv('CLOUDSQL_CONNECTION_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');

    $socket = "/cloudsql/" . $instance;

    $conn = new mysqli(null, $user, $pass, $db, null, $socket);

} else {

    // ✅ LOCAL (XAMPP or testing with public IP)
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'easymarket-user';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'easymarket';
    $port = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($host, $user, $pass, $db, $port);
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

return $conn;




// $host = '127.0.0.1';
// $user = 'easymarket-user';
// $pass = 'mlzf},i]TP95rqU[';
// $db   = 'easymarket';
// $port = 3306;

// $conn = new mysqli($host, $user, $pass, $db, $port);