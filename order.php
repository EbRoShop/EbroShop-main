<?php
header('Content-Type: application/json');

// 1. PASTE YOUR BREVO API KEY HERE
$apiKey = 'YOUR_BREVO_API_KEY_HERE'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = $_POST['name'];
    $phone   = $_POST['phone'];
    $payment = $_POST['paymentMethod'];
    $total   = $_POST['totalPrice'];
    $cartData = json_decode($_POST['cartData'], true);

    // Build the Product Table for the email
    $tableRows = "";
    foreach ($cartData as $item) {
        $sub = $item['price'] * $item['qty'];
        $tableRows .= "<tr>
            <td style='padding:8px; border:1px solid #ddd;'>{$item['name']}</td>
            <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$item['qty']}</td>
            <td style='padding:8px; border:1px solid #ddd; text-align:right;'>ETB " . number_format($sub, 2) . "</td>
        </tr>";
    }

    // Prepare Brevo Data
    $data = [
        "sender" => ["name" => "EbRoShop", "email" => "orders@ebroshop.com"],
        "to" => [["email" => "ebroshoponline@gmail.com", "name" => "Admin"]],
        "subject" => "📝 NEW ORDER: $name",
        "htmlContent" => "
            <html>
            <body>
                <h2 style='color:#136835;'>Order Receipt</h2>
                <p><strong>Customer:</strong> $name</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Payment:</strong> $payment</p>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr style='background:#f4f4f4;'>
                        <th style='padding:8px; border:1px solid #ddd;'>Item</th>
                        <th style='padding:8px; border:1px solid #ddd;'>Qty</th>
                        <th style='padding:8px; border:1px solid #ddd;'>Total</th>
                    </tr>
                    $tableRows
                </table>
                <h3 style='text-align:right;'>Grand Total: ETB " . number_format($total, 2) . "</h3>
            </body>
            </html>"
    ];

    // Send via CURL (Brevo API)
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo json_encode(["status" => "success"]);
}
?>