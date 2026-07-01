<?php
    // session_start();
    require './session_config.php';//set session details

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
        http_response_code(401);
        exit(json_encode([
            "status" => 401,
            "success" => false,
            "error" => "Unauthorized, Admins Only"
        ]));
    }
