<?php
// 1. Database connection
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Collect and sanitize input
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    // 3. Check if email already exists
    $checkEmail = "SELECT email FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        echo "<script>alert('Error: This email is already registered.'); window.history.back();</script>";
    } else {
        // 4. Hash the password for security
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

        // 5. Insert into database
        $sql = "INSERT INTO users (first_name, last_name, email, password, role) 
                VALUES ('$fname', '$lname', '$email', '$hashed_password', 'customer')";

        if ($conn->query($sql) === TRUE) {
            
            // ==========================================
            // NEW: SEND WELCOME EMAIL VIA BREVO API
            // ==========================================
            $apiKey = getenv('BREVO_API_KEY'); 
            $senderEmail = 'ebroshoponline@gmail.com'; 
            $siteUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
            
            // Replace this with your actual Cloudinary Logo URL
            $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1765983301/wwa0hvys9hynad7fju9u.jpg";

            $data = array(
                "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
                "to" => array(array("email" => $email, "name" => $fname)),
                "subject" => "Welcome to EbRoShop!",
                "htmlContent" => "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; text-align: center;'>
                    <img src='$logoUrl' alt='EbRoShop Logo' style='width: 150px; margin-bottom: 20px;'>
                    <h1 style='color: #333;'>Welcome to EbRoShop!</h1>
                    <p style='font-size: 16px; color: #555;'>Hello $fname, your account has been successfully activated. Next time you shop with us, log in for faster checkout.</p>
                    <br>
                    <a href='$siteUrl' style='background-color: #136835; color: white; padding: 15px 30px; text-decoration: none; font-size: 18px; border-radius: 5px; display: inline-block; font-weight: bold;'>Visit our store</a>
                    <br><br><br>
                    <hr style='border: 0; border-top: 1px solid #eee;'>
                    <p style='font-size: 12px; color: #999;'>If you have any questions, reply to this email or contact us at <a href='mailto:ebroshoponline@gmail.com'>ebroshoponline@gmail.com</a></p>
                </div>"
            );

            $ch = curl_init('https://api.api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ));

            curl_exec($ch); // Send the email
            curl_close($ch);
            // ==========================================

            echo "<script>
                    alert('Registration successful! Check your email for a welcome message.');
                    window.location.href = 'login.html';
                  </script>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
$conn->close();
?>