<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    

    $jsonData = json_decode(file_get_contents('php://input'),true);

    // Get page number from client
    $page = isset($jsonData['page']) ? (int)$jsonData['page'] : 1;
    $limit = 20;

    // Calculate offset
    $offset = ($page - 1) * $limit;
    //check if data is set and not empty
    if (!isset($jsonData["min"], $jsonData["max"], $jsonData["lat"], $jsonData["long"], $jsonData["radius"])) {
        echo json_encode([
                "status" => "failed",
                "success" => false,
                "error" => "Fill in all fields",
                "fields" => $jsonData
            ]);
            exit;
    }

    $minPrice = (int)$jsonData["min"];
    $maxPrice = (int)$jsonData["max"];
    $lat = (float)$jsonData["lat"];
    $long = (float)$jsonData["long"];
    $radius = (int)$jsonData["radius"];

    $minDistance = 0;

    $cacheDir = __DIR__ . "/cache";
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $cacheLat = (int)$lat;
    $cacheLong = (int)$long;

    $cacheKey = md5("$cacheLat-$cacheLong-$radius-$minPrice-$maxPrice-$page");
    $cacheFile = $cacheDir . "/$cacheKey.json";

    // check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 120) {
        echo json_encode([
            "status" => "success",
            "success" => true,
            "products" => json_decode(file_get_contents($cacheFile), true)
        ]);
        exit;
    }


    //select only available products in close radius

    $sql = 
        "SELECT *,
        (6371 * ACOS(
            COS(RADIANS(?)) *
            COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) *
            SIN(RADIANS(latitude))
        )) AS distance
        FROM products
        WHERE price BETWEEN ? AND ?
        HAVING distance BETWEEN ? AND ?
        ORDER BY distance ASC
        LIMIT ? OFFSET ?
        ";

    $getProductsStmt = $conn->prepare($sql);

    $getProductsStmt->bind_param(
        "dddiiiiii", $lat, $long, $lat, $minPrice, $maxPrice, $minDistance, $radius, $limit, $offset
    );

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
            "products" => $products,
            "fields" => $jsonData
        ]);

        file_put_contents($cacheFile, json_encode($products));
    }else{
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "No products found",
            "fields" => $jsonData
        ]);
    }

    


    
    //close and end connection
    $getProductsStmt->close();
    $conn->close();
    die();
