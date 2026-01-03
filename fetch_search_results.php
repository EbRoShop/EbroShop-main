<?php
// fetch_search_results.php
include 'db.php';

$query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if ($query !== '') {
    // Search by name or category in your 'products' table
    $sql = "SELECT * FROM products 
            WHERE name LIKE '%$query%' 
            OR category LIKE '%$query%' 
            LIMIT 10";
            
    $result = $conn->query($sql);
    $products = [];

    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);
} else {
    echo json_encode([]);
}
?>