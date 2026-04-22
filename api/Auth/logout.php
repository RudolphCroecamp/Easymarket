<?php
    header('Content-Type: application/json');
    
    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    require '../../config/cors.php';//allow access from webserver
    require './session_config.php';//set session details

    //delete user session
    session_unset();
    session_destroy();


    //notify client of successfull logout
    exit(json_encode([
        "status"=>"success",
        "success"=>true,
        "message" => "Logged out"
    ]));
