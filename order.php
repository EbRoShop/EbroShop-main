<?php
session_start();
// This prevents any minor database warnings from breaking the JSON response
error_reporting(0); 
header('Content-Type: application/json');

// 1. Include the same DB connection used in your registration
include 'db.php'; 

$apiKey = getenv('BREVO_API_KEY');
$input = json_decode(file_get_contents('php://input'), true);

if ($input && $apiKey) {
    // Sanitize all inputs just like in register.php
    $name = mysqli_real_escape_string($conn, $input['name']); 
    $phone = mysqli_real_escape_string($conn, $input['phone']);
    $payment = mysqli_real_escape_string($conn, $input['payment']);
    $total = floatval($input['total']);
    $cart = $input['cart'];
    $order_id = rand(1000, 9999); 

    // --- STEP 1: IDENTIFY USER FOR HISTORY ---
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $customerEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';

    // If session is lost, search DB for the email
    if (empty($customerEmail)) {
        $search = "SELECT id, email FROM users WHERE first_name = '$name' LIMIT 1";
        $res = $conn->query($search);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $customerEmail = $row['email'];
            $user_id = $row['id'];
        }
    }

    // --- STEP 2: SAVE TO ORDERS TABLE (ORDER HISTORY) ---
    // This part ensures the order shows up in "Account Details"
    $sql = "INSERT INTO orders (user_id, order_id, total_amount, payment_method, status) 
            VALUES ('$user_id', '$order_id', '$total', '$payment', 'Pending')";
    $conn->query($sql);

    // --- STEP 3: SEND THE PROFESSIONAL EMAIL ---
    if (!empty($customerEmail)) {
        // Build the Product Table (The design you liked)
        $rows = "";
        foreach($cart as $p) {
            $sub = $p['price'] * $p['qty'];
            $rows .= "<tr>
                        <td style='padding:12px; border-bottom:1px solid #eee; color:#333;'>{$p['name']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:center;'>{$p['qty']}</td>
                        <td style='padding:12px; border-bottom:1px solid #eee; text-align:right;'>ETB " . number_format($sub, 2) . "</td>
                      </tr>";
        }

        $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";
        $senderEmail = 'ebroshoponline@gmail.com';

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
            "to" => array(array("email" => $customerEmail, "name" => $name)),
            "subject" => "Your Order Confirmation #$order_id",
            "htmlContent" => "
                <div style='font-family:Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius:10px;'>
                    <div style='text-align: center; border-bottom: 2px solid #136835; padding-bottom: 15px; margin-bottom: 20px;'>
                        <img src='$logoUrl' alt='EbRoShop' style='width: 200px;'>
                    </div>
                    <h2 style='color: #136835; text-align: center;'>Thank you for your order!</h2>
                    <p style='color:#555;'>Hello <b>$name</b>, your order has been received and is being processed.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <tr style='background: #222; color: white;'>
                            <th style='padding:12px; text-align:left;'>Product</th>
                            <th style='padding:12px;'>Qty</th>
                            <th style='padding:12px; text-align:right;'>Price</th>
                        </tr>
                        $rows
                        <tr style='font-weight: bold; font-size: 18px;'>
                        <td colspan='2' style='padding:20px 12px; text-align:right;'>Total Amount:</td>
                            <td style='padding:20px 12px; text-align:right; color:#136835;'>ETB " . number_format($total, 2) . "</td>
                        </tr>
                    </table>
                    <div style='background:#f9f9f9; padding:15px; border-radius:5px; border-left: 4px solid #136835;'>
                        <p style='margin:5px 0;'><b>Phone:</b> $phone</p>
                        <p style='margin:5px 0;'><b>Payment:</b> $payment</p>
                    </div>
                    <p style='font-size: 12px; color: #777; margin-top: 30px; text-align: center;'>
                        Contact us at <a href='mailto:$senderEmail'>$senderEmail</a> or <b>+251970130755</b>
                    </p>
                </div>"
        );

        // Send via Brevo API using the working register.php method
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    // ALWAYS RETURN SUCCESS SO TELEGRAM WORKS
    echo json_encode(["success" => true, "order_id" => $order_id]);
}
?>