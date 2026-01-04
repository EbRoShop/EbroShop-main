<?php
// FIXED: Your Gmail Address
$admin_email = "ebroshoponline@gmail.com"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = $_POST['name'];
    $phone   = $_POST['phone'];
    $payment = $_POST['paymentMethod'];
    $total   = $_POST['totalPrice'];
    $cartData = json_decode($_POST['cartData'], true);

    $subject = "📝 New Order from $name";
    
    // Design with TABLE and QUANTITY column
    $message = "
    <html>
    <body style='font-family: monospace; padding: 20px;'>
        <div style='max-width: 450px; border: 1px solid #000; padding: 20px; background: #fff;'>
            <h2 style='text-align: center; color: #136835;'>EbRo-Shop Online</h2>
            <p><strong>NAME:</strong> $name</p>
            <p><strong>PHONE:</strong> $phone</p>
            <p><strong>PAYMENT:</strong> $payment</p>
            <hr>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='border-bottom: 1px solid #000;'>
                    <th style='text-align: left;'>ITEM</th>
                    <th style='text-align: center;'>QTY</th>
                    <th style='text-align: right;'>PRICE</th>
                </tr>";

    foreach ($cartData as $item) {
        $message .= "
                <tr>
                    <td style='padding: 5px 0;'>{$item['name']}</td>
                    <td style='text-align: center;'>{$item['qty']}</td>
                    <td style='text-align: right;'>ETB " . number_format($item['price'] * $item['qty'], 2) . "</td>
                </tr>";
    }

    $message .= "
            </table>
            <hr>
            <h3 style='text-align: right;'>GRAND TOTAL: ETB " . number_format($total, 2) . "</h3>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: orders@ebroshop.com";
    
    mail($admin_email, $subject, $message, $headers);
    echo json_encode(["status" => "success"]);
}
?>