<?php
require '../../config/cors.php'; //allow access from webserver
require '../../config/protectedRoute.php'; //user must be authorised
$conn = require '../../config/dbconn.php'; //connect to DB

require './generateSignature.php'; //user must be authorised

//get info from client
$jsonData = json_decode(file_get_contents('php://input'), true);

if (
    !isset(
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
) {
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
if (!isset($userID) || empty(trim($userID))) {
    exit(json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "Could not process payment, No user-ID found."
    ]));
}


$orders = $jsonData["orders"];

$platFormTakePerc = 5;

//order stmt
$CreateOrderStmt = $conn->prepare("INSERT INTO orders (sellerID, buyerID, totalPrice, platform_fee, seller_fee, delivery_fee, status) VALUES (?,?,?,?,?,?,?)");
$UpdateOrderStmt = $conn->prepare("UPDATE orders SET totalPrice = ?, status = ?, platform_fee = ?, seller_fee = ? WHERE orderID = ?");

//orders stmt
$addOrderStmt = $conn->prepare("INSERT INTO order_items (orderID, productID, price, quantity) VALUES (?,?,?,?)");

$getProductInfo = $conn->prepare("SELECT ownerID, productID, price, quantity, 'name' FROM products where productID = ?");

//payment
$paymantStmt = $conn->prepare("INSERT INTO payments (orderID, status) VALUES (?,?)");


$status = "Awaiting Payment";
$deliveryFee = 100;

$conn->begin_transaction();
$orderedItems = [];
$cartTotal = 0;

try {
    $ordersBySeller = [];

    //group cart by seller
    foreach ($orders as $order) {

        $getProductInfo->bind_param("s", $order["productID"]);
        $getProductInfo->execute();

        $productInfo = $getProductInfo->get_result();

        if (!$productInfo || $productInfo->num_rows === 0) {
            continue;
        }

        $product = $productInfo->fetch_assoc();

        $sellerID  = $product["ownerID"];
        $productID = $product["productID"];
        $price     = $product["price"];

        $quantity = min($order["quantity"], $product["quantity"]);

        $ordersBySeller[$sellerID][] = [
            "productID" => $productID,
            "price"     => $price,
            "quantity"  => $quantity
        ];
    }

    if($jsonData["shippingMethod"] == "Standard"){
        $deliveryFee = 100;
    }else if($jsonData["shippingMethod"] == "Express"){
        $deliveryFee = 180;
    }else{
        $deliveryFee = 0;
    }

    //process each seller's order 
    foreach ($ordersBySeller as $sellerID => $items) {

        // create order per seller
        $status = "processing";

        $temp = 0;//we cannot add 0 inside the bind_param, we need to add a variable

        $CreateOrderStmt->bind_param("ssdddds", $sellerID, $userID, $temp, $temp, $temp, $deliveryFee, $status);
        $CreateOrderStmt->execute();

        $orderID = $conn->insert_id;

        if ($orderID <= 0) {
            throw new Exception("Order creation failed");
        }

        $totalPrice = 0;

        foreach ($items as $item) {

            $productID = $item["productID"];
            $price     = $item["price"];
            $quantity  = $item["quantity"];

            $lineTotal = $price * $quantity;
            $totalPrice += $lineTotal;

            //the total for all cart items
            $cartTotal += $lineTotal;

            // insert order item
            $addOrderStmt->bind_param("isdi", $orderID, $productID, $price, $quantity);

            $addOrderStmt->execute();
        }

        // update order total for this seller
        $platformFee = ($totalPrice / 100) * $platFormTakePerc;
        $sellerFee   = $totalPrice - $platformFee;
        $updateStatus = "Order Placed";
        $UpdateOrderStmt->bind_param("dsddi", $totalPrice, $updateStatus, $platformFee, $sellerFee, $orderID);
        $UpdateOrderStmt->execute();

    }

    //get buyer INfo
    $buyerStmt = $conn->prepare("SELECT fName, lName, email FROM users WHERE userID = ?");
    $buyerStmt->bind_param("s", $userID);
    $buyerStmt->execute();

    $buyer_results = $buyerStmt->get_result();

    $buyerInfo = [];
    if ($buyer_results && $buyer_results->num_rows > 0) {
        $buyerInfo = $buyer_results->fetch_assoc();
    } else {
        throw new Exception("Buyer information could not be found.");
    }


    //create payment
    $payment_status = "Awaiting Payment";
    $paymantStmt->bind_param("ss", $orderID, $payment_status);
    $paymantStmt->execute();

    $paymentID = $conn->insert_id;
    if ($paymentID <= 0) {
        throw new Exception("Payment creation failed");
    }




    //create payfast request
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
        'email_address' => $buyerInfo["email"],
        // Transaction details
        'm_payment_id' => $paymentID,
        'amount' => number_format(sprintf('%.2f', $cartTotal), 2, '.', ''),
        'item_name' => "MarketPlace Order"
    );

    $signature = generateSignature($data, $passphrase);
    $data['signature'] = $signature;

    // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
    $testingMode = true;
    $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
    $htmlForm = ""; //'<form action="https://'.$pfHost.'/eng/process" method="post">';
    foreach ($data as $name => $value) {
        $htmlForm .= '<input name="' . $name . '" type="hidden" value=\'' . $value . '\' />';
    }

    //save orders to database
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "success" => true,
        "orders" => $orderedItems,
        // "buyerInfo" =>$buyerInfo,
        "form" => $htmlForm
    ]);
} catch (\Throwable $th) {
    //revert orders when error happens
    $conn->rollback();

    exit(json_encode([
        "status" => "failed",
        "success" => false,
        "error" => $th->getMessage()
    ]));
}
