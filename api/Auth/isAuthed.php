<?php
    require '../../config/cors.php';//allow access from webserver

    session_start();

    //check if user is logged in or not
    if(isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]===true){
        //do not regenerate session id here, it breaks the session
        echo json_encode([
            "logged_in" => true,
        ]);
    }else{
        echo json_encode([
            "logged_in" => false,//redirect to login page on frontend
        ]);
    }
