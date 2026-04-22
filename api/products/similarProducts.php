<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB

    // Get page number from client

    $limit = 12;

    if(!isset($_GET["category"])){
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No category present"
        ]);
    }else{
        $category = $_GET["category"];
    }

    //select only available products
    $sql = "SELECT * FROM products WHERE deleted = FALSE AND category = ? ORDER BY name ASC LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $category, $limit);
    $stmt->execute();

    //get data from db
    $result = $stmt->get_result();

    //check if we have received rows form the db
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        //return products in json format
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "products"=>$products
        ]);

    }else{
        //return a failed message when no data was return from the db
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No products found"
        ]);
    }


    
    //close and end connection
    $stmt->close();
    $conn->close();
    die();
