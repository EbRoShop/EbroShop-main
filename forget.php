<?php
// 1. Database Connection
// Ensure your db.php is in the same folder
include 'db.php'; 

// ==========================================
// PART A: SENDING THE RESET LINK (API METHOD)
// ==========================================
if (isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32));
    
    // Update the user record with the new token
    $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // --- BREVO CONFIGURATION ---
        // Replace this with your actual key starting with xkeysib-
        $apiKey = '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'; 
        $senderEmail = 'ebroshoponline@gmail.com'; 

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $resetLink = "$protocol://$host/forget.php?token=$token";

        $data = array(
            "sender" => array("name" => "EbRoShop", "email" => $senderEmail),
            "to" => array(array("email" => $email)),
            "subject" => "Reset Your Password",
            "htmlContent" => "<html><body>
                                <h2>Password Reset Request</h2>
                                <p>Click the button below to reset your password:</p>
                                <a href='$resetLink' style='background:#0076ad; color:white; padding:12px; text-decoration:none; border-radius:5px; display:inline-block;'>Reset My Password</a>
                                <p>Or copy this link: $resetLink</p>
                              </body></html>"
        );

        // Sending via HTTPS (Port 443) which Render allows
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
            echo "<script>alert('Success! Check your email inbox.'); window.location.href='login.html';</script>";
        } else {
            // If it fails, this will show the exact error from Brevo
            echo "<script>alert('Error ($httpCode): " . addslashes($response) . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email not found.'); window.history.back();</script>";
    }
}

// ==========================================
// PART B: THE NEW PASSWORD FORM (HTML)
// ==========================================
if (isset($_GET['token'])): 
    $token = $_GET['token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; padding: 50px; }
        .box { max-width: 350px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { background: #0076ad; color: #fff; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 50px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Set New Password</h2>
        <form method="POST" action="forget.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="new_pass" placeholder="Enter New Password" required minlength="6">
            <button type="submit" name="update_now">Update Password</button>
        </form>
    </div>
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
    
    // Clear token after use so it can't be used twice
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $hashed, $token);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Password updated successfully!'); window.location.href='login.html';</script>";
    } else {
        echo "<div style='text-align:center;'><h3>Link expired or invalid.</h3><a href='forget.html'>Try again</a></div>";
    }
}
?>