<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver

    $conn =require '../../config/dbconn.php';//connect to DB


    //check if all params has a value
    if(!isset($_POST["fname"], $_POST["lname"], $_POST["email"], $_POST["password"])){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Fill in all fields(1)",
        ]));
    }

    //initialise variables
    $fname = $_POST["fname"];
    $lname = $_POST["lname"];
    $email =$_POST["email"];
    $password = $_POST["password"];
    

    //check if values are empty
    if(empty($fname) || empty($lname) || empty($email) || empty($password)){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Fill in all fields(2)"
        ]));
    }

    //validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Invalid email address"
        ]));
    }


    //check if email already exist
    //email is our unique identifier
    $sql = "SELECT * FROM users where email = ? LIMIT 1";

    //prepare sql command
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    //get data from db
    $result = $stmt->get_result();

    //check if we have received rows form the db -> meaning an email was found
    if ($result && $result->num_rows > 0) {
        //close and end connection and return a failed message when user already exist
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>"A user with the same email already exist"
        ]));
    }



    //finally add the user to the db
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //create a user id
    // $userID =  uniqid('', true);
    // $userID = bin2hex(random_bytes(16));
    require_once '../../config/generateGUID.php';//connect to DB
    $userID = generateGUID();
    $role = "client";

    //add user to auth table
    $sql = "INSERT INTO auth (userID, email, password, role) VALUES (?, ?, ?, ?)";

    //prepare sql command
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $userID, $email, $password_hash, $role);
    $stmt->execute();

    //check if user was added to auth table successfully
    if ($stmt->affected_rows <= 0) {
        //close and end connection and return a failed message when user could not be added
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>"Could not add user to db(1)"
        ]));
    }

    //add user to user table if added to auth table sucessfully
    $sql = "INSERT INTO users (userID, email, fname, lname) VALUES (?, ?, ?, ?)";

    //prepare sql command
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $userID, $email, $fname, $lname);
    $stmt->execute();
    

    //check if user was added to user table
    if ($stmt->affected_rows <= 0) {
        //close and end connection and return a failed message when user could not be added
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>"Could not add user to db(2)"
        ]));
    }

    //close and end connection and return user added to db sucessfully
    exit(json_encode([
        "status"=>"success",
        "success"=>true,
        "message" => "User added to db"
    ]));
    
    //close and end connection
    exit;
