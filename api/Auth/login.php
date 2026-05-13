<?php //login.php
    require '../../config/cors.php';//allow access to webserver

    require './session_config.php';//set session details

    $conn = require '../../config/dbconn.php';//connect to DB

    header('Content-Type: application/json');

    //check if all params has a value
    if(!isset($_POST["email"], $_POST["password"])){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Fill in all fields(1)",
        ]));
    }

    //initialise variables
    $email =$_POST["email"];
    $password = $_POST["password"];
    

    //check if values are empty
    if(empty($email) || empty($password)){
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

    //select user from auth table
    $sql = "SELECT userID, password, role FROM auth WHERE email = ?";

    //prepare sql command
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows === 1){//user was found
        $row = $result->fetch_assoc();

        $userID = $row['userID'];
        $hashedPassword = $row['password'];
        $role = $row['role'];

        //verify password
        if(password_verify($password, $hashedPassword)){
            //valid password
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['userID'] = $userID;
            $_SESSION['email'] = $email;
            $_SESSION['logged_in'] = true;
            $_SESSION['role'] = $role;

            exit(json_encode([
                "status"=>"success",
                "success"=>true,
                "publicUserInfo" => [
                    "role" => $role
                ],
                "message" => "Login Success"
            ]));
        }else{
            //invalid password
            exit(json_encode([
                "status"=>"failed",
                "success"=>false,
                "error" => "Invalid credentials"
            ]));
        }
    }else{
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Invalid credentials"
        ]));
    }




    //close and end connection and return login sucessfully
    exit(json_encode([
        "status"=>"failed",
        "success"=>false,
        "message" => "Login failed"
    ]));
    
    //close and end connection
    exit;
