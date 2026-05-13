<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // import env variables
    try {
        //local env variabes
        require_once __DIR__ . '/../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $host = $_ENV['DB_HOST'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];
        $db   = $_ENV['DB_NAME'];
        $port   = $_ENV['DB_PORT'];

    } catch (\Throwable $th) {
        //get from hosting server environment
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $db = getenv('DB_NAME');
        $port   = getenv('DB_PORT');
    }


    $ssl_ca = file_exists(__DIR__ . "/ca.pem")
    ? __DIR__ . "/ca.pem"
    : "/secrets/aiven-ca";
    
    // Create connection
    $conn = mysqli_init();

    // Enable SSL
    mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

    if (!mysqli_real_connect(
        $conn,
        $host,
        $user,
        $pass,
        $db,
        $port,
        NULL,
        MYSQLI_CLIENT_SSL
    )) {
        die("Connection failed: " . mysqli_connect_error());
    }

    if (!$conn) {
        throw new Exception("DB connection failed");
    }


    return $conn;
