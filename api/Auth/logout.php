<?php
    header('Content-Type: application/json');
    
    require '../../config/cors.php';//allow access from webserver
    require './session_config.php';//set session details

    //delete user session
    session_destroy();


    //notify client of successfull logout
    exit(json_encode([
        "status"=>"success",
        "success"=>true,
        "message" => "Logged out"
    ]));
