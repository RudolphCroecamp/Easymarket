<?php

$allowedOrigins = [
    "http://localhost",
    "http://127.0.0.1:8080",
    "https://easymarket-727523185751.europe-west1.run.app",
    "https://easymarket2-727523185751.africa-south1.run.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

// Only set CORS if origin exists and is allowed
if ($origin && in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
    
    // header("Access-Control-Allow-Origin: https://easymarket-727523185751.europe-west1.run.app");
    // header("Access-Control-Allow-Credentials: true");
    // header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    // header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
