<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Search Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Your original design styles */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; }
        #searchWrapper { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; }
        #searchPanel { width: 85%; max-width: 400px; height: 100%; background: #fff; display: flex; flex-direction: column; }
        .search-header { display: flex; justify-content: space-between; padding: 20px; align-items: center; }
        .input-area { padding: 0 20px 15px; }
        .search-box-wrapper { background: #f1f1f1; border-radius: 4px; display: flex; align-items: center; padding: 10px; }
        #searchInput { flex: 1; border: none; background: transparent; font-size: 16px; outline: none; }
        .results-container { flex: 1; overflow-y: auto; padding: 10px 20px; }
        .product-card { border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; padding: 10px; text-align: center; }
        .product-card img { width: 100%; height: 150px; object-fit: contain; }
        .product-name { font-weight: bold; margin: 10px 0 5px; }
        .product-price { color: #136835; font-weight: bold; margin-bottom: 10px; }
        .quick-add-btn { width: 100%; padding: 10px; background: #008cff; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .status-msg { text-align: center; color: #888; margin-top: 20px; }
    </style>
</head>
<body>

<div id="searchWrapper">
    <div id="searchPanel">
        <div class="search-header">
            <h2>Search</h2>
            <a href="javascript:history.back()" style="text-decoration:none; color:#333; font-size:24px;">✕</a>
        </div>
        <div class="input-area">
            <div class="search-box-wrapper">
                <input id="searchInput" type="text" placeholder="Search items..." autocomplete="off">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
        </div>
        <div class="results-container" id="searchResults">
            <div class="status-msg">Start typing to find products...</div>
        </div>
    </div>
    <div style="flex:1" onclick="history.back()"></div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        if (query.length < 1) {
            searchResults.innerHTML = '<div class="status-msg">Start typing to find products...</div>';
            return;
        }

        // FETCH DATA FROM THE CLEAN PHP WORKER
        fetch(`fetch_search_results.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => {
                if (products.length === 0) {
                    searchResults.innerHTML = '<div class="status-msg">No products found</div>';
                    return;
                }

                searchResults.innerHTML = products.map(p => {
                    const isSoldOut = (p.stock <= 0 || p.status === 'sold_out');
                    return `
                        <div class="product-card" style="${isSoldOut ? 'opacity: 0.5;' : ''}">
                            <img src="${p.image}" alt="${p.name}">
                            <div class="product-name">${p.name}</div>
                            <div class="product-price">${p.price} Birr</div>
                            ${isSoldOut
                            ? '<button class="quick-add-btn" style="background:#888;" disabled>Sold Out</button>' 
                            : `<button class="quick-add-btn" onclick="addToCart(${p.id})">Quick Add</button>`
                            }
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                searchResults.innerHTML = '<div class="status-msg">Error connecting to database</div>';
            });
    });

    function addToCart(id) {
        window.location.href = "Cart.html?add=" + id;
    }
</script>
</body>
</html>