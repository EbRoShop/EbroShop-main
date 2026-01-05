<?php
session_start();
// Prevent PHP from printing any HTML errors that break the JSON
error_reporting(0);
header('Content-Type: application/json');

// 1. Get the Key from Render Environment
$apiKey = getenv('BREVO_API_KEY'); 

// 2. Read Order Data from JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    $name = $input['name'];
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- FIX: Logic to find User Email without crashing ---
    $customerEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;

    if (!$customerEmail) {
        // Try a quick database check only if the database is awake
        $host = "ebroshop-db-rebyidejene8949-bf18.c.aivencloud.com";
        $user = "avnadmin";
        $pass = "YOUR_PASSWORD"; // Ensure this matches your real password
        $db   = "defaultdb";
        $port = "27481";

        $conn = mysqli_init();
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        $connected = @mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

        if ($connected) {
            $safeName = mysqli_real_escape_string($conn, $name);
            $res = $conn->query("SELECT email FROM users WHERE first_name LIKE '%$safeName%' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $customerEmail = $row['email'];
            }
            $conn->close();
        }
    }

    // FINAL FALLBACK: If we still have no email, send it to your admin email
    if (!$customerEmail) {
        $customerEmail = "ebroshoponline@gmail.com"; 
    }

    // Build Product Table for Email
    $rows = "";
    foreach($cart as $p) {
        $st = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:8px; border:1px solid #ddd;'>{$p['name']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$p['qty']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:right;'>ETB " . number_format($st, 2) . "</td>
                  </tr>";
    }

    // BREVO EMAIL SETUP
    $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
    $data = [
        "sender" => ["name" => "EbRo Shop", "email" => "ebroshoponline@gmail.com"],
        "to" => [["email" => $customerEmail, "name" => $name]],
        "subject" => "Receipt for Order #$order_id - EbRo Shop",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                <img src='$logoUrl' alt='EbRoShop Logo' style='width: 200px; display: block; margin-bottom: 20px;'>
                <h2 style='color: #136835;'>Thank you for your order!</h2>
                <p>Hello $name, your order has been received and is being processed.</p>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background: #f8f8f8;'>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Product</th>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Qty</th>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Subtotal</th>
                    </tr>
                    $rows
                </table>
                <h3 style='text-align: right;'>Total: ETB " . number_format($total, 2) . "</h3>
                <p><strong>Phone:</strong> $phone | <strong>Payment:</strong> $payment</p>
            </div>"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    curl_exec($ch);
    curl_close($ch);

    // ALWAYS return success so the JavaScript moves to the Telegram part
    echo json_encode(["success" => true, "order_id" => $order_id]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid input data or API key missing."]);
}
?>