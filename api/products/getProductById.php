<?php
    header('Content-Type: application/json');//return data in json format

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB



    //check if server received a productID
    if(!isset($_GET['productID'])){

        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Invalid productID"
        ]);

        $stmt->close();
        $conn->close();
        die();
    }else{
        $productID = $_GET['productID'];
    }

    //select only available products
    $sql ="
        SELECT p.*, u.fname, u.lname, u.upvotes, u.downvotes
        FROM products p
        JOIN users u ON p.ownerID = u.userID
        WHERE p.deleted = FALSE AND p.productID = ?
        ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $productID);
    $stmt->execute();

    //get data from db
    $product_result = $stmt->get_result();

    //get all tags
    $sql_tags = "
        SELECT t.name 
        FROM tags t 
        JOIN product_tags pt ON t.tagID = pt.tagID
        WHERE pt.productID = ?
    ";
    $stmt = $conn->prepare($sql_tags);
    $stmt->bind_param("s", $productID);
    $stmt->execute();
    //get data from db
    $tags_result = $stmt->get_result();

    //check if we have received rows form the db
    if ($product_result && $product_result->num_rows > 0) {
        //add product info to array of product 
        $product = $product_result->fetch_assoc();

        $tags = [];
        while ($row = $tags_result->fetch_assoc()) {
            $tags[] = $row;
        }

        //return products in json format
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "product"=>$product,
            "tags"=>$tags
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
