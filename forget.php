<?php
// --- 1. ERROR REPORTING ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 2. DATABASE CONNECTION ---
// If db.php fails, we handle it gracefully here
if (!file_exists('db.php')) {
    die("Error: db.php is missing from the server.");
}
include 'db.php'; 

// ==========================================
// PART A: SENDING THE RESET LINK
// ==========================================
if (isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32));
    
    // Update the user record
    $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $apiKey = getenv('BREVO_API_KEY'); 
        $senderEmail = 'ebroshoponline@gmail.com'; 

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host_url = $_SERVER['HTTP_HOST'];
        $resetLink = "$protocol://$host_url/forget.php?token=$token";

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
            "to" => array(array("email" => $email)),
            "subject" => "Reset Your Password",
            "htmlContent" => "<html><body><h3>Reset Password</h3><p>Link: <a href='$resetLink'>$resetLink</a></p></body></html>"
        );

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

        if ($httpCode == 201 || $httpCode == 200) {
            echo "<script>alert('Success! Check email.'); window.location.href='login.html';</script>";
        } else {
            echo "<h3>API Error ($httpCode)</h3><pre>$response</pre>";
        }
    } else {
        echo "<script>alert('Email not found.'); window.history.back();</script>";
    }
}

// ==========================================
// PART B: NEW PASSWORD FORM
// ==========================================
// ==========================================
// PART B: THE NEW PASSWORD FORM (MOBILE OPTIMIZED)
// ==========================================
if (isset($_GET['token'])): 
    $token = $_GET['token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Set New Password</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            text-align: center; 
            padding: 20px; 
            background: #f4f4f4; 
            margin: 0;
        }
        .box { 
            max-width: 400px; 
            margin: 40px auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        h2 { 
            color: #333; 
            font-size: 24px; /* Larger Header */
            margin-bottom: 20px;
        }
        input { 
            width: 100%; 
            padding: 15px; /* Bigger touch area */
            margin: 15px 0; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-size: 18px; /* Larger Text for Mobile */
            box-sizing: border-box; 
        }
        button { 
            background: #0076ad; 
            color: white; 
            border: none; 
            width: 100%; 
            padding: 16px; 
            cursor: pointer; 
            border-radius: 50px; 
            font-size: 18px; 
            font-weight: bold;
            transition: background 0.3s;
        }
        button:active {
            background: #005a84;
            transform: scale(0.98);
        }

       /* Styling the password container to position the eye */ 
       .password-container { 
                    position: relative;
                    width: 100%;
        } 
       .password-container input {
                width: 100%; 
                padding: 15px;
                padding-right: 45px;
                /* Leave space for the eye */ 
                margin: 15px 0; 
                border: 1px solid #ccc; 
                border-radius: 8px; 
                font-size: 18px; 
                box-sizing: border-box; 
        }
       .toggle-password {
                 position: absolute;
                 right: 15px;
                 top: 50%; 
                 transform: translateY(-50%); 
                 cursor: pointer; 
                 color: #666; 
                 font-size: 20px; 
                 z-index: 2; 
        } 
</style>
</head>
<body>
    <div class="box">
        <h2>Enter New Password Here👇</h2>
        <p style="color: #666; font-size: 14px;">Enter a strong password for your EbRoShop account.</p>
        <form method="POST" action="forget.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="password-container"> 
              <input type="password" name="new_pass" id="new_pass" placeholder="Enter New Password" required minlength="6"> 
              <i class="fa-solid fa-eye toggle-password" id="eyeIcon" onclick="togglePassword()"></i>
            </div> 
         <button type="submit" name="update_now">Update Password</button>
        </form>
    </div>
      <script>
        function togglePassword() {
            const passwordInput = document.getElementById("new_pass");
            const eyeIcon = document.getElementById("eyeIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                // Change eye to eye-slash
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                // Change eye-slash back to eye
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
<?php endif; ?>


<?php
// ==========================================
// PART C: UPDATING THE DATABASE
// ==========================================
if (isset($_POST['update_now'])) {
    $token = $_POST['token'];
    $hashed = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $hashed, $token);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Updated! Please login.'); window.location.href='login.html';</script>";
    } else {
        echo "<h3>Link invalid or expired.</h3>";
    }
}
?>