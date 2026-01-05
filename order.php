<?php
session_start();
// Hide all system warnings to prevent breaking the JSON response
error_reporting(0);
header('Content-Type: application/json');

// 1. Get Brevo Key - Try both environment and direct if needed for testing
$apiKey = getenv('BREVO_API_KEY'); 

// 2. Read the data sent from your JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    $name = $input['name'];
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- EMAIL LOGIC ---
    // If user is logged in, use their email. Otherwise, send to your shop email as backup.
    $targetEmail = isset($_SESSION['email']) ? $_SESSION['email'] : "ebroshoponline@gmail.com";

    // Build the Product Table for the email
    $rows = "";
    foreach($cart as $p) {
        $subtotal = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:8px; border:1px solid #ddd;'>{$p['name']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$p['qty']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:right;'>ETB " . number_format($subtotal, 2) . "</td>
                  </tr>";
    }

    $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
    
    $emailData = [
        "sender" => ["name" => "EbRo Shop", "email" => "ebroshoponline@gmail.com"],
        "to" => [["email" => $targetEmail, "name" => $name]],
        "subject" => "Receipt for Order #$order_id - EbRo Shop",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                <img src='$logoUrl' style='width: 200px; margin-bottom: 20px;'>
                <h2 style='color: #136835;'>Order Received!</h2>
                <p>Hello $name, we are processing your order. Here is your receipt:</p>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f8f8f8;'>
                            <th style='padding:10px; border:1px solid #ddd; text-align:left;'>Item</th>
                            <th style='padding:10px; border:1px solid #ddd;'>Qty</th>
                            <th style='padding:10px; border:1px solid #ddd; text-align:right;'>Price</th>
                        </tr>
                    </thead>
                    <tbody>$rows</tbody>
                </table>
                <h3 style='text-align: right;'>Total: ETB " . number_format($total, 2) . "</h3>
                <p><strong>Phone:</strong> $phone | <strong>Payment:</strong> $payment</p>
            </div>"
    ];

    // Send to Brevo API
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    // Return success to JavaScript so Telegram part starts
    echo json_encode(["success" => true, "order_id" => $order_id]);
} else {
    // If API key is missing or no data, tell the JS what happened
    echo json_encode([
        "success" => false, 
        "message" => $apiKey ? "No data received" : "Brevo API Key missing in Render settings"
    ]);
}
?>