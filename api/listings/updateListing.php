<?php
    header('Content-Type: application/json');//return data in json format

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    $hasImages = false;
    //check if whe have received images (optional)
    if (isset($_FILES['images'])){
        $hasImages = true;
    }

    if($hasImages){
        if ($_FILES['images']['size'][0] > 5 * 1024 * 1024) {
            echo json_encode(["error" => "Image too large (max 5MB)"]);
            exit;
        }
    }
    

    if (!isset(
        $_POST['productID'],
        $_POST['title'], 
        $_POST['price'],
        $_POST['condition'],
        $_POST['description'],
        $_POST['delivery'],
        $_POST['province'],
        $_POST['city'],
        $_POST['category'],
        $_POST['subcategory'],
        $_POST['latitude'],
        $_POST['longitude'],
        $_POST['quantity'],
        $_POST['numberOfCurrentImages'],
        $_POST["filesToKeep"],
        // $_POST["filesToDelete"]
    ))
    {
        echo json_encode(["error" => "Fill in all fields"]);
        exit;
    }

    //get data from client
    $productID = $_POST['productID'];
    $title = $_POST['title'];
    $price = $_POST['price'];
    $condition = $_POST['condition'];
    $description = $_POST['description'];
    $delivery = $_POST['delivery'];
    $province = $_POST['province'];
    $city = $_POST['city'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $quantity = $_POST['quantity'];
    $numberOfCurrentImages = $_POST["numberOfCurrentImages"];

    $filesToKeep = json_decode($_POST["filesToKeep"] ?? "[]", true);
    // $filesToDelete = json_decode($_POST["filesToDelete"] ?? "[]", true);


    $uploadDir = __DIR__ . "/uploads/";

    if (!file_exists($uploadDir)){
        mkdir($uploadDir, 0777, true);
    }

    require_once __DIR__ . '/uploadToOracleBucket.php';
    require_once __DIR__ . '/deleteFromOracleBucket.php';

 
    //save all the images from client to db
    $uploadedFiles = [];
    $image_count = 0;

    $usedLetters = [];
    foreach ($filesToKeep as $img) {
        preg_match('/_([a-z])\.webp$/', $img, $m);
        if (isset($m[1])) {
            $usedLetters[] = $m[1];
        }
    }


    if($hasImages){
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] !== 0) continue;

            $imageInfo = getimagesize($tmpName);
            if (!$imageInfo) continue;

            $mime = $imageInfo['mime'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mime, $allowedTypes)) continue;

            switch ($mime) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($tmpName);
                    break;

                case 'image/png':
                    $image = imagecreatefrompng($tmpName);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;

                case 'image/webp':
                    $image = imagecreatefromwebp($tmpName);
                    break;
            }


            // get next available letter
            $image_char = nextLetter($usedLetters);

            //reserve it immediately
            $usedLetters[] = $image_char;

            $fileName = $productID . "_" . $image_char . ".webp";
            $filePath = $uploadDir . $fileName;

            imagewebp($image, $filePath, 80);
            imagedestroy($image);

            $imageUrl = uploadToOracle($filePath, $fileName);

            unlink($filePath);

            $uploadedFiles[] = $imageUrl;
        }
    }
    
    $image_count = count($usedLetters);

    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();

     //add listing details to db
    $updateProductStmt = $conn->prepare("
        UPDATE products 
        SET 
            ownerID = ?,
            name = ?,
            description = ?,
            price = ?,
            `condition` = ?,
            delivery = ?,
            category = ?,
            subcategory = ?,
            latitude = ?,
            longitude = ?,
            province = ?,
            city = ?,
            imageCount = ?,
            quantity = ?
        WHERE productID = ?
    ");

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


    try {

        //get userID from session
        $ownerID = $_SESSION['userID'];

        $updateProductStmt->bind_param(
            "sssdssssddssiis",
            $ownerID,
            $title,
            $description,
            $price,
            $condition,
            $delivery,
            $category,
            $subcategory,
            $latitude,
            $longitude,
            $province,
            $city,
            $image_count,
            $quantity,
            $productID
        );

        $updateProductStmt->execute();
        
        //check if we have tags - tags are optional
        try {
            if(isset($_POST['tags']) && !empty($_POST['tags'])){

                //we have tags
                $tags = json_decode($_POST['tags'], true);
            
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
            }


        } catch (\Throwable $th) {
            throw $th;
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
        
        //delete files after it failed
        foreach ($uploadedFiles as $file) {
            if (file_exists($file)) unlink($file);
        }

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



function nextLetter($used) {
    for ($i = 0; $i < 26; $i++) {
        $l = chr(97 + $i);
        if (!in_array($l, $used)) return $l;
    }
    throw new Exception("No available slots");
}