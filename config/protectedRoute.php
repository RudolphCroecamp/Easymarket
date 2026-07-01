<?php
    require '../Auth/session_config.php';//set session details

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        http_response_code(401);
        exit(json_encode([
            "success" => false,
            "status" => 401,
            "error" => "Unauthorized"
        ]));
    }
