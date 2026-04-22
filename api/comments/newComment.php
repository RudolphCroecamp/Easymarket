<?php
    header('Content-Type: application/json');//return data in json format

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB


    $result = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($result['productID'], $result['rating'], $result['message']) ||
        empty($result['productID']) ||
        empty($result['rating']) ||
        empty(trim($result['message']))
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


    if($rating <= 0){
        $rating=1;
    }

    if($rating > 5){
        $rating=5;
    }


    //generate commentID
    require_once '../../config/generateGUID.php';//connect to DB
    $commentID = generateGUID();// 2a38e78c-99be-4b32-a5f5-cac84f9efccf


    //get userID from session
    $userID = $_SESSION['userID'];


    //start a transaction so we can revert all inserts if one fails
    $conn->begin_transaction();

    try {
        //check if user have already submitted an review
        $checkStmt = $conn->prepare("SELECT 1 FROM comments WHERE productID = ? AND senderID = ?");
        $checkStmt->bind_param("ss", $productID, $userID);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception("You already reviewed this product");
            exit;
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
            (commentID, productID, senderID, fname, lname, rating, comment) 
            VALUES (?,?,?,?,?,?,?)
        ");

        //we generate our commentID above -> line 49
        $insertCommentStmt->bind_param(
            "sssssis", 
            $commentID, $productID, $userID, $fname, $lname, $rating, $message
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

    $conn->close();
    die();