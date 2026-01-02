<?php
session_start();

// --- 1. SECURITY LOCK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 2. HANDLE DELETE PRODUCT ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?msg=Product Deleted");
    }
    $stmt->close();
    exit();
}

// --- 3. FETCH ALL PRODUCTS ---
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Admin Dashboard - EBRO Shop</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f4f4f4; padding: 10px; color: #333; margin: 0; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        h1 { font-size: 22px; color: #0076ad; margin-bottom: 5px; }
        
        /* Mobile Responsive Header */
        .header-section { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .nav-links { display: flex; flex-wrap: wrap; gap: 10px; }
        
        /* Bigger Buttons for Fingers */
        .btn-view-orders, .btn-logout, .btn-add { 
            padding: 12px 15px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: bold; 
            text-align: center;
            font-size: 14px;
            flex: 1; /* Makes buttons grow to fill width on mobile */
            min-width: 140px;
        }
        .btn-view-orders { background: #136835; color: white; }
        .btn-logout { background: #ff4d4d; color: white; }
        .btn-add { background: #0076ad; color: white; border: none; width: 100%; margin-top: 10px; cursor: pointer; }

        /* Form Style - Stacked for Mobile */
        .product-form { display: flex; flex-direction: column; gap: 12px; background: #eef7ff; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .product-form input, .product-form select { padding: 15px; border: 1px solid #b8d8ff; border-radius: 8px; font-size: 16px; width: 100%; box-sizing: border-box; }
        
        /* Scrollable Table for Mobile */
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; background: white; border-radius: 8px; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; } /* Ensures table doesn't squish too much */
        th, td { padding: 12px 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; }
        th { background: #0076ad; color: white; }

        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        .btn-edit { color: #0076ad; font-weight: bold; padding: 8px; display: inline-block; }
        .btn-delete { color: #d9534f; font-weight: bold; padding: 8px; display: inline-block; }

        /* Tablet/Desktop layout adjustment */
        @media (min-width: 768px) {
            body { padding: 20px; }
            .header-section { flex-direction: row; justify-content: space-between; align-items: center; }
            .product-form { display: grid; grid-template-columns: 1fr 1fr; }
            .btn-add { grid-column: span 2; }
            .nav-links { flex-wrap: nowrap; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-section">
        <div>
            <h1>Admin Dashboard</h1>
            <p style="margin:0;">User: <strong><?php echo $_SESSION['full_name']; ?></strong></p>
        </div>
        <div class="nav-links">
            <a href="admin_orders.php" class="btn-view-orders">VIEW ORDERS</a>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Log out?')">Logout</a>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✓ <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <h2>Add New Product</h2>
    <form action="manage_products.php" method="POST" class="product-form">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" step="0.01" name="price" placeholder="Price (ETB)" required>
        <input type="text" name="image_url" placeholder="Cloudinary Image URL" required>
        <select name="status">
            <option value="available">Available</option>
            <option value="sold_out">Sold Out</option>
        </select>
        <select name="category" required>
            <option value="" disabled selected>Select Category</option>
            <option value="baby">Baby Products</option>
            <option value="basicfood">Basic Foods</option>
            <option value="packedfood">Packed Foods</option>
            <option value="oil">Food Oils</option>
            <option value="cookingItems">Cooking Ingredients</option>
            <option value="spiecsPowder">Spiecs Powder</option>
            <option value="Dayper&Wipes">Dayper&Wipes</option>
            <option value="Cosmotics">Cosmotics</option>
            <option value="Liquidsoap">Liquid soap</option>
            <option value="powderSoap">Powder soap</option>
            <option value="Modes&Softs">Modes&Soft</option>
            <option value="packagedGoods">Packaged Goods</option>
        </select>
        <button type="submit" class="btn-add">SAVE PRODUCT</button>
    </form>

    <h2>Manage Products</h2>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Img</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><img src="<?php echo $row['image_url']; ?>" class="img-preview" onerror="this.src='https://via.placeholder.com/60?text=No+Img'"></td>
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong><br><small><?php echo strtoupper($row['category']); ?></small></td>
                        <td>ETB <?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo ($row['status'] == 'available') ? '✅' : '❌'; ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                            <a href="admin_dashboard.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this?')">Del</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px;">No products.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>