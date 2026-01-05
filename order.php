<?php
session_start();
header('Content-Type: application/json');

// 1. Get the Key from Render Environment
$apiKey = getenv('BREVO_API_KEY'); 

// 2. Get the email of the user who is currently logged in
$customerEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;

$input = json_decode(file_get_contents('php://input'), true);

if (!$customerEmail) {
    echo json_encode(["success" => false, "message" => "User session expired. Please log in again."]);
    exit;
}

if ($input) {
    $name = $input['name'];
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // Build the table for the email receipt
    $rows = "";
    foreach($cart as $p) {
        $sub = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:8px; border:1px solid #ddd;'>{$p['name']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$p['qty']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:right;'>ETB " . number_format($sub, 2) . "</td>
                  </tr>";
    }

    // Prepare Brevo Data (Matching your register.php style)
    $data = [
        "sender" => ["name" => "EbRo Shop", "email" => "ebroshoponline@gmail.com"],
        "to" => [["email" => $customerEmail, "name" => $name]],
        "subject" => "Order Receipt #$order_id - EbRo Shop",
        "htmlContent" => "
            <div style='font-family: Arial; padding: 20px; border: 1px solid #eee;'>
                <h2 style='color: #0b91ff;'>Your Receipt from EbRo Shop</h2>
                <p>Hello $name, thank you for your purchase!</p>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background: #f4f4f4;'>
                        <th style='padding:8px; border:1px solid #ddd;'>Item</th>
                        <th style='padding:8px; border:1px solid #ddd;'>Qty</th>
                        <th style='padding:8px; border:1px solid #ddd;'>Total</th>
                    </tr>
                    $rows
                </table>
                <h3 style='text-align: right;'>Grand Total: ETB " . number_format($total, 2) . "</h3>
                <p><b>Phone:</b> $phone <br> <b>Payment:</b> $payment</p>
            </div>"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(["success" => true, "order_id" => $order_id]);
    } else {
        echo json_encode(["success" => false, "message" => "Email Error ($httpCode): " . $response]);
    }
}
?>