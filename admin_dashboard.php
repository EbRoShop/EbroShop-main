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
        /* YOUR ORIGINAL STYLES */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; padding: 20px; color: #333; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1, h2 { color: #0076ad; margin-top: 0; }
        
        .alert { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: bold; }
        
        .nav-links { display: flex; gap: 10px; align-items: center; }
        .btn-view-orders { background: #136835; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn-view-orders:hover { background: #0e5029; }
        .btn-logout { background: #ff4d4d; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn-logout:hover { background: #cc0000; }

        .product-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #eef7ff; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #d1e9ff; }
        .product-form input, .product-form select { padding: 12px; border: 1px solid #b8d8ff; border-radius: 6px; font-size: 14px; outline: none; }
        .btn-add { grid-column: span 2; padding: 14px; background: #0076ad; color: white; border: none; cursor: pointer; font-weight: bold; text-transform: uppercase; border-radius: 6px; transition: 0.3s; }
        .btn-add:hover { background: #005a85; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; overflow: hidden; border-radius: 8px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #0076ad; color: white; font-weight: 600; }
        .img-preview { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .available { background: #d4edda; color: #155724; }
        .sold_out { background: #f8d7da; color: #721c24; }
        .category-tag { background: #e2e8f0; padding: 4px 10px; border-radius: 6px; font-size: 12px; color: #4a5568; font-weight: 600; }
        .btn-edit { color: #0076ad; text-decoration: none; font-weight: bold; margin-right: 15px; }
        .btn-delete { color: #d9534f; text-decoration: none; font-weight: bold; }

        /* NEW MOBILE COMFORT ADDITIONS (Doesn't break your design) */
        @media (max-width: 600px) {
            body { padding: 10px; }
            .container { padding: 15px; }
            .nav-links { flex-direction: column; width: 100%; }
            .btn-view-orders, .btn-logout { width: 100%; text-align: center; box-sizing: border-box; }
            .product-form { grid-template-columns: 1fr; }
            .btn-add { grid-column: span 1; }
            .table-container { overflow-x: auto; } /* Allows sliding the table on small screens */
            h1 { font-size: 20px; }
            input, select, button { font-size: 16px !important; } /* Prevents tiny text zoom */
        }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Welcome, <strong><?php echo $_SESSION['full_name']; ?></strong></p>
        </div>
        <div class="nav-links">
            <a href="admin_orders.php" class="btn-view-orders">VIEW CUSTOMER ORDERS</a>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Log out of admin panel?')">Logout</a>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert">
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
            <option value="Dayper&Wipes">Dayper&Wipes </option>
            <option value="Cosmotics">Cosmotics</option>
            <option value="Liquidsoap">Liquid soap</option>
            <option value="powderSoap">Powder soap</option>
            <option value="Modes&Softs">Modes&Soft </option>
            <option value="packagedGoods">Packaged Goods</option>
        </select>
        <button type="submit" class="btn-add">SAVE PRODUCT</button>
    </form>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 40px 0;">
    <h2>Manage Existing Products</h2>
    
    <div class="table-container"> <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
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
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><span class="category-tag"><?php echo strtoupper($row['category']); ?></span></td>
                        <td><strong>ETB <?php echo number_format($row['price'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge <?php echo $row['status']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $row['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                            <a href="admin_dashboard.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>