<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5;

    // Calculate offset
    $offset = ($page - 1) * $limit;

    if(!isset($_GET["productID"])){
        //return a failed message when no data was return from the db
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No productID provided"
        ]);
    }else{
        $productID = $_GET["productID"];
    }

    //select only available products
    $sql = "SELECT fname, lname, rating, created_at, comment FROM comments WHERE productID = ? ORDER BY created_at ASC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $productID, $limit, $offset);
    $stmt->execute();

    //get data from db
    $result = $stmt->get_result();

    //check if we have received rows form the db
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        //return products in json format
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "comments"=>$comments
        ]);

    }else{
        //return a failed message when no data was return from the db
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No comments found"
        ]);
    }


    
    //close and end connection
    $stmt->close();
    $conn->close();
    die();
