<?php
session_start();
// Hide system errors to ensure clean JSON output for JavaScript
error_reporting(0);
header('Content-Type: application/json');

// Include your database connection (same as register.php)
include 'db.php';

// 1. Get the Brevo Key from your Render Environment Variables
$apiKey = getenv('BREVO_API_KEY');

// 2. Read the Order Data from the JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    // Sanitize the name to prevent database errors
    $name = mysqli_real_escape_string($conn, $input['name']);
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- FIX: IDENTIFY THE USER'S REGISTERED EMAIL ---
    $customerEmail = null;

    // Check if the user is currently logged in via Session
    if (isset($_SESSION['email'])) {
        $customerEmail = $_SESSION['email'];
    } 
    // If session is lost, search the 'users' table for the email matching the name provided
    else {
        $search = "SELECT email FROM users WHERE first_name LIKE '%$name%' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
        }
    }

    // FINAL SAFETY: If no registered email is found, use admin email so you don't lose the order
    if (!$customerEmail) {
        $customerEmail = 'ebroshoponline@gmail.com'; 
    }

    // 3. Build the Order Items Table (Matching your professional design)
    $rows = "";
    foreach($cart as $p) {
        $itemTotal = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:12px; border-bottom:1px solid #eee; color:#333;'>{$p['name']}</td>
                    <td style='padding:12px; border-bottom:1px solid #eee; text-align:center; color:#333;'>{$p['qty']}</td>
                    <td style='padding:12px; border-bottom:1px solid #eee; text-align:right; color:#333;'>ETB " . number_format($itemTotal, 2) . "</td>
                  </tr>";
    }

    // 4. Set up the Professional Email Template (Using your Cloudinary Logo)
    $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
    $senderEmail = 'ebroshoponline@gmail.com';

    $data = array(
        "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
        "to" => array(array("email" => $customerEmail, "name" => $name)), // This sends to the USER
        "subject" => "Thank you for your order! #$order_id",
        "htmlContent" => "
            <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius:10px;'>
                <div style='text-align: center; border-bottom: 2px solid #136835; padding-bottom: 15px; margin-bottom: 20px;'>
                    <img src='$logoUrl' alt='EbRoShop' style='width: 200px;'>
                </div>

                <h2 style='color: #136835; text-align: center;'>Thank you for your order!</h2>
                <p style='color:#555;'>Hello <b>$name</b>, your order has been received and is being processed.</p>

                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='background: #222; color: white;'>
                        <th style='padding:12px; text-align:left;'>Product</th>
                        <th style='padding:12px;'>Qty</th>
                        <th style='padding:12px; text-align:right;'>Price</th>
                    </tr>
                    $rows
                    <tr style='font-weight: bold; font-size: 18px;'>
                        <td colspan='2' style='padding:20px 12px; text-align:right;'>Total Amount:</td>
                        <td style='padding:20px 12px; text-align:right; color:#136835;'>ETB " . number_format($total, 2) . "</td>
                    </tr>
                </table>
                <div style='background:#f9f9f9; padding:15px; border-radius:5px; border-left: 4px solid #136835;'>
                    <p style='margin:5px 0;'><b>Phone:</b> $phone</p>
                    <p style='margin:5px 0;'><b>Payment:</b> $payment</p>
                </div>

                <p style='font-size: 12px; color: #777; margin-top: 30px; text-align: center;'>
                    Contact us at <a href='mailto:$senderEmail'>$senderEmail</a> or <b>+251970130755</b>
                </p>
            </div>"
    );

    // 5. Send via Brevo API (Same as register.php)
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

    // 6. Return success to the JavaScript to finish Telegram and Redirect
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>