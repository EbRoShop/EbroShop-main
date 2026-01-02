<?php
// 1. DIRECT FILE LOADING (No folder needed)
require __DIR__ . '/Exception.php';
require __DIR__ . '/PHPMailer.php';
require __DIR__ . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Database Connection
include 'db.php'; 

// --- PART A: Sending the Link ---
if (isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32));
    
    // Save token to DB
    $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mail = new PHPMailer(true);
        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ebroshoponline@gmail.com'; 
            $mail->Password   = 'mfaknagaapurcpjm';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Email Content
            $mail->setFrom('ebroshoponline@gmail.com', 'EbRoShop');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            
            // DYNAMIC LINK DETECTION
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = "$protocol://$host/forget.php?token=$token";

            $mail->Body    = "<h3>Reset Your Password</h3>
                              <p>Click the link below to securely reset your password:</p>
                              <a href='$resetLink'>$resetLink</a>";

            $mail->send();
            echo "<script>alert('Check your email inbox!'); window.location.href='login.html';</script>";
        } catch (Exception $e) { 
            echo "Mailer Error: {$mail->ErrorInfo}"; 
        }
    } else { 
        echo "<script>alert('Email not found in our system.'); window.history.back();</script>"; 
    }
}

// --- PART B: Showing the New Password Form ---
if (isset($_GET['token'])): 
    $token = $_GET['token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 50px; text-align: center; }
        .box { max-width: 350px; margin: auto; background: #fff; padding: 30px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { font-size: 20px; text-transform: uppercase; margin-bottom: 20px; color: #136835; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-update { background: #0076ad; color: #fff; padding: 12px; border: none; width: 100%; cursor: pointer; font-weight: bold; text-transform: uppercase; border-radius: 50px; }
        .btn-update:hover { background: #2d9bf0; }
    </style>
</head>
<body>
    <div class="box">
        <h2>New Password</h2>
        <form method="POST" action="forget.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="new_pass" placeholder="Enter New Password" required minlength="6">
            <button type="submit" name="update_now" class="btn-update">Update Password</button>
        </form>
    </div>
</body>
</html>
<?php endif; ?>

<?php
// --- PART C: Updating the Database ---
if (isset($_POST['update_now'])) {
    $token = $_POST['token'];
    $hashed = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    
    // Update password and clear the token
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $hashed, $token);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Success! Your password has been updated.'); window.location.href='login.html';</script>";
    } else {
        echo "<div style='text-align:center; margin-top:50px;'><h3>Error: Invalid or expired link.</h3><a href='forget.html'>Try again</a></div>";
    }
}
?>