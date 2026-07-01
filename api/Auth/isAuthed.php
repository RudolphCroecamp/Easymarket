<?php
require '../../config/cors.php'; //allow access from webserver
require './session_config.php'; //set session details
try {

    // Start/loading the session
    if (session_status() === PHP_SESSION_NONE) {
        throw new Exception("No login session created");
    }

    // Check if session cookie exists
    if (!isset($_COOKIE[session_name()])) {
        throw new Exception("No valid session for user");
    }

    // Check if user is logged in
    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {

        echo json_encode([
            "logged_in" => true,
        ]);
    } else {

        echo json_encode([
            "logged_in" => false,
        ]);
    }
} catch (\Throwable $th) {

    echo json_encode([
        "logged_in" => false,
        "error" => $th->getMessage()
    ]);
}
