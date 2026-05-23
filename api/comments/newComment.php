<?php
    header('Content-Type: application/json');//return data in json format

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB


    $result = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($result['productID'], $result['rating'], $result['message']) ||
        empty($result['productID']) || empty($result['rating']) || empty(trim($result['message']))
    ) {
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>"Fill in all fields"
        ]);
        exit;
    }

    // Get data
    $productID = $result['productID'];
    $rating = $result['rating'];
    $message = trim($result['message']);

    //prevent invalid ratings
    if($rating <= 0){
        $rating=1;
    }

    if($rating > 5){
        $rating=5;
    }

    //get userID from session
    $userID = $_SESSION['userID'];

    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();

    try {
        //check if user is the owner of the product -> they cannot review their own products
        $checkIfOwner = $conn->prepare("SELECT 1 FROM products WHERE productID = ? AND ownerID = ?");
        $checkIfOwner->bind_param("ss", $productID, $userID);
        $checkIfOwner->execute();

        if ($checkIfOwner->get_result()->num_rows > 0) {
            throw new Exception("You cannot review a product you own");
        }

        //check if user have already submitted an review
        $checkStmt = $conn->prepare("SELECT 1 FROM comments WHERE productID = ? AND senderID = ?");
        $checkStmt->bind_param("ss", $productID, $userID);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception("You already reviewed this product");
        }

        $getSenderInfoStmt = $conn->prepare("SELECT fName, lName FROM users WHERE userID = ? LIMIT 1");
        $getSenderInfoStmt->bind_param("s", $userID);
        $getSenderInfoStmt->execute();

        //get data from db
        $senderInfo = $getSenderInfoStmt->get_result();

        //check if we have received rows form the db
        if (!$senderInfo || $senderInfo->num_rows === 0) {
            throw new Exception("User not found");
        }

        $row = $senderInfo->fetch_assoc();
        $fname = $row["fName"];
        $lname = $row["lName"];


        //add listing details to db
        $insertCommentStmt = $conn->prepare("
            INSERT INTO comments 
            (productID, senderID, fname, lname, rating, comment) 
            VALUES (?,?,?,?,?,?)
        ");


        $insertCommentStmt->bind_param(
            "ssssis", $productID, $userID, $fname, $lname, $rating, $message
        );

        $insertCommentStmt->execute();

        $conn->commit();
        //return success message after comment have been added
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "message"=>"Comment added"
        ]);

    } catch (\Throwable $th) {
        //Rollback if anything fails
        $conn->rollback();
        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "message"=>"Could not add comment",
            "error"=>$th->getMessage()
        ]);
    }

    //Close statements and connection
    if (isset($insertCommentStmt)) $insertCommentStmt->close();
    if (isset($getSenderInfoStmt)) $getSenderInfoStmt->close();
    if (isset($checkStmt)) $checkStmt->close();
    if (isset($checkIfOwner)) $checkIfOwner->close();


    $conn->close();
    die();