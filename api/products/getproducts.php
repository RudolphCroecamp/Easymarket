<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    //require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;

    // Calculate offset
    $offset = ($page - 1) * $limit;

    //select only available products
    $getProductsStmt = $conn->prepare("SELECT * FROM products WHERE deleted = FALSE ORDER BY name ASC LIMIT ? OFFSET ?");
    $getProductsStmt->bind_param("ii", $limit, $offset);
    $getProductsStmt->execute();

    //get data from db
    $product_result = $getProductsStmt->get_result();
    
    //get product imageUrl
    $getProductImageUrlStmt = $conn->prepare("SELECT imageUrl 
        FROM product_images 
        WHERE productID = ? 
        AND isPrimary = 1
        LIMIT 1
    ");

    $products = [];

    //check if we have received rows form the db
    if ($product_result && $product_result->num_rows > 0) {
        while ($product = $product_result->fetch_assoc()) {

            $productID = $product["productID"];

            $getProductImageUrlStmt->bind_param("s", $productID);
            $getProductImageUrlStmt->execute();

            $images_result = $getProductImageUrlStmt->get_result();
            $img = $images_result->fetch_assoc();

            $product["primaryImage"] = $img["imageUrl"] ?? null;

            $products[] = $product;
        }

        echo json_encode([
            "status" => "success",
            "success" => true,
            "products" => $products
        ]);
    }


    
    //close and end connection
    $getProductsStmt->close();
    $conn->close();
    die();
