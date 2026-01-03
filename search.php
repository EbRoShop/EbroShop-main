<?php
// search.php
include 'db.php'; // Connects to your database
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Search Overlay</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* Your Exact Design remains 100% the same */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; }

        #searchWrapper {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
        }

        #searchPanel {
            width: 65%;
            height: 100%;
            background: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 15px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 10px 20px;
        }
        .search-header h2 { font-size: 26px; font-weight: bold; color: #111; }
        .close-btn { font-size: 28px; color: #333; text-decoration: none; cursor: pointer; padding: 5px; }

        .input-area { padding: 0 20px 15px 20px; }
        .search-box-wrapper {
            background: #f1f1f1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 10px 15px;
        }
        #searchInput {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 17px;
            outline: none;
            padding: 8px 0;
        }

        .results-container { flex: 1; overflow-y: auto; padding: 10px 20px; }
        
        .product-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eee;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .product-card img { width: 100%; height: 130px; object-fit: contain; }
        .product-name { padding: 10px 10px 2px; font-size: 15px; font-weight: bold; }
        .product-price { padding: 0 10px; font-size: 14px; color: #136835; font-weight: bold; }
        
        .quick-add-btn {
            margin: 10px;
            width: calc(100% - 20px);
            padding: 10px;
            background: #008cff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .status-msg {
            text-align: center;
            margin-top: 50px;
            color: #777;
            font-size: 16px;
        }

        .outside-click-area { flex: 1; cursor: pointer; }
    </style>
</head> 
<body>

<div id="searchWrapper">
    <div id="searchPanel">
        <div class="search-header">
            <h2>Search</h2>
            <a href="javascript:history.back()" class="close-btn">✕</a>
        </div>

        <div class="input-area">
            <div class="search-box-wrapper">
                <input id="searchInput" type="text" placeholder="Search favorite items...">
                <i class="fa-solid fa-magnifying-glass" style="color: #888;"></i>
            </div>
        </div>
        
        <div class="results-container">
            <div id="searchResults">
                <div class="status-msg">Start typing to find products...</div>
            </div>
        </div>
    </div>
    
    <div class="outside-click-area" onclick="history.back()"></div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    // This function runs every time you type a letter
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();

        if (query.length < 2) {
            searchResults.innerHTML = '<div class="status-msg">Start typing to find products...</div>';
            return;
        }

        // We fetch the results from our backend "fetch_products.php"
        fetch(`fetch_search_results.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => {
                if (products.length === 0) {
                    searchResults.innerHTML = '<div class="status-msg">No products found</div>';
                    return;
                }

                // Map the database products to your exact design
                searchResults.innerHTML = products.map(p => `
                    <div class="product-card">
                        <img src="${p.image}" alt="${p.name}">
                        <div class="product-name">${p.name}</div>
                        <div class="product-price">${p.price} Birr</div>
                        <button class="quick-add-btn" onclick="addToCart(${p.id})">Quick Add</button>
                    </div>
                `).join('');
            });
    });

    // Link to your existing Cart logic
    function addToCart(id) {
        // You can use your existing Cart.html logic here
        window.location.href = "Cart.html?add=" + id;
    }
</script>
</body>
</html>