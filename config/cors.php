<?php

    $allowedOrigins = [
        "http://localhost",
        "http://localhost/Easymarket",
        "http://127.0.0.1:5500",
        "http://localhost:5500",
        "http://127.0.0.1:8080",
        "https://easymarket-727523185751.europe-west1.run.app"
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");//
        header("Vary: Origin");
    } else {
        http_response_code(403);
        exit(json_encode(["error" => "CORS blocked"]));
    }

    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");

    // MUST be first response for preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
    
    // header("Access-Control-Allow-Origin: https://easymarket-727523185751.europe-west1.run.app");
    // header("Access-Control-Allow-Credentials: true");
    // header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    // header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
