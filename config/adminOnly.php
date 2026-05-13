<?php
    // session_start();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
        http_response_code(401);
        exit(json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "Unauthorized, Admins Only"
        ]));
    }
