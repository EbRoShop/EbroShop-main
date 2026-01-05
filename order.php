<?php
session_start();
header('Content-Type: application/json');

// 1. Database connection (Hidden errors)
include 'db.php'; 

// 2. Get the Brevo Key
$apiKey = getenv('BREVO_API_KEY'); 

// 3. Get Input from Javascript
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $name = mysqli_real_escape_string($conn, $input['name']);
    $phone = mysqli_real_escape_string($conn, $input['phone']);
    $payment = $input['payment'];
    $total = $input['total'];
    $cart = $input['cart'];
    $order_id = rand(1000, 9999);

    // --- FIX: GET EMAIL EVEN IF SESSION IS EXPIRED ---
    $customerEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;

    if (!$customerEmail) {
        // Search database for the email using the name provided
        $search = "SELECT email FROM users WHERE first_name LIKE '%$name%' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
        } else {
            // BACKUP: If no email found, send it to your store email so the order works!
            $customerEmail = "ebroshoponline@gmail.com";
        }
    }

    // Build Email Table
    $rows = "";
    foreach($cart as $p) {
        $sub = $p['price'] * $p['qty'];
        $rows .= "<tr>
                    <td style='padding:8px; border:1px solid #ddd;'>{$p['name']}</td>
                    <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$p['qty']}</td>
                    <td style='padding:8px; border:1px solid #ddd;'>ETB " . number_format($sub, 2) . "</td>
                  </tr>";
    }

    // BREVO DATA (Using your register.php style)
    $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
    
    $data = array(
        "sender" => array("name" => "EbRoShop", "email" => "ebroshoponline@gmail.com"),
        "to" => array(array("email" => $customerEmail, "name" => $name)),
        "subject" => "Order Receipt #$order_id",
        "htmlContent" => "
            <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee;'>
                <img src='$logoUrl' style='width: 200px; margin-bottom: 20px;'>
                <h2 style='color: #136835;'>Order Confirmation</h2>
                <p>Hello $name, thank you for your order! Here is your receipt.</p>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background:#f8f8f8;'><th>Product</th><th>Qty</th><th>Total</th></tr>
                    $rows
                </table>
                <h3 style='text-align:right;'>Grand Total: ETB " . number_format($total, 2) . "</h3>
                <p><b>Phone:</b> $phone <br> <b>Payment:</b> $payment</p>
            </div>"
    );

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'api-key: ' . $apiKey,
        'Content-Type: application/json'
    ));

    curl_exec($ch);
    curl_close($ch);

    // ALWAYS return success so the user sees "Thank You" and Telegram works
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?