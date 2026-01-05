<?php
session_start();
// Hide system errors so they don't break the Telegram part in JavaScript
error_reporting(0);
header('Content-Type: application/json');

// 1. Use your working database connection
include 'db.php'; 

// 2. Get Brevo Key from Render Environment
$apiKey = getenv('BREVO_API_KEY');

$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    // Sanitize the name just like register.php does
    $name = mysqli_real_escape_string($conn, $input['name']); 
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- STEP 1: FIND THE USER'S REGISTERED EMAIL ---
    $customerEmail = null;

    // Check if user is logged in
    if (isset($_SESSION['email'])) {
        $customerEmail = $_SESSION['email'];
    } 
    // If not logged in, SEARCH the database for the user's registered email
    else {
        // We look for a user whose first name matches the name entered in checkout
        $search = "SELECT email FROM users WHERE first_name LIKE '%$name%' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
        }
    }

    // --- STEP 2: SEND EMAIL ONLY TO THE CUSTOMER ---
    if ($customerEmail && $customerEmail != 'ebroshoponline@gmail.com') {
        
        // Build Product Table (Matching your professional screenshot)
        $rows = "";
        foreach($cart as $p) {
            $itemTotal = $p['price'] * $p['qty'];
            $rows .= "<tr>
                        <td style='padding:12px; border-bottom:1px solid #eee;'>{$p['name']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:center;'>{$p['qty']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:right;'>ETB " . number_format($itemTotal, 2) . "</td>
                      </tr>";
        }

        $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
        $senderEmail = 'ebroshoponline@gmail.com';

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
            "to" => array(array("email" => $customerEmail, "name" => $name)), // This sends to the USER!
            "subject" => "Receipt for Order #$order_id - EbRo Shop",
            "htmlContent" => "
                <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius:10px;'>
                    <div style='text-align: center; border-bottom: 2px solid #136835; padding-bottom: 10px; margin-bottom: 20px;'>
                        <img src='$logoUrl' style='width: 200px;'>
                    </div>
                    <h2 style='color: #136835; text-align: center;'>Thank you for your order!</h2>
                    <p>Hello <b>$name</b>, your order has been received and is being processed.</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                        <tr style='background: #222; color: white;'>
                            <th style='padding:10px; text-align:left;'>Item</th>
                            <th style='padding:10px;'>Qty</th>
                            <th style='padding:10px; text-align:right;'>Price</th>
                        </tr>
                        $rows
                        <tr style='font-weight: bold;'>
                            <td colspan='2' style='padding:15px; text-align:right;'>Total Amount:</td>
                            <td style='padding:15px; text-align:right; color:#136835;'>ETB " . number_format($total, 2) . "</td>
                            </tr>
                    </table>
                    <p><b>Phone:</b> $phone | <b>Payment:</b> $payment</p>
                    <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
                    <p style='font-size: 12px; color: #777; text-align: center;'>
                        Contact us at $senderEmail or +251970130755
                    </p>
                </div>"
        );

        // Same cURL settings as your register.php
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

    // Always send success to JavaScript so Telegram part can start
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>