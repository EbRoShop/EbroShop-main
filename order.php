<?php
header('Content-Type: application/json');

// Get the API Key from Render Environment Variables
$apiKey = getenv('BREVO_API_KEY'); 

$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    $name = $input['name'];
    $phone = $input['phone'];
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // Build product rows for email
    $rows = "";
    foreach($cart as $p) {
        $st = $p['price'] * $p['qty'];
        $rows .= "<tr><td>{$p['name']}</td><td>{$p['qty']}</td><td>ETB " . number_format($st, 2) . "</td></tr>";
    }

    $emailData = [
        "sender" => ["name" => "EbRo Shop", "email" => "system@ebroshop.com"],
        "to" => [["email" => "ebroshoponline@gmail.com"]],
        "subject" => "New Order #$order_id - $name",
        "htmlContent" => "<h3>New Order Received</h3>
                          <p><b>Customer:</b> $name</p>
                          <p><b>Phone:</b> $phone</p>
                          <p><b>Payment:</b> $payment</p>
                          <table border='1' cellpadding='5' style='border-collapse:collapse;'>
                            <tr style='background:#f4f4f4;'><th>Product</th><th>Qty</th><th>Subtotal</th></tr>
                            $rows
                          </table>
                          <h4>Total: ETB " . number_format($total, 2) . "</h4>"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 300) {
        echo json_encode(["success" => true, "order_id" => $order_id]);
    } else {
        echo json_encode(["success" => false, "message" => "Email failed to send. Check API Key."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing data or API Key not found in Environment Variables."]);
}
?>