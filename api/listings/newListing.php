<?php
    header('Content-Type: application/json');//return data in json format

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // var_dump($_POST);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB


    //check if whe have received images
    if (!isset($_FILES['images'])){
        echo json_encode(["error" => "No images uploaded"]);
        exit;
    }

    if (!isset(
        $_POST['title'], 
        $_POST['price'],
        $_POST['category'],
        $_POST['condition'],
        $_POST['description'],
        $_POST['delivery'],
        $_POST['amountType'],
        $_POST['latitude'],
        $_POST['longitude']
        
    ))
    {
        echo json_encode(["error" => "Fill in all fields"]);
        exit;
    }

    //get data from client
    $title = $_POST['title'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $condition = $_POST['condition'];
    $description = $_POST['description'];
    $delivery = $_POST['delivery'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $amountType = $_POST['amountType'];

    //quantity defualts to 1 if not provided
    if (!isset($_POST['quantity']) || $_POST['quantity'] == 0){
        $quantity = 1;
    }else{
        $quantity = $_POST['quantity'];
    }

    //verify upload directory exist
    $uploadDir = "uploads/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFiles = [];

    require_once '../../config/generateGUID.php';//connect to DB
    $productID = generateGUID();// 2a38e78c-99be-4b32-a5f5-cac84f9efccf
    $image_char = "a";


    //save all the images from client to db
    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {

        $fileName = $_FILES['images']['name'][$index];
        $fileType = $_FILES['images']['type'][$index];
        $fileError = $_FILES['images']['error'][$index];

        if ($fileError !== 0) {
            continue; //skip failed uploads
        }

        //validate type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }


        //save image as /uploads/2a38e78c-99be-4b32-a5f5-cac84f9efccf_a.jpg
        
        //rename file
        //$extension = pathinfo(basename($fileName), PATHINFO_EXTENSION);//get the image extention
        $filePath = $uploadDir . $productID . "_" . $image_char . ".jpg";//name of new image

        if (move_uploaded_file($tmpName, $filePath)) {
            $uploadedFiles[] = $filePath;
        }

        $image_char++;
    }



    $tags = json_decode($_POST['tags'], true);

    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();



    try {
        //we get out product id above -> line 64

        //add listing details to db
        $insertProductStmt = $conn->prepare("
            INSERT INTO products 
            (productID, ownerID, name, description, price, category, sold, deleted, `condition`, delivery, latitude, longitude, amountType, quantity) 
            VALUES (?,?,?,?,?,?, FALSE, FALSE, ?,?,?,?,?,?)
        ");
        //get userID from session
        $ownerID = $_SESSION['userID'];

        $insertProductStmt->bind_param(
            "ssssdsssddsi", 
            $productID, $ownerID, $title, $description, $price, $category, $condition, $delivery, $latitude, $longitude, $amountType, $quantity
        );

        $insertProductStmt->execute();

        // Prepare statements
        $insertTagStmt = $conn->prepare("
            INSERT INTO tags (name)
            VALUES (?)
            ON DUPLICATE KEY UPDATE tagID = LAST_INSERT_ID(tagID)
        ");

        $insertProductTagStmt = $conn->prepare("
            INSERT IGNORE INTO product_tags (productID, tagID)
            VALUES (?, ?)
        ");

        //add all tags to tags database
        foreach ($tags as $tag) {

            //Clean tag
            $cleanTag = strtolower(trim($tag));

            if ($cleanTag === '') continue;

            //Insert or reuse tag
            $insertTagStmt->bind_param("s", $cleanTag);
            $insertTagStmt->execute();

            //Get tag ID
            $tagID = $conn->insert_id;

            //Link product to tag
            $insertProductTagStmt->bind_param("si", $productID, $tagID);
            $insertProductTagStmt->execute();
        }

        $conn->commit();
        //return success message after all product details have been added
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "message"=>"Listing added"
        ]);

    } catch (\Throwable $th) {
        //Rollback if anything fails
        $conn->rollback();
        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "message"=>"Could not add listing",
            "error"=>$th->getMessage()
        ]);
    }

    //Close statements and connection
    if (isset($insertProductStmt)) $insertProductStmt->close();
    if (isset($insertTagStmt)) $insertTagStmt->close();
    if (isset($insertProductTagStmt)) $insertProductTagStmt->close();

    $conn->close();
    die();