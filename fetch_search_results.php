<?php
// fetch_search_results.php
include 'db.php'; 

// Set header to JSON and clear any accidental output
header('Content-Type: application/json');
if (ob_get_length()) ob_clean(); 

$query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if ($query !== '') {
    // We select image_url specifically to match your database
    $sql = "SELECT id, name, price, image_url, status, category FROM products 
            WHERE name LIKE '%$query%' 
            OR category LIKE '%$query%' 
            LIMIT 20";
            
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