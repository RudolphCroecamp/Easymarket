<?php
    header('Content-Type: application/json');//return data in json format

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB


    //check if whe have received images
    if (!isset($_FILES['images'])){
        echo json_encode(["error" => "No images uploaded"]);
        exit;
    }

    if ($_FILES['images']['size'][0] > 5 * 1024 * 1024) {
        echo json_encode(["error" => "Image too large (max 5MB)"]);
        exit;
    }

    if (!isset(
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
        $_POST['longitude']
    ))
    {
        echo json_encode(["error" => "Fill in all fields"]);
        exit;
    }


    //get data from client
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



    require_once '../../config/generateGUID.php';//connect to DB
    $productID = generateGUID();// 2a38e78c-99be-4b32-a5f5-cac84f9efccf

    //save all the images from client to db
    $uploadedFiles = [];
    $image_char = "a";

    $uploadDir = __DIR__ . "/uploads/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    require_once __DIR__ . '/uploadToOracleBucket.php';

    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {

        $fileError = $_FILES['images']['error'][$index];

        if ($fileError !== 0) continue;

        // 🔒 Validate real image (NOT $_FILES['type'])
        $imageInfo = getimagesize($tmpName);
        if (!$imageInfo) continue;

        $mime = $imageInfo['mime'];
        $allowedTypes = ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowedTypes)) continue;

        // 🎨 Create image resource
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($tmpName);
                break;

            case 'image/png':
                $image = imagecreatefrompng($tmpName);

                // preserve transparency
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                continue 2;
        }

        // 📏 Resize (max width 1200px)
        $maxWidth = 1200;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newHeight = max(1, (int)(($height / $width) * $maxWidth));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // 🧾 Save as WebP instead of JPG
        $filePath = $uploadDir . $productID . "_" . $image_char . ".webp";

        if (!imagewebp($image, $filePath, 80)) {
            imagedestroy($image);
            continue;
        }

        imagedestroy($image);

        $fileName = $productID . "_" . $image_char . ".webp";

        // Upload to Oracle
        $imageUrl = uploadToOracle($filePath, $fileName);

        // Delete local file after upload
        unlink($filePath);

        // Store URL instead of local path
        $uploadedFiles[] = $imageUrl;
        
        $image_char++;
    }
    
    if (count($uploadedFiles) === 0) {
        echo json_encode([
            "success" => false,
            "message" => "No valid images uploaded"
        ]);
        exit;
    }





    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();

     //add listing details to db
    $insertProductStmt = $conn->prepare("
        INSERT INTO products 
        (productID, ownerID, name, description, price, sold, deleted, `condition`, delivery, category, subcategory, latitude, longitude, province, city) 
        VALUES (?,?,?,?,?, FALSE, FALSE, ?, ?, ?, ?, ?, ?, ?, ?)
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

    //add product images to db
    $insertImageStmt = $conn->prepare("
        INSERT INTO product_images (productID, imageUrl, position, isPrimary)
        VALUES (?, ?, ?, ?)
    ");


    try {

        //get userID from session
        $ownerID = $_SESSION['userID'];

        $insertProductStmt->bind_param(
            "ssssdssssddss", 
            $productID, $ownerID, $title, $description, $price, $condition, $delivery, $category, $subcategory, $latitude, $longitude, $province, $city
        );

        $insertProductStmt->execute();
        
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

            $imgPosition = 1;
            foreach ($uploadedFiles as $filePath) {
                $isPrimary = $imgPosition == 1 ? 1 : 0;
                $insertImageStmt->bind_param("ssii", $productID, $filePath, $imgPosition, $isPrimary);
                $insertImageStmt->execute();

                $imgPosition++;
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
    if (isset($insertImageStmt)) $insertImageStmt->close();
    $conn->close();
    die();