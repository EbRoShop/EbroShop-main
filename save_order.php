<?php
// 1. ONLY include db.php. 
// It already handles the 30-day session start and database connection.
include 'db.php';

header('Content-Type: application/json');

// 2. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// No need to check $conn->connect_error here because db.php handles the die() if it fails.

// 3. Get the data from the JavaScript Fetch request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$user_id = $_SESSION['user_id'];
$name    = mysqli_real_escape_string($conn, $data['name']);
$phone   = mysqli_real_escape_string($conn, $data['phone']);
$payment = mysqli_real_escape_string($conn, $data['payment']);
$total   = $data['total'];
$cart    = $data['cart'];

// 4. Start Transaction
$conn->begin_transaction();

try {
    // Insert into 'orders' table
    $stmt1 = $conn->prepare("INSERT INTO orders (user_id, full_name, phone, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt1->bind_param("issds", $user_id, $name, $phone, $total, $payment);
    $stmt1->execute();
    
    $order_id = $conn->insert_id; 

    // Insert each item into 'order_items' table
    $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_name, price, quantity) VALUES (?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        $stmt2->bind_param("isdi", $order_id, $item['name'], $item['price'], $item['qty']);
        $stmt2->execute();
    }

    $conn->commit();
    
    // IMPORTANT: Only clear the CART session if you use one, 
    // NEVER use session_destroy() here.
    // unset($_SESSION['cart']); 

    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>