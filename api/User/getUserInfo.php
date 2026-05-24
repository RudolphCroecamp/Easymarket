<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB


    try {
        $userID = $_SESSION["userID"];

        $userStmt = $conn->prepare("SELECT email, fName, lName, cell FROM users WHERE userID = ?");
        $userStmt->bind_param("s", $userID);
        $userStmt->execute();

        $userInfo = $userStmt->get_result();

        if($userInfo && $userInfo->num_rows > 0){
            //return products in json format

            $user = $userInfo->fetch_assoc();

            echo json_encode([
                "status" => "success",
                "success" => true,
                "user" => $user
            ]);
        }else{
            throw new Exception("No user found");
        }


    } catch (\Throwable $th) {
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "No users found"
        ]);
    }


    //close and end connection
    $userStmt->close();
    $conn->close();
    die();
