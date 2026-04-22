<?php

// $allowedOrigins = [
//     "http://127.0.0.1:5500",
//     "http://localhost:5500",
//     "http://127.0.0.1:8080",
//     "http://localhost:80",
// ];

// $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// if (in_array($origin, $allowedOrigins)) {
//     header("Access-Control-Allow-Origin: *");//$origin
//     header("Vary: Origin");
// } else {
//     // IMPORTANT: fail-safe for debugging (remove later if needed)
//     header("Access-Control-Allow-Origin: *");
// }

// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type");
// header("Access-Control-Allow-Credentials: true");
// header("Content-Type: application/json");

    header("Access-Control-Allow-Origin: https://easymarket-727523185751.europe-west1.run.app");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }