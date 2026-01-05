<?php
session_start();
// Hide all database/system errors from the output
error_reporting(0);
header('Content-Type: application/json');

// 1. Get the Key from Render Environment
$apiKey = getenv('BREVO_API_KEY'); 

// 2. Get Input from JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    $name = $input['name'];
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- FIX: If session email is missing, send to your shop email as backup ---
    $customerEmail = isset($_SESSION['email']) ? $_SESSION['email'] : "ebroshoponline@gmail.com";

    // Build Email Table
    $rows = "";
    foreach($cart as $p) {
        $st = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:8px; border:1px solid #ddd;'>{$p['name']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$p['qty']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:right;'>ETB " . number_format($st, 2) . "</td>
                  </tr>";
    }

    // BREVO EMAIL DATA
    $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
    $data = [
        "sender" => ["name" => "EbRoShop", "email" => "ebroshoponline@gmail.com"],
        "to" => [["email" => $customerEmail, "name" => $name]],
        "subject" => "Receipt for Order #$order_id - EbRo Shop",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                <img src='$logoUrl' style='width: 200px; display: block; margin-bottom: 20px;'>
                <h2 style='color: #136835;'>Thank you for your order!</h2>
                <p>Hello $name, your order has been received and is being processed.</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <tr style='background: #f8f8f8;'>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Product</th>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Qty</th>
                        <th style='padding: 10px; border: 1px solid #ddd;'>Price</th>
                    </tr>
                    $rows
                </table>
                <h3 style='text-align: right;'>Total Amount: ETB " . number_format($total, 2) . "</h3>
                <p><strong>Phone:</strong> $phone | <strong>Payment:</strong> $payment</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #777;'>Contact us at ebroshoponline@gmail.com or +251970130755</p>
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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return success to the browser so Telegram logic can finish
    echo json_encode(["success" => true, "order_id" => $order_id]);
} else {
    echo json_encode(["success" => false, "message" => "Missing data or API key."]);
}
?>