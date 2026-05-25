<?php
    header('Content-Type: application/json');
    
    require '../../config/cors.php';//allow access from webserver
    require './session_config.php';//set session details

    //Clear all session variables
    $_SESSION = [];

    //Destroy the session on the server
    session_destroy();

    //Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    //notify client of successfull logout
    exit(json_encode([
        "status"=>"success",
        "success"=>true,
        "message" => "Logged out"
    ]));

    // // 4. Redirect to login page
    // header("Location: login.php");
    // exit;