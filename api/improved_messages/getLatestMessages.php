<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    require_once "./message_encryption.php";

        

    try {

        $result = json_decode(file_get_contents("php://input"), true);

        //get userID from session
        $userID = $_SESSION['userID'];

        //check for groupID
        $getGroupChatIdStmt = $conn->prepare("SELECT groupID, buyerID, productID FROM group_chats WHERE buyerID = ? OR ownerID = ?");
        $getGroupChatIdStmt->bind_param("ss", $userID, $userID);
        $getGroupChatIdStmt->execute();
        
        $groupChatInfo = $getGroupChatIdStmt->get_result();

        $groups = [];

        //add group ids to array
        while ($row = $groupChatInfo->fetch_assoc()) {
            $groups[] = $row;
        }

        if (empty($groups)) {
            throw new Exception("Could not get groupID", 501);
        }

        $msgStmt = $conn->prepare("
            SELECT message, created_at, sentByID
            FROM chats
            WHERE groupID = ?
            ORDER BY chatID DESC
            LIMIT 1
        ");

        $userStmt = $conn->prepare("
            SELECT u.fName, u.lName
            FROM group_chats gc
            INNER JOIN users u 
                ON u.userID = IF(gc.buyerID = ?, gc.ownerID, gc.buyerID)
            WHERE gc.groupID = ?
        ");


        $inbox = [];

        foreach ($groups as $group) {

            $groupID = $group['groupID'];
            $productID = $group['productID'];

            //get latest message from current group
            $msgStmt->bind_param("s", $groupID);
            $msgStmt->execute();

            $msgResult = $msgStmt->get_result();
            $messageRow = $msgResult->fetch_assoc();

            if (!$messageRow) continue; // skip empty chats

            //get other user information from current group
            $userStmt->bind_param("si", $userID, $groupID);
            $userStmt->execute();

            $userResult = $userStmt->get_result();
            $userRow = $userResult->fetch_assoc();

            //add messages to inbox
            $inbox[] = [
                "groupID" => $groupID,
                "latest_message" => decryptMessage($messageRow['message']),
                "created_at" => $messageRow['created_at'],
                "fName" => $userRow['fName'],
                "lName" => $userRow['lName'],
                "productID" => $productID
            ];
        }


        //return message to client
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "messages"=>$inbox
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
    if (isset($msgStmt)) $msgStmt->close();
    if (isset($userStmt)) $userStmt->close();

    $conn->close();
    die();
