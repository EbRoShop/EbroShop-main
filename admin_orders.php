<?php
session_start();
include 'db.php';

// 1. UPDATE STATUS LOGIC (Checks if the Update button was clicked)
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    header("Location: admin_orders.php"); 
    exit();
}

// 2. FETCH ALL ORDERS + PRODUCT NAMES
// We use GROUP_CONCAT to grab all products belonging to one Order ID
$sql = "SELECT o.*, u.first_name, u.last_name, 
        GROUP_CONCAT(oi.product_name SEPARATOR ', ') as all_products
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC";

$all_orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - EBRO Shop</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; padding: 20px; color: #333; margin: 0; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #136835; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 24px; }
        .btn-back { background: #0076ad; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 20px; transition: 0.3s; }
        .btn-back:hover { background: #005a85; }

        .table-container { overflow-x: auto; border-radius: 8px; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; background: white; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #136835; color: white; font-weight: 600; }
        
        .product-list { font-weight: bold; color: #0076ad; font-size: 14px; max-width: 300px; }
        .badge { padding: 6px 12px; border-radius: 20px; color: white; font-weight: bold; font-size: 11px; text-transform: uppercase; display: inline-block; }
        .pending { background: #333; }
        .completed { background: #136835; }
        .cancelled { background: #e74c3c; }

        select { padding: 8px; border-radius: 6px; border: 1px solid #ddd; outline: none; }
        .btn-save { background: #0066ff; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .btn-save:hover { background: #004fb3; }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .container { padding: 15px; }
            h1 { font-size: 20px; }
            .btn-back { width: 100%; text-align: center; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="btn-back">← BACK TO DASHBOARD</a>
    <h1>Customer Order Management</h1>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Items Purchased</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_orders && $all_orders->num_rows > 0): ?>
                    <?php while($row = $all_orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td class="product-list">
                            <?php 
                                // Show product names if they exist, otherwise show "No items listed"
                                echo !empty($row['all_products']) ? htmlspecialchars($row['all_products']) : '<span style="color:gray; font-weight:normal;">No items listed</span>'; 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                        <td><strong>ETB <?php echo number_format($row['total_amount'], 2); ?></strong></td>
                        <td>
                            <?php 
                            $s = strtolower($row['status']);
                            $class = ($s == 'completed' || $s == 'delivered') ? 'completed' : (($s == 'cancelled') ? 'cancelled' : 'pending');
                            ?>
                            <span class="badge <?php echo $class; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <select name="new_status">
                                    <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Completed" <?php if($row['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                    <option value="Cancelled" <?php if($row['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-save">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">No orders found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>