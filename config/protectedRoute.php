<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        http_response_code(401);
        exit(json_encode([
            "success" => false,
            "error" => "Unauthorized"
        ]));
    }
