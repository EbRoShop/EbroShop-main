<?php
ob_start(); 
session_start();
error_reporting(0); 
header('Content-Type: application/json');

include 'db.php'; 

// CONFIGURATION
$apiKey = getenv('BREVO_API_KEY'); 
$botToken = "8310816737:AAEIrdAPb4IXUwTl4fUeM-9k2qTE2jQmmuk";      
$chatId = "5335234629";          

if ($_POST && $apiKey) {
    $name    = mysqli_real_escape_string($conn, $_POST['name']); 
    $phone   = $_POST['phone'];
    $email   = $_POST['email']; 
    $address = $_POST['address'];
    $payment = $_POST['payment'];
    $total   = $_POST['total'];
    $cart    = json_decode($_POST['cart'], true);
    $order_id = rand(1000, 9999); 

    // 1. SAVE TO DATABASE
    $user_id = $_SESSION['user_id'] ?? 0;
    $conn->query("INSERT INTO orders (user_id, order_id, total_amount, payment_method, status) 
                 VALUES ('$user_id', '$order_id', '$total', '$payment', 'Pending')");

    // 2. TELEGRAM PART (With Photo)
    $caption = "📦 *New Order #$order_id*\n\n"
             . "👤 Customer: $name\n"
             . "📞 Phone: $phone\n"
             . "💰 Total: ETB $total\n"
             . "💳 Method: $payment";

    if (isset($_FILES['proof'])) {
        $photo = $_FILES['proof']['tmp_name'];
        $post_fields = ['chat_id' => $chatId, 'photo' => new CURLFile($photo), 'caption' => $caption, 'parse_mode' => 'Markdown'];
        $ch = curl_init("https://api.telegram.org/bot$botToken/sendPhoto");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    // 3. ENHANCED EMAIL DESIGN (Matching your Page)
    if (!empty($email)) {
        $rows = "";
        foreach($cart as $p) {
            $sub = $p['price'] * $p['qty'];
            $rows .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #444;'>{$p['name']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center; color: #444;'>{$p['qty']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #136835;'>ETB " . number_format($sub, 2) . "</td>
            </tr>";
        }

        $htmlContent = "
        <div style='background-color: #f4f4f4; padding: 20px; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;'>
            <div style='max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 6px solid #136835;'>
                
                <div style='background: #fff; padding: 20px; text-align: center;'>
                    <h1 style='color: #136835; margin: 0; font-size: 24px;'>Order Confirmed!</h1>
                    <p style='color: #666;'>Thank you for shopping with EbRoShop</p>
                </div>

                <div style='padding: 20px;'>
                    <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                        <p style='margin: 5px 0;'><strong>Order ID:</strong> #$order_id</p>
                        <p style='margin: 5px 0;'><strong>Customer:</strong> $name</p>
                        <p style='margin: 5px 0;'><strong>Address:</strong> $address</p>
                    </div>

                    <table style='width: 100%; border-collapse: collapse;'>
                        <thead>
                            <tr style='background: #136835; color: #fff;'>
                                <th style='padding: 12px; text-align: left;'>Product</th>
                                <th style='padding: 12px;'>Qty</th>
                                <th style='padding: 12px; text-align: right;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>$rows</tbody>
                    </table>
                    <div style='margin-top: 20px; text-align: right; padding: 10px;'>
                        <h2 style='color: #136835; margin: 0;'>Total: ETB " . number_format($total, 2) . "</h2>
                        <p style='color: #888; font-size: 14px;'>Paid via $payment</p>
                    </div>
                </div>

                <div style='background: #222; color: #fff; padding: 20px; text-align: center; font-size: 13px;'>
                    <p>Questions? Contact us at ebroshoponline@gmail.com</p>
                    <p>© " . date("Y") . " EbRoShop. All Rights Reserved.</p>
                </div>
            </div>
        </div>";

        $emailData = array(
            "sender" => array("name" => "EbRoShop", "email" => "ebroshoponline@gmail.com"),
            "to" => array(array("email" => $email, "name" => $name)),
            "subject" => "Receipt for Order #$order_id",
            "htmlContent" => $htmlContent
        );

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . $apiKey, 'Content-Type: application/json'));
        curl_exec($ch);
        curl_close($ch);
    }

    ob_end_clean(); 
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>