<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/adminOnly.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    

    $jsonData = json_decode(file_get_contents('php://input'),true);

    // Get page number from client
    $page = isset($jsonData['page']) ? (int)$jsonData['page'] : 1;
    $limit = 10;

    // Calculate offset
    $offset = ($page - 1) * $limit;

    //check if data is set and not empty
    if (!isset($jsonData["page"])) {
        echo json_encode([
                "status" => "failed",
                "success" => false,
                "error" => "Fill in all fields",
                "fields" => $jsonData
            ]);
            exit;
    }



    //create thee cache dir if not exist
    $cacheDir = __DIR__ . "/listings/cache";
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }


    $cacheKey = md5("$page");
    $cacheFile = $cacheDir . "/$cacheKey.json";

    // check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 120) {
        echo json_encode([
            "status" => "success",
            "success" => true,
            "listings" => json_decode(file_get_contents($cacheFile), true)
        ]);
        exit;
    }


    $sql = "SELECT * FROM products LIMIT ? OFFSET ?";

    // $sql = "SELECT productID, ownerID, name, description, price, category, subcategory, created_at, condition, delivery FROM products LIMIT ? OFFSET ?";


    $getlistingsStmt = $conn->prepare($sql);

    $getlistingsStmt->bind_param("ii", $limit, $offset);

    $getlistingsStmt->execute();

    //get data from db
    $listings_result = $getlistingsStmt->get_result();
    

    $listings = [];

    //check if we have received rows form the db
    if ($listings_result && $listings_result->num_rows > 0) {
        while ($listing = $listings_result->fetch_assoc()) {
            $listings[] = $listing;
        }

        echo json_encode([
            "status" => "success",
            "success" => true,
            "listings" => $listings,
            "fields" => $jsonData
        ]);


        //add data to cache
        file_put_contents($cacheFile, json_encode($listings));
    }else{
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "No listings found",
            "fields" => $jsonData
        ]);
    }

    


    
    //close and end connection
    $getlistingsStmt->close();
    $conn->close();
    die();
