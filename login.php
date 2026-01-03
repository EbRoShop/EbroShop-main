<?php
/**
 * 1. LOAD CONFIGURATION
 * We include db.php FIRST. It contains your 30-day session settings 
 * and the $conn database connection. This prevents 'Session already active' warnings.
 */
include 'db.php';

// Check connection (from db.php)
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * 2. PROCESS LOGIN POST REQUEST
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    // Search for the user in your 'users' table
    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if the password matches the hash in the database
        if (password_verify($pass, $user['password'])) {
            
            // 3. SAVE DATA INTO THE SESSION (Uses the 30-day rules from db.php)
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role']      = $user['role'];
            
            // Logic for name: Use first/last name if available, otherwise email
            $_SESSION['full_name'] = isset($user['first_name']) ? $user['first_name'] . " " . $user['last_name'] : $email;

            // 4. REDIRECT BASED ON ROLE
            if ($user['role'] == 'admin') {
                // Takes you to your special admin tools
                header("Location: admin_dashboard.php");
            } else {
                // Takes customers to the shopping page
                // IMPORTANT: Ensure home.html is now home.php to stay logged in!
                header("Location: home.html");
            }
            exit();
        }
    }
    
    // 5. ERROR HANDLING
    // If we reach here, either email was wrong or password failed
    echo "<script>alert('Invalid Email or Password'); window.history.back();</script>";
}
?>