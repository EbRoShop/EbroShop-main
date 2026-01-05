<?php
session_start();
// This stops the "Unexpected token" JSON error from your screenshots
error_reporting(0); 
header('Content-Type: application/json');

// Include the same DB connection used in register.php
include 'db.php'; 

$apiKey = getenv('BREVO_API_KEY'); 
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    // 1. Sanitize input using the same method as register.php
    $name = mysqli_real_escape_string($conn, $input['name']); 
    $phone = mysqli_real_escape_string($conn, $input['phone']);
    $payment = mysqli_real_escape_string($conn, $input['payment']);
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- FIND THE USER ID (The missing part for Order History) ---
    $customerEmail = null;
    $user_id = 0; 

    // Check the session for the logged-in user
    if (isset($_SESSION['email'])) {
        $customerEmail = $_SESSION['email'];
        // This is the key to showing history: capturing the user_id
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    } 
    // If not logged in, search the database for this name
    else {
        $search = "SELECT id, email FROM users WHERE first_name = '$name' OR CONCAT(first_name, ' ', last_name) = '$name' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
            $user_id = $row['id'];
        }
    }

    // --- STEP 2: SAVE TO THE ORDERS TABLE (Fixes History) ---
    // This uses the exact table columns your database expects
    $sql_history = "INSERT INTO orders (user_id, order_id, total_amount, payment_method, status) 
                    VALUES ('$user_id', '$order_id', '$total', '$payment', 'Pending')";
    $conn->query($sql_history);

    // --- STEP 3: SEND EMAIL TO THE CUSTOMER ---
    if ($customerEmail) {
        $rows = "";
        foreach($cart as $p) {
            $sub = $p['price'] * $p['qty'];
            $rows .= "<tr>
                        <td style='padding:12px; border-bottom:1px solid #eee;'>{$p['name']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:center;'>{$p['qty']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:right;'>ETB " . number_format($sub, 2) . "</td>
                      </tr>";
        }

        $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => "ebroshoponline@gmail.com"),
            "to" => array(array("email" => $customerEmail, "name" => $name)),
            "subject" => "Your Order Confirmation #$order_id",
            "htmlContent" => "
                <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius:10px;'>
                    <div style='text-align: center; border-bottom: 2px solid #136835; padding-bottom: 15px; margin-bottom: 20px;'>
                        <img src='$logoUrl' alt='EbRoShop' style='width: 200px;'>
                    </div>
                    <h2 style='color: #136835; text-align: center;'>Thank you for your order!</h2>
                    <p>Hello <b>$name</b>, your order has been received.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <tr style='background: #222; color: white;'>
                            <th style='padding:12px; text-align:left;'>Product</th>
                            <th style='padding:12px;'>Qty</th>
                            <th style='padding:12px; text-align:right;'>Price</th>
                            </tr>
                        $rows
                        <tr style='font-weight: bold;'>
                            <td colspan='2' style='padding:15px; text-align:right;'>Total Amount:</td>
                            <td style='padding:15px; text-align:right; color:#136835;'>ETB " . number_format($total, 2) . "</td>
                        </tr>
                    </table>
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

    // SUCCESS JSON RESPONSE
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>