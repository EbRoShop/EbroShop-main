<?php
header('Content-Type: application/json');

// INSERT YOUR BREVO API KEY HERE
$apiKey = 'BREVO_API_KEY'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $payment = $_POST['paymentMethod'];
    $total = $_POST['totalPrice'];
    $cartData = json_decode($_POST['cartData'], true);

    $tableRows = "";
    foreach ($cartData as $item) {
        $sub = $item['price'] * $item['qty'];
        $tableRows .= "
            <tr>
                <td style='padding:10px; border-bottom:1px solid #eee;'>{$item['name']}</td>
                <td style='padding:10px; border-bottom:1px solid #eee; text-align:center;'>{$item['qty']}</td>
                <td style='padding:10px; border-bottom:1px solid #eee; text-align:right;'>ETB " . number_format($sub, 2) . "</td>
            </tr>";
    }

    $emailContent = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 500px; border: 1px solid #136835; padding: 20px; border-radius: 10px;'>
            <h2 style='color: #136835; text-align: center;'>EbRo Shop Order Receipt</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Payment:</strong> $payment</p>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background: #136835; color: white;'>
                    <th style='padding: 10px;'>Item</th>
                    <th style='padding: 10px;'>Qty</th>
                    <th style='padding: 10px;'>Price</th>
                </tr>
                $tableRows
            </table>
            <h3 style='text-align: right; margin-top: 20px;'>Grand Total: ETB " . number_format($total, 2) . "</h3>
        </div>
    </body>
    </html>";

    $data = [
        "sender" => ["name" => "EbRo-Shop", "email" => "system@ebroshop.com"],
        "to" => [["email" => "ebroshoponline@gmail.com"]],
        "subject" => "New Receipt: $name",
        "htmlContent" => $emailContent
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $res = curl_exec($ch);
    curl_close($ch);
    echo json_encode(["status" => "success"]);
}
?>