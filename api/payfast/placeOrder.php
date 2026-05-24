<?php
    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    require './generateSignature.php';//user must be authorised

    //get info from client
    $jsonData = json_decode(file_get_contents('php://input'),true);

    if(!isset(
        $jsonData["fName"],
        $jsonData["lName"],
        $jsonData["email"],
        $jsonData["cell"],
        $jsonData["streetAddress"],
        $jsonData["province"],
        $jsonData["city"],
        $jsonData["postal"],
        $jsonData["shippingMethod"],
        $jsonData["orders"]
    ) 
    
    || empty(trim($jsonData["fName"])) 
    || empty(trim($jsonData["lName"]))
    || empty(trim($jsonData["email"]))
    || empty(trim($jsonData["cell"]))
    || empty(trim($jsonData["streetAddress"]))
    || empty(trim($jsonData["province"]))
    || empty(trim($jsonData["city"]))
    || empty(trim($jsonData["postal"]))
    || empty(trim($jsonData["shippingMethod"]))
    ){
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "Fill in all fields",
            "fields" => $jsonData
        ]);
        exit;
    }


    $userID = $_SESSION["userID"];


    //return error when no userID was found
    if(!isset($userID) || empty(trim($userID))){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Could not process payment, No user-ID found."
        ]));
    }


    $orders = $jsonData["orders"];

    $platFormTakePerc = 5;

    //order stmt
    $CreateOrderStmt = $conn->prepare("INSERT INTO orders (totalPrice, status) VALUES (0, 'processing')");
    $UpdateOrderStmt = $conn->prepare("UPDATE orders SET totalPrice = ?, status = ? WHERE orderID = ?");

    //orders stmt
    $addOrderStmt = $conn->prepare("INSERT INTO order_items 
    ( orderID, sellerID, buyerID, productID, price, platform_fee, seller_fee, delivery_fee, quantity, status )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $getProductInfo = $conn->prepare("SELECT ownerID, productID, price, quantity, 'name' FROM products where productID = ?");
    

    $status = "Awaiting Payment";
    $deliveryFee = 100;

    $conn->begin_transaction();
    $orderedItems = [];
    $cartTotal = 0;

    try {
        $CreateOrderStmt->execute();

        $orderID = $conn->insert_id;
        if($orderID <= 0){
            throw new Exception("Could not create order", 1);
        }



        //add orders to order table
        foreach ($orders as $order){
            $getProductInfo->bind_param("s", $order["productID"]);
            $getProductInfo->execute();

            $productInfo = $getProductInfo->get_result();

            if($productInfo && $productInfo->num_rows > 0){
                //return products in json format
                $productInfo = $productInfo->fetch_assoc();
            }else{
                continue;
            }

            $sellerID = $productInfo["ownerID"];

            $productID = $productInfo["productID"];
            $price = $productInfo["price"];

            

            //quantity =  sellected amount or if not enought items ammount available
            $quantity = $order["quantity"] <= $productInfo["quantity"] ? $order["quantity"] : $productInfo["quantity"];

            //platformFee = 5% of itemPrice * quantity
            //sellerFee = itemPrice - platformFee
            $platformFee = ($price * $quantity) / 100 * $platFormTakePerc;
            $sellerFee = ($price * $quantity) - $platformFee;
            

            if($jsonData["shippingMethod"] == "Standard"){
                $deliveryFee = 100;
            }else if($jsonData["shippingMethod"] == "Express"){
                $deliveryFee = 180;
            }else{
                $deliveryFee = 0;
            }

            $addOrderStmt->bind_param("isssddddis", $orderID, $sellerID, $userID, $productID, $price, $platformFee, $sellerFee, $deliveryFee, $quantity, $status);
            
            $addOrderStmt->execute();

            $cartTotal += ($price + $deliveryFee);


            //check if order was placed successfully
            $paymentID = $conn->insert_id;
            if($paymentID <= 0){
                continue;
            }

            //add oreder to order array -> used to send to payfast checkout
            $orderedItems[] = [
                "productID" => $productInfo["productID"],
                "title" => $productInfo["name"],
                "price" => $productInfo["price"],
                "quantity" => $productInfo["quantity"],
            ];
        }//end for loop

        $updateStatus = "Orders Placed";
        $UpdateOrderStmt->bind_param("dsi", $cartTotal, $updateStatus, $orderID);
        $UpdateOrderStmt->execute();



        if (!$orderedItems || count($orderedItems) <= 0) {
            throw new Exception("Could Not Place Orders", 1);
        }


        //get buyer INfo
        $buyerStmt = $conn->prepare("SELECT fName, lName, email FROM users WHERE userID = ?");
        $buyerStmt->bind_param("s", $userID);
        $buyerStmt->execute();

        $buyer_results = $buyerStmt->get_result();

        $buyerInfo = [];
        if($buyer_results && $buyer_results->num_rows > 0){
            $buyerInfo = $buyer_results->fetch_assoc();
        }else{
            throw new Exception("Buyer information could not be found.");
        }


        $passphrase = 'qwefasfawqeqwqweasd';

        $data = array(
            // Merchant details
            'merchant_id' => '10046638',
            'merchant_key' => 'ntk5urzc2lda3',
            'return_url' => 'https://easymarket-727523185751.europe-west1.run.app/',
            'cancel_url' => 'https://easymarket-727523185751.europe-west1.run.app/',
            'notify_url' => 'https://easymarket-727523185751.europe-west1.run.app/api/payfast/paymentNotification.php',
            // Buyer details
            'name_first' => $buyerInfo["fName"],
            'name_last'  => $buyerInfo["lName"],
            'email_address'=> $buyerInfo["email"],
            // Transaction details
            'm_payment_id' => $orderID,
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => "MarketPlace Order"
        );

        $signature = generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
        $testingMode = true;
        $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $htmlForm = "";//'<form action="https://'.$pfHost.'/eng/process" method="post">';
        foreach($data as $name=> $value)
        {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
        }

        //save orders to database
        $conn->commit();

        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "orders" => $orderedItems,
            // "buyerInfo" =>$buyerInfo,
            "form" => $htmlForm
        ]);

    } catch (\Throwable $th) {
        //revert orders when error happens
        $conn->rollback();

        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => $th->getMessage()
        ]));
    }
