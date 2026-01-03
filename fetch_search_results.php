<?php
include 'db.php';
header('Content-Type: application/json');

// Clear any previous output buffers to ensure only JSON is sent
ob_clean(); 

$query = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

if ($query !== '') {
    // Search across ALL categories automatically
    $sql = "SELECT * FROM products WHERE name LIKE '%$query%' OR category LIKE '%$query%' LIMIT 15";
    $result = $conn->query($sql);
    
    $products = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    echo json_encode($products);
} else {
    echo json_encode([]);
}
exit();
?>