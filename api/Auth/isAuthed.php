<?php
require '../../config/cors.php'; //allow access from webserver

// if (session_status() === PHP_SESSION_NONE) {
//     // session_start();
//     echo json_encode([
//         "logged_in" => false,
//     ]);
//     exit();
// }

// //check if user is logged in or not
// if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
//     //do not regenerate session id here, it breaks the session
//     echo json_encode([
//         "logged_in" => true,
//     ]);
// } else {
//     echo json_encode([
//         "logged_in" => false, //redirect to login page on frontend
//     ]);
// }

try {

    // Start/loading the session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
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