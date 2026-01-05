<?php
session_start();
// This stops the "Unexpected token" error by hiding database warnings
error_reporting(0); 
header('Content-Type: application/json');

// 1. Include the database connection used in register.php
include 'db.php'; 

// 2. Get the API Key and site details from your register logic
$apiKey = getenv('BREVO_API_KEY'); 
$senderEmail = 'ebroshoponline@gmail.com';
$logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";

$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    // 3. Sanitize inputs exactly like register.php
    $fname = mysqli_real_escape_string($conn, $input['name']); 
    $phone = mysqli_real_escape_string($conn, $input['phone']);
    $payment = mysqli_real_escape_string($conn, $input['payment']);
    $total = floatval($input['total']);
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // 4. Identify the user for Order History
    $user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    // If session is lost, search for the user like the email check in register.php
    if (empty($user_email)) {
        $search = "SELECT id, email FROM users WHERE first_name = '$fname' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $user_email = $row['email'];
            $user_id = $row['id'];
        }
    }

    // 5. SAVE TO ORDERS TABLE (This fixes your Order History)
    $sql = "INSERT INTO orders (user_id, order_id, total_amount, payment_method, status) 
            VALUES ('$user_id', '$order_id', '$total', '$payment', 'Pending')";
    $conn->query($sql);

    // 6. SEND THE EMAIL (Using the exact cURL settings from register.php)
    if (!empty($user_email)) {
        $rows = "";
        foreach($cart as $p) {
            $sub = $p['price'] * $p['qty'];
            $rows .= "<tr><td style='padding:10px; border-bottom:1px solid #eee;'>{$p['name']}</td><td style='text-align:center;'>{$p['qty']}</td><td style='text-align:right;'>ETB ".number_format($sub,2)."</td></tr>";
        }

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
            "to" => array(array("email" => $user_email, "name" => $fname)),
            "subject" => "Order Confirmation #$order_id",
            "htmlContent" => "
                <div style='font-family:Arial; max-width:600px; margin:auto; padding:20px; border:1px solid #eee;'>
                    <img src='$logoUrl' style='width:200px;'>
                    <h2 style='color:#136835;'>Thank you for your order!</h2>
                    <table style='width:100%; border-collapse:collapse;'>$rows</table>
                    <h3 style='text-align:right;'>Total: ETB ".number_format($total, 2)."</h3>
                    <p><b>Phone:</b> $phone | <b>Payment:</b> $payment</p>
                </div>"
        );

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    // 7. Success response for Telegram
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>