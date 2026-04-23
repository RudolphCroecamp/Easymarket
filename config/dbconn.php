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
        $user = $_ENV['DB_USERNAME'];
        $pass = $_ENV['DB_PASS'];
        $db   = $_ENV['DB_NAME'];
    } catch (\Throwable $th) {
        //get from hosting server environment
        $host = getenv('DB_HOST');
        $user = getenv('DB_USERNAME');
        $pass = getenv('DB_PASS');
        $name = getenv('DB_NAME');
    }

    
    
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode([
                "status"=>"failed",
                "success"=>false,
                "error"=>"Could not connect to db"
            ]);
        die();
    }
