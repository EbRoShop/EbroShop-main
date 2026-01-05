<?php
session_start();
error_reporting(0); // Prevents HTML errors from breaking your Telegram logic
header('Content-Type: application/json');

include 'db.php'; // Required to look up the user's email

// 1. Get the Brevo Key from Render Environment
$apiKey = getenv('BREVO_API_KEY');

// 2. Read Order Data from your JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    $name = mysqli_real_escape_string($conn, $input['name']);
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- CRITICAL FIX: FIND THE REGISTERED USER EMAIL ---
    $customerEmail = null;

    // First: Check if the user is logged in
    if (isset($_SESSION['email'])) {
        $customerEmail = $_SESSION['email'];
    } 
    // Second: Search the database for the email matching the name provided
    else {
        $search = "SELECT email FROM users WHERE first_name LIKE '%$name%' OR last_name LIKE '%$name%' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
        }
    }

    // --- SEND EMAIL ONLY IF WE FOUND THE USER'S EMAIL ---
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
            "to" => array(array("email" => $customerEmail, "name" => $name)), // THIS SENDS TO THE USER
            "subject" => "Your Order Confirmation #$order_id",
            "htmlContent" => "
                <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius:10px;'>
                    <div style='text-align: center; border-bottom: 2px solid #136835; padding-bottom: 10px; margin-bottom: 20px;'>
                        <img src='$logoUrl' style='width: 200px;'>
                    </div>
                    <h2 style='color: #136835;'>Thank you for your order!</h2>
                    <p>Hello <b>$name</b>, we have received your order.</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr style='background: #222; color: white;'>
                            <th style='padding:10px; text-align:left;'>Item</th>
                            <th style='padding:10px;'>Qty</th>
                            <th style='padding:10px; text-align:right;'>Price</th>
                        </tr>
                        $rows
                    </table>
                    <h3 style='text-align: right; color:#136835;'>Total Amount: ETB " . number_format($total, 2) . "</h3>
                    <p style='font-size: 12px; color: #777; margin-top: 20px; text-align: center;'>
                        EbRoShop Support: +251970130755
                    </p>
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
    // ALWAYS return success so the JavaScript Telegram part starts
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>