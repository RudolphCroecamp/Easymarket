<?php
    header('Content-Type: application/json');//return data in json format

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // var_dump($_POST);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB



    if (!isset(
        $_POST['productID'],
        $_POST['title'], 
        $_POST['price'],
        $_POST['category'],
        $_POST['condition'],
        $_POST['description'],
        $_POST['delivery']
    ))
    {
        echo json_encode(["error" => "Fill in all fields"]);
        exit;
    }

    
    //get data from client
    $productID = $_POST['productID'];
    $title = $_POST['title'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $condition = $_POST['condition'];
    $description = $_POST['description'];
    $delivery = $_POST['delivery'];

    $tags = json_decode($_POST['tags'], true);

    //quantity defualts to 1 if not provided
    if (!isset($_POST['quantity']) || $_POST['quantity'] == 0){
        $quantity = 1;
    }else{
        $quantity = $_POST['quantity'];
    }


    //check if whe have received images
    if (isset($_FILES['images'])){

    

        //verify upload directory exist
        $uploadDir = "uploads/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadedFiles = [];

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
            $filePath = $uploadDir . $productID . "_" . $image_char . ".jpg";//name of new image

            if (move_uploaded_file($tmpName, $filePath)) {
                $uploadedFiles[] = $filePath;
            }

            $image_char++;
        }

    }//end adding images

    

    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();



    try {
        //we get out product id above -> line 64

        //add listing details to db
        $insertProductStmt = $conn->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, category = ?, `condition` = ?, delivery = ?, amountType = ?, quantity = ?
            WHERE productID = ?
        ");

        //get userID from session
        $ownerID = $_SESSION['userID'];

        $insertProductStmt->bind_param(
            "ssdssssis", 
            $title, $description, $price, $category, $condition, $delivery, $amountType, $quantity, $productID
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


        //remove old tags
        $conn->query("DELETE FROM product_tags WHERE productID = '$productID'");

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
            "message"=>"Listing updated"
        ]);

    } catch (\Throwable $th) {
        //Rollback if anything fails
        $conn->rollback();
        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "message"=>"Could not update listing",
            "error"=>$th->getMessage()
        ]);
    }

    //Close statements and connection
    if (isset($insertProductStmt)) $insertProductStmt->close();
    if (isset($insertTagStmt)) $insertTagStmt->close();
    if (isset($insertProductTagStmt)) $insertProductTagStmt->close();

    $conn->close();
    die();