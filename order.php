<?php
// SETTINGS
$admin_email = "ebroshoponline@gmail.com"; 
$shop_name = "EbRo-Shop Online";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = $_POST['name'] ?? 'Guest';
    $phone   = $_POST['phone'] ?? 'No Phone';
    $payment = $_POST['paymentMethod'] ?? 'Not Selected';
    $total   = $_POST['totalPrice'] ?? '0';
    $cartData = json_decode($_POST['cartData'], true);

    $subject = "📝 NEW ORDER: $name";
    
    // Design matching your pen-written request
    $message = "
    <html>
    <body style='background-color: #fdfdfd; padding: 15px; font-family: \"Courier New\", Courier, monospace;'>
        <div style='max-width: 400px; margin: auto; background: #fff; padding: 20px; border: 1px solid #000;'>
            <div style='text-align: center; border-bottom: 2px dashed #136835; padding-bottom: 10px; margin-bottom: 15px;'>
                <h2 style='margin: 0; color: #136835;'>$shop_name</h2>
                <p style='margin: 5px 0; font-size: 12px;'>" . date("d M Y, h:i A") . "</p>
            </div>

            <p style='margin: 5px 0;'><strong>NAME:</strong> $name</p>
            <p style='margin: 5px 0;'><strong>PHONE:</strong> $phone</p>
            <p style='margin: 5px 0;'><strong>PAY:</strong> $payment</p>

            <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                <tr style='border-bottom: 1px solid #000;'>
                    <th style='text-align: left;'>ITEM</th>
                    <th style='text-align: center;'>QTY</th>
                    <th style='text-align: right;'>PRICE</th>
                </tr>";

    foreach ($cartData as $item) {
        $price = (float)$item['price'];
        $qty = (int)$item['qty'];
        $message .= "
                <tr>
                    <td style='padding: 5px 0;'>{$item['name']}</td>
                    <td style='text-align: center;'>$qty</td>
                    <td style='text-align: right;'>ETB " . number_format($price * $qty, 2) . "</td>
                </tr>";
    }

    $message .= "
            </table>

            <div style='margin-top: 15px; border-top: 2px dashed #136835; padding-top: 10px; font-weight: bold; font-size: 18px;'>
                <span>TOTAL:</span>
                <span style='float: right;'>ETB " . number_format($total, 2) . "</span>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: orders@ebroshop.com" . "\r\n";

    mail($admin_email, $subject, $message, $headers);
    echo json_encode(["status" => "success"]);
}
?>