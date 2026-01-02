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
                "subject" => "Customer account confirmation",
                "htmlContent" => "
                <div style='background-color: #f8f9fb; padding: 40px 10px; font-family: Helvetica, Arial, sans-serif;'>
                    <div style='max-width: 500px; margin: auto; background-color: #ffffff; padding: 40px 30px; border-radius: 4px;'>
                        
                        <div style='text-align: left; margin-bottom: 40px;'>
                            <img src='$logoUrl' alt='Logo' style='width: 200px; height: auto;'>
                        </div>

                        <h1 style='font-size: 28px; color: #1d1d1f; margin-bottom: 20px; font-weight: 500;'>
                            Welcome to EbRoShop!
                        </h1>

                        <p style='font-size: 18px; color: #6e6e73; line-height: 1.5; margin-bottom: 30px;'>
                            You've activated your customer account. Next time you shop with us, log in for faster checkout.
                        </p>

                        <div style='text-align: center; margin-top: 40px;'>
                            <a href='$siteUrl' style='background-color: #2b98d3; color: #ffffff; padding: 18px 0; text-decoration: none; font-size: 18px; border-radius: 6px; display: block; font-weight: 500;'>
                                Visit our store
                            </a>
                        </div>

                        <div style='margin-top: 50px; border-top: 1px solid #e5e5e5; padding-top: 30px;'>
                            <p style='font-size: 14px; color: #86868b; line-height: 1.6;'>
                                If you have any questions, reply to this email or contact us at 
                                <a href='mailto:ebroshoponline@gmail.com' style='color: #2b98d3; text-decoration: none;'>info@ebroshop.com</a>. 
                                You can also reach us by calling our helpline number at +251970130755
                            </p>
                        </div>
                    </div>
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