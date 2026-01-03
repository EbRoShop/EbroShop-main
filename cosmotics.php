<?php
include 'db.php';

// 1. Fetch Products for basicfood Category
$result = $conn->query("SELECT * FROM products WHERE category = 'Cosmotics'");
$dynamic_html = "";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $isSoldOut = ($row['status'] == 'sold_out');
        $badge = $isSoldOut ? '<div style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; padding: 5px 12px; font-size: 12px; border-radius: 5px; font-weight: bold; z-index: 2;">SOLD OUT</div>' : '';
        
        if ($isSoldOut) {
            $btnAction = "handleSoldOut()";
            $btnStyle = "background-color: #0e7dc7ff; cursor: not-allowed;";
            $btnText = "Sold Out";
        } else {
            // Variables for Cart
            $btnAction = "addToCart(".$row['id'].", '".addslashes($row['name'])."', ".$row['price'].", '".$row['image_url']."')";
            $btnStyle = "background-color: #136835; cursor: pointer;"; // Green color
            $btnText = "Quick Add";
        }

        // --- THE FIX FOR SORT: Added data-price and data-name here ---
        $dynamic_html .= '
        <div class="product" data-price="'.$row['price'].'" data-name="'.strtolower($row['name']).'" style="position: relative; padding: 15px; border: 1px solid #f0f0f0; border-radius: 15px; background: #fff; display: flex; flex-direction: column; align-items: center; text-align: center;">
            ' . $badge . '
            <div style="height: 180px; width: 100%; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                <img src="'.$row['image_url'].'" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
            <h3 style="color: #000; font-size: 14px; font-weight: 600; margin-bottom: 8px; height: 40px; overflow: hidden;">'.htmlspecialchars($row['name']).'</h3>
            <p style="font-weight:bold; margin-bottom: 12px;">'.number_format($row['price'], 0).' ETB/pcs</p>
            <button type="button" onclick="'.$btnAction.'" 
                style="'.$btnStyle.' color: #fff; border: none; width: 100%; padding: 12px 0; border-radius: 50px; font-weight: bold; font-size: 14px;">
                '.$btnText.'
            </button>
        </div>';
    }
}

// 2. JavaScript Fixes
$js_code = "
<script>
// Faithfully Alert Fix
function handleSoldOut() {
    alert(\"This item is sold out. You can't add it right now. We'll add it soon.\");
}

// Quick Add Fix
function addToCart(id, name, price, image) {
    const CART_KEY = 'cartItems';
    let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
    let existing = cart.find(item => item.id == id);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ id: id, name: name, price: parseFloat(price), image: image, qty: 1 });
    }
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    window.location.href = 'Cart.html';
}

// --- CRITICAL SORT FIX ---
// This forces the sort system to find the correct container
document.addEventListener('DOMContentLoaded', function() {
    const sortEl = document.getElementById('sort');
    if(sortEl) {
        sortEl.onchange = function() {
            const container = document.getElementById('products');
            const items = Array.from(container.getElementsByClassName('product'));
            if (this.value === 'priceLow') items.sort((a,b) => a.dataset.price - b.dataset.price);
            if (this.value === 'priceHigh') items.sort((a,b) => b.dataset.price - a.dataset.price);
            if (this.value === 'az') items.sort((a,b) => a.dataset.name.localeCompare(b.dataset.name));
            if (this.value === 'za') items.sort((a,b) => b.dataset.name.localeCompare(a.dataset.name));
            container.innerHTML = '';
            items.forEach(el => container.appendChild(el));
        };
    }
});
</script>
";
// 3. Template Injection
$html_file = "Cosmotics.html"; 
if (file_exists($html_file)) {
    $template = file_get_contents($html_file);
    
    // Fix Redirection Error: Change .html links to .php so they stay on the dynamic page
    $template = str_replace('Cosmotics.html', 'Cosmotics.php', $template);
    
    // Inject JS and Products
    $template = str_replace('</body>', $js_code . '</body>', $template);
    $hook = '<div class="products" id="products">';
    echo str_replace($hook, $hook . $dynamic_html, $template);
} else {
    echo "File $html_file not found. Please check the name.";
}
?>