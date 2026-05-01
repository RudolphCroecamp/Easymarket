<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    require_once "./message_encryption.php";

    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 100;

    // Calculate offset
    $offset = ($page - 1) * $limit;


    try {

        $result = json_decode(file_get_contents("php://input"), true);

        //check for correct data from client
        if (!isset($result["productID"]) || empty($result["productID"]) ) {
            throw new Exception("No productID recieved", 1);
            
        }

        $productID = $result["productID"];

        //get userID from session
        $userID = $_SESSION['userID'];


        //prevent the user from chatting to themselfs

        //get ownerID
        $getOwnerIDStmt = $conn->prepare("SELECT ownerID from products WHERE productID = ?");
        $getOwnerIDStmt->bind_param("s", $productID);
        $getOwnerIDStmt->execute();

        $owner_result = $getOwnerIDStmt->get_result();
        
        //check for valid ownerID
        if($owner_result->num_rows <= 0){
            throw new Exception("Could not find the owner of the product", 1);
        }

        //get product's ownerID
        $row = $owner_result->fetch_assoc();
        $ownerID = $row["ownerID"];

        if($ownerID == $userID){
            throw new Exception("You cannot message yourself", 1);
        }


        //check for groupID
        $getGroupChatIdStmt = $conn->prepare("SELECT groupID, buyerID FROM group_chats WHERE productID = ? AND buyerID = ?");
        $getGroupChatIdStmt->bind_param("is", $productID, $userID);
        $getGroupChatIdStmt->execute();
        
        $group_result = $getGroupChatIdStmt->get_result();
        
        //check if group exist
        if($group_result->num_rows<=0){
            throw new Exception("The current user does not belong to any group for the current product", 1);  
        }

        $row = $group_result->fetch_assoc();
        $groupID = $row["groupID"];


        //get messages by groupID
        $getMessageStmt = $conn->prepare("SELECT * FROM chats where groupID = ? ORDER BY created_at ASC LIMIT ? OFFSET ?");
        $getMessageStmt->bind_param("iii", $groupID, $limit, $offset);
        $getMessageStmt->execute();

        $message_result = $getMessageStmt->get_result();

        $messages = [];


        //check if we have received rows form the db
        if ($message_result->num_rows > 0) {
            while ($row = $message_result->fetch_assoc()) {
                $row['message'] = decryptMessage($row['message']);
                $row["sentByCurrentUser"] = $row["sentByID"] == $userID ? true : false;
                $messages[] = $row;
            }
        }else{
            throw new Exception("No messages found", 1);
        }


        //return messages to client
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "messages"=> $messages
        ]);

    } catch (\Throwable $th) {
        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>$th->getMessage()
        ]);
    }

    //Close statements and connection
    if (isset($getGroupChatIdStmt)) $getGroupChatIdStmt->close();
    if (isset($getMessageStmt)) $getMessageStmt->close();

    $conn->close();
    die();
