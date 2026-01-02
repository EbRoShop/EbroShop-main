<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    $checkEmail = "SELECT email FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Email already exists.'); window.history.back();</script>";
    } else {
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (first_name, last_name, email, password, role) 
                VALUES ('$fname', '$lname', '$email', '$hashed_password', 'customer')";

        if ($conn->query($sql) === TRUE) {
            
            // --- BREVO EMAIL SYSTEM ---
            $apiKey = getenv('BREVO_API_KEY'); 
            $senderEmail = 'ebroshoponline@gmail.com'; // MUST be verified in Brevo
            $siteUrl = "https://ebroshop.onrender.com"; // Your Render URL
            
            // Your Cloudinary Logo
            $logoUrl = "https://res.cloudinary.com/die8hxris/image/upload/v1767382208/n8ixozf4lj5wfhtz2val.jpg";

            $data = array(
                "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
                "to" => array(array("email" => $email, "name" => $fname)),
                "subject" => "Welcome to EbRoShop!",
                "htmlContent" => "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; text-align: center; border-radius:10px;'>
                    <img src='$logoUrl' alt='Logo' style='width: 150px;'>
                    <h1 style='color: #136835;'>Welcome to EbRoShop!</h1>
                    <p style='font-size: 16px; color: #555;'>Hello $fname, your customer account is now active.</p>
                    <p style='color: #777;'>Next time you shop with us, login for a faster checkout.</p>
                    <br>
                    <a href='$siteUrl' style='background-color: #136835; color: white; padding: 15px 35px; text-decoration: none; font-size: 18px; border-radius: 50px; display: inline-block; font-weight: bold;'>Visit our store</a>
                    <br><br>
                    <p style='font-size: 12px; color: #bbb;'>If you have any questions, reply to this email or contact us at $senderEmail. You can also reach us by calling our phone number at +251970130755</p>
                </div>"
            );

            // FIXED URL: api.brevo.com
            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Success message
            echo "<script>
                    alert('Registration Successful! Welcome email sent to $email');
                    window.location.href = 'login.html';
                  </script>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
$conn->close();
?>