<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}



// 1. DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $order_id = (int)$_GET['delete_id'];
    $u_id = $_SESSION['user_id'];
    $conn->query("DELETE FROM orders WHERE id = $order_id AND user_id = $u_id");
    header("Location: account.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Info
$user_query = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();


// 3. FETCH HISTORY WITH ALL PRODUCT NAMES
$sql = "SELECT o.*, 
               (SELECT GROUP_CONCAT(product_name SEPARATOR ', ') 
                FROM order_items 
                WHERE order_id = o.id) AS all_products
        FROM orders o 
        WHERE o.user_id = $user_id 
        ORDER BY o.created_at DESC";
$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>My Account - EBRO Shop</title>
    <style>
        /* YOUR EXACT ORIGINAL DESIGNS */
        .header-title { font-size: 24px; font-weight: 800; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 1px; color: #0076ad;}
        .section-title { font-size: 18px; font-weight: 700; margin: 30px 0 15px 0; border-bottom: 2px solid #eee; padding-bottom: 5px; }
        
        .order-row { border-bottom: 1px solid #eee; padding: 15px 0; display: flex; justify-content: space-between; align-items: center; }
        
        /* THE COLOR BADGES */
        .status-badge { 
            color: #fff; 
            padding: 3px 10px; 
            font-size: 10px; 
            border-radius: 2px; 
            text-transform: uppercase; 
            font-weight: bold;
            display: inline-block;
        }
        .bg-pending { background: #333; }      /* Black for Pending */
        .bg-completed { background: #136835; }  /* Green for Completed */
        .bg-cancelled { background: #cc0000; }  /* Red for Cancelled */

        .address-card { border: 1px solid #e0e0e0; padding: 20px; border-radius: 4px; margin-bottom: 20px; background: #fafafa; }
        .user-name { font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; text-transform: uppercase; }
        .default-tag { background: #0076ad; color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 2px; }
        
        .btn-outline { width: 100%; border: 2px solid #0076ad; background: transparent; color: #0076ad; padding: 12px; font-size: 14px; font-weight: bold; text-transform: uppercase; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; box-sizing: border-box; transition: 0.3s; }
        .btn-outline:hover { background: #0076ad; color: white; }
        
        .del-x { color: #ff4d4d; text-decoration: none; font-weight: bold; font-size: 18px; margin-left: 15px; }
        .logout-link { display: block; text-align: center; margin-top: 30px; color: #cc0000; text-decoration: none; font-size: 14px; font-weight: bold; }
   
   

        /*For main header and others*/
         /* RESET */
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:#fff}

/* HEADER */
.header{
  background:#136835;
  height:62px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 16px;
  color:#fff;
}
.left,.right{display:flex;align-items:center;gap:18px}
.icon-btn{cursor:pointer;display:flex;align-items:center}
.icon-btn svg{
  width:22px;height:22px;stroke:#fff;fill:none;stroke-width:2;
}

/* CART BADGE */
.cart{position:relative;}
.badge{
  position:absolute;
  top:-6px;
  right:-6px;
  background:#fff;
  color:#136835;
  width:18px;
  height:18px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:11px;
  font-weight:bold;
  display:none;
}


/* EbRo logo – fully isolated */
.ebro-logo-link {
  display: flex;
  align-items: center;
  text-decoration: none;
  color: inherit;
}

/* Circle like icon */
.ebro-logo-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f4f4f4;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* Image behaves like icon */
.ebro-logo-img {
  width: 100%;
  height: 100%;
  object-fit:contain;
}

/* Text area */
.ebro-logo-text {
  margin-left: 8px;
}

/* Main text */
.ebro-logo-main {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.1;
}

/* Sub text */
.ebro-logo-sub {
  font-size: 11px;
  color: #ffffff;
}


/* LOGO */
.logo-area{display:flex;align-items:center;gap:10px}
.logo-circle{
  width:42px;height:42px;background:#fff;border-radius:50%;
  display:flex;align-items:center;justify-content:center;padding:5px;
}
.logo-text .main{font-size:20px;font-weight:bold}
.logo-text .sub{font-size:10px;margin-top:2px;text-transform:capitalize;}

/* OVERLAY */
#menuOverlay{
  position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.42);
  display:none;z-index:9;
}

/* SIDE MENU (SLIDE DRAWER) */
#sideMenu{
  position:fixed;
  top:0;
  left:-320px;
  width:320px;
  height:100%;
  background:#fafafa;
  box-shadow:3px 0 8px rgba(0,0,0,0.2);
  z-index:10;
  transition:left .28s ease;
  padding:20px 22px;
  overflow-y:auto;
}

#sideMenu a {
  text-decoration: none; /* remove underline */
  color: #111;           /* text color */
}

/* MENU SECTIONS */
.menu-title{
  color:#999;
  font-size:14px;
  margin:18px 0 6px;
}

.menu-item{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px 0;
  font-size:16px;
  color:#000;
  cursor:pointer;
  border-bottom:1px solid #eee;
}
.menu-item svg{
  width:22px;height:22px;stroke:#000;fill:none;stroke-width:1.7;
}

/* LOGIN ROW */
.login-row{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px 0 20px;
  border-bottom:1px solid #ddd;
}
.login-row .user-icon{
  width:46px;height:46px;border-radius:50%;background:#eee;
  display:flex;align-items:center;justify-content:center;
}
.login-row .user-icon svg{width:28px;height:28px;stroke:#777}

/* CLOSE BUTTON */
.close-btn{
  font-size:26px;
  font-weight:bold;
  cursor:pointer;
  margin-bottom:12px;
  display:inline-block;
}


/* for text header */
@keyframes ticker-scroll{
    0%   { transform: translateX(0%); }
    100% { transform: translateX(-50%); }
  }
@media (max-width:520px){
  .ticker-track{font-size:14px}
}

/* -------------------------
   SEARCH PANEL STYLES
   ------------------------- */
#searchPanel {
  position:fixed;
  top:0; left:0;
  width:100%; height:100%;
  background:#fff;
  z-index:9999;
  display:none;
  overflow-y:auto;
  -webkit-overflow-scrolling:touch;
}

.search-top {
  padding:12px;
  display:flex;
  gap:10px;
  align-items:center;
  border-bottom:1px solid #eee;
}

.search-top input {
  flex:1;
  padding:12px;
  font-size:16px;
  border:1px solid #ccc;
  border-radius:8px;
}

.search-close-btn {
  padding:10px 14px;
  background:#136835;
  color:#fff;
  border:none;
  border-radius:8px;
  cursor:pointer;
}

/* product card design (matches your screenshot) */
.products-wrap { padding:14px; }
.products-grid {
  display:flex;
  flex-wrap:wrap;
  gap:12px;
  justify-content:space-between;
}

.product-card {
    width: 48%;
    background:#fff;
    border-radius:14px;
    box-shadow:0 3px 8px rgba(0,0,0,0.07);
    padding-bottom:8px;
    overflow:hidden;
}

.product-card img {
    width:100%;
    height:150px;
    object-fit:cover;
    display:block;
}
.product-name {
  padding:10px;
    font-size:16px;
    font-weight:600;
}
.product-price {
    padding:0 10px;
    font-size:16px;
    font-weight:bold;
    color:#111;
}
.add-btn {
    margin:12px 10px 16px 10px;
    width:calc(100% - 20px);
    padding:10px 0;
    background:#008cff;
    color:#fff;
    border:none;
    font-size:15px;
    border-radius:8px;
    cursor:pointer;
}

/* small responsiveness */
@media (max-width:520px){
  .product-card{width:100%}
  .logo-text .main{font-size:16px}
}


 /*for FAQ's*/
        .links a:hover {
    color: #000;
}

.hidden {
    display: none;
}


/*For last footer*/
.payment-logos a:hover img {
    transform: scale(1.05);
}

/* Mobile adjustment */
@media (max-width: 600px) {
    .footer-text {
        font-size: 20px;
    }

    .payment-logos a img {
        height: 34px;
    }
}


/*for bottom nav*/
  .nav-item.active {
    color: #000;
  }

  .nav-item.active svg {
    stroke: #000;
  }

  .cart {
    position: relative;
  }

   </style>
</head>
<body>


<!-- TOP TICKER -->
<div style=" background:#136835; --white:#ffffff; --muted-white:rgba(255,255,255,0.95);">
  <div style="font-family: Helvetica Neue, Arial, sans-serif; background:#fff;">
    <header style="background:#136835; color:var(--white); width:100%; box-shadow: 0 1px 0 rgba(0,0,0,0.08);" class="site-header" role="banner">
      <div style="overflow:hidden; border-top:1px solid rgba(255,255,255,0.06);" class="ticker-wrap" aria-hidden="false">
        <div style="display:block; width:100%; padding:10px 0;" class="ticker" role="marquee">
          <div style="display:inline-block; white-space:nowrap; will-change:transform; font-weight:600; font-size:16px; color:var(--muted-white); animation: ticker-scroll 10s linear infinite;" class="ticker-track">
            <span style="display:inline-block; padding-right:60px;">Fast delivery all over Addis Abeba. Order Now.</span>
            <span style="display:inline-block; padding-right:60px;">ወዳሉበት በፍጥነት ወዳሉበት እናደርሳለን. አሁኑኑ ይዘዙን</span>
            <span style="padding-left:40px;">Fast delivery all over Addis Abeba. Order Now.</span>
            <span style="padding-left:40px;">ወዳሉበት በፍጥነት ወዳሉበት እናደርሳለን. አሁኑኑ ይዘዙን.</span>
          </div>
        </div>
      </div>
    </header>
  </div>
</div>

<!-- MAIN HEADER -->
<div  class="header">
  <div class="left">
    <div class="icon-btn" id="hamburgerBtn" aria-label="Open menu">
      <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </div>

   <a href="search.html" aria-label="Search" class="icon-btn">
          <svg viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="6"/>
          <path d="M21 21l-4-4"/>
          </svg>
       </a>
  </div>

 <div class="logo-area">
  <a href="account.php" class="ebro-logo-link">
    
    <div class="ebro-logo-circle" title="EbRoShop">
      <img src="https://res.cloudinary.com/die8hxris/image/upload/v1765983404/j5tztu0kuzdspwmk3zfg.jpg" alt="EbRoShop Logo" class="ebro-logo-img">
    </div>

    <div class="ebro-logo-text">
      <div class="ebro-logo-main">EbRoShop.com</div>
      <div class="ebro-logo-sub">by Ebro Gurage Tone</div>
    </div>

  </a>
</div>

  <div class="right">
    <div class="icon-btn" id="accountBtn" aria-label="Account">
      <svg viewBox="0 0 24 24">
        <path d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 20a8 8 0 0116 0"/>
      </svg>
    </div>

    <div class="cart icon-btn" id="cartBtn" aria-label="Cart">
      <svg viewBox="0 0 24 24">
        <path d="M3 3h2l3 12h10l3-8H6"/>
        <circle cx="9" cy="20" r="1"/>
        <circle cx="18" cy="20" r="1"/>
      </svg>
      <div class="badge" id="cartBadge">0</div>
    </div>
  </div>
</div>

<!-- OVERLAY + SIDE MENU -->
<div id="menuOverlay" aria-hidden="true"></div>

 <div id="sideMenu" aria-hidden="true">
  <span class="close-btn" id="closeMenu">✖️</span>

  <!--For signup-->
<a href="register.html" class="login-row" id="auth-link">
  <div class="user-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/>
      <path d="M4 20a8 8 0 0116 0"/>
    </svg>
  </div>
  <div id="auth-text" style="font-size:14px; font-weight: 800;color: #0d4a7e;">Signup or Register</div>
</a>

<!-- Main Links -->
  <a href="home.html" class="menu-item">
    <!-- Home Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M3 11l9-8 9 8"/>
  <path d="M5 10v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V10"/>
  <path d="M9 22V12h6v10"/>
</svg>

    <span>Home</span>
  </a>

  <a href="about.html" class="menu-item">
    <!-- Info Icon -->
  <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round">
  <circle cx="12" cy="12" r="10"/>
  <line x1="12" y1="11" x2="12" y2="16"/>
  <circle cx="12" cy="8" r="1"/>
</svg>
    <span>About-us</span>
  </a>

  <a href="contact.html" class="menu-item">
    <!-- Contact/Phone Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
  <path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.24
           11.4 11.4 0 003.6.6a1 1 0 011 1v3.5a1 1 0 01-1 1
           C10.6 22 2 13.4 2 3.5a1 1 0 011-1H6.5a1 1 0 011 1
           11.4 11.4 0 00.6 3.6 1 1 0 01-.25 1z"/>
</svg>
    <span>Contact-us</span>
  </a>

  <a href="faq.html" class="menu-item">
    <!-- FAQ/Question Icon -->
  <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
  <path d="M12 2a10 10 0 1 0 0 20a10 10 0 0 0 0-20zm0 15a1.25 1.25 0 1 1 0-2.5a1.25 1.25 0 0 1 0 2.5zm1.1-4.6c-.9.6-1.1 1-1.1 1.6h-2
           c0-1.5.6-2.4 1.9-3.2
           .9-.6 1.4-1 1.4-1.8
           0-.9-.7-1.5-1.8-1.5
           -1 0-1.8.6-1.9 1.7H7.6
           C7.7 6.2 9.4 5 11.5 5
           c2.4 0 4.2 1.4 4.2 3.6
           0 1.6-1 2.6-2.5 3.4z"/>
</svg>
    <span>FAQs</span>
  </a>

  <a href="terms.html" class="menu-item">
    <!-- Document Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
  <path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
  <path d="M14 2v6h6"/>
  <path d="M8 13l2 2l4-4"/>
</svg>
    <span>Terms Of Service</span>
  </a>

  <a href="returns.html" class="menu-item">
    <!-- Return/Arrow Icon -->
<svg viewBox="0 0 24 24" width="24" height="24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
 <!--First arrow (clockwise, top-right) -->
  <path d="M4.5 12a7.5 7.5 0 0 1 7.5-7.5"/>
  <polyline points="12 2 14 6 10 6"/>
  <!-- Second arrow (counter-clockwise, bottom-left) -->
  <path d="M19.5 12a7.5 7.5 0 0 1-7.5 7.5"/>
  <polyline points="12 22 10 18 14 18"/>
</svg> 
    <span>Return Policy</span>
  </a>

  <a href="privacy.html" class="menu-item">
    <!-- Lock Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
  <path d="M4 3h16v18H4z"/>
  <path d="M7 7h10M7 11h10M7 15h6"/>
</svg>
    <span>Privacy Policy</span>
  </a>

  <!-- Products -->
  <div class="menu-title">Products</div>

  <a href="basicfood.php" class="menu-item">
    <!-- Food Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M3 9h18l-1 10H4z"/>
  <path d="M16 4a2 2 0 1 1-4 0"/>
</svg>
    <span>Basic Food</span>
  </a>

  <a href="cooking.php" class="menu-item">
    <!-- Cooking Ingredients Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M9 5h6v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V5z"/>
  <path d="M9 5c0-1.5 1-3 3-3s3 1.5 3 3"/>
  <path d="M11 9h2"/>
  <path d="M11 13h2"/>
</svg>
    <span>Cooking ingrident</span>
  </a>

  <a href="babyproduct.php" class="menu-item">
    <!-- Baby Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="7" r="4"/>
      <path d="M6 21v-2a6 6 0 0112 0v2"/>
    </svg>
    <span>Baby product</span>
  </a>

  <a href="cosmotics.php" class="menu-item">
    <!-- Health/Medical Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 10h-6l-3-6-3 6H3l5 7-2 6 6-4 6 4-2-6 5-7z"/>
    </svg>
    <span>HealthCare</span>
  </a>

   <a href="packaged.php" class="menu-item">
    <!-- Health/Medical Icon -->
 <svg
  viewBox="0 0 24 24"
  width="24"
  height="24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <path d="M3 7l9-4 9 4" />
  <path d="M3 7v10l9 4 9-4V7" />
  <path d="M12 3v4" />
  <path d="M12 13v8" />
</svg>
    <span>Packaged Goods</span>
  </a>

   <a href="powdersoap.php" class="menu-item">
    <!-- Health/Medical Icon -->
    <svg
  viewBox="0 0 24 24"
  width="24"
  height="24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <!-- Detergent box -->
  <path d="M6 3h10l2 4v14H6z" />
  <!-- Box top -->
  <path d="M6 7h12" />
  <!-- Scoop handle -->
  <path d="M9 11c2-2 4-2 6 0" />
  <!-- Powder granules -->
  <circle cx="9" cy="16" r="0.8" />
  <circle cx="12" cy="17" r="0.8" />
  <circle cx="15" cy="16" r="0.8" />
</svg>
    <span>Laundery powders</span>
  </a>

    <a href="dayper.php" class="menu-item">
    <!-- Health/Medical Icon -->
    <svg
  viewBox="0 0 24 24"
  width="24"
  height="24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <!-- Diaper outer shape -->
  <path d="M4 7c2-3 14-3 16 0v6c0 4-4 7-8 7s-8-3-8-7z" />
  <!-- Waist folds -->
  <path d="M7 7v2M17 7v2" />
  <!-- Inner absorbent area -->
  <path d="M9 12c1 2 5 2 6 0" />
</svg>

    <span>Dayper$Wipes</span>
  </a>
  <a href="packedfood.php" class="menu-item">
    <!-- Health/Medical Icon -->
    <svg
  viewBox="0 0 24 24"
  width="24"
  height="24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <!-- Can body -->
  <path d="M6 6c0-2 12-2 12 0v12c0 2-12 2-12 0z" />
  <!-- Top rim -->
  <path d="M6 6c0 2 12 2 12 0" />
  <!-- Bottom rim -->
  <path d="M6 18c0-2 12-2 12 0" />
  <!-- Label -->
  <path d="M9 10h6v4H9z" />
</svg>
    <span>Packed Foods</span>
  </a>

    <a href="otheringrident.php" class="menu-item">
    <!-- Health/Medical Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <!-- Bowl -->
  <path d="M3 14h18" />
  <path d="M5 14c0 4 4 6 7 6s7-2 7-6" />

  <!-- Powder mound -->
  <path d="M8 14c1-2 2-3 4-3s3 1 4 3" />

  <!-- Granules -->
  <circle cx="9" cy="10" r="0.6" />
  <circle cx="12" cy="9" r="0.6" />
  <circle cx="15" cy="10" r="0.6" />
</svg>
    <span>Spices powders</span>
  </a>

    <a href="oil.php" class="menu-item">
    <!-- Health/Medical Icon -->
    <svg viewBox="0 0 24 24" width="24" height="24"
     fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round">
  <!-- Bottle neck -->
  <path d="M10 2h4v3h-4z"/>
  <!-- Bottle body -->
  <path d="M9 5h6c1.5 0 2 1.5 2 3v9c0 2-2 4-5 4s-5-2-5-4V8c0-1.5.5-3 2-3z"/>
  <!-- Oil level -->
  <path d="M7 13h10"/>
  <!-- Oil drop -->
  <path d="M19 14c1.2 1.6 1.2 2.8 0 3.8a2 2 0 0 1-3 0c-1.2-1-.8-2.4 0-3.8l1.5-2z"/>
</svg>
    <span>Food Oils</span>
  </a>

   <a href="modes.php" class="menu-item">
    <!-- Health/Medical Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24"
     fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round">
  <!-- Pad body -->
  <rect x="6" y="3" width="12" height="18" rx="4" ry="4"/>
  <!-- Inner absorbent area -->
  <rect x="9" y="6" width="6" height="12" rx="3" ry="3"/>
  <!-- Side wings -->
  <path d="M6 9l-3 3 3 3"/>
  <path d="M18 9l3 3-3 3"/>
</svg>
    <span>Modes$Soft</span>
  </a>

    <a href="liquidsoap.php" class="menu-item">
    <!-- Health/Medical Icon -->
   <svg viewBox="0 0 24 24" width="24" height="24"
     fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round">
  <!-- Pump top -->
  <path d="M10 2h4"/>
  <path d="M12 2v3"/>
  <path d="M12 5h6"/>
  <!-- Bottle body -->
  <rect x="7" y="6" width="10" height="15" rx="3"/>
  <!-- Liquid level -->
  <path d="M7 13h10"/>
  <!-- Soap drop -->
  <path d="M12 11c1.2 1.6 1.2 2.8 0 3.8a2 2 0 0 1-3 0c-1.2-1-.8-2.4 0-3.8l1.5-2z"/>
</svg>
    <span>Liquid soap</span>
  </a>
<br>
<br>
<br>
</div>



<!-- SEARCH PANEL -->
<div id="searchPanel" aria-hidden="true">
  <div class="search-top">
    <input id="searchInput" type="text" placeholder="Search products..." aria-label="Search products">
    <button class="search-close-btn" id="closeSearch">Close</button>
  </div>

  <div class="products-wrap">
    <div id="searchResults" class="products-grid">
      <!-- products will render here -->
    </div>
  </div>
</div>


   <br>
   <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Arial, sans-serif; background: #fff; margin: 0; padding: 20px; color: #333; } ">
    <div  class="header-title">My Account</div>

    <div class="section-title">Order History</div>

    <?php if (!$orders || $orders->num_rows == 0): ?>
        <p style="color:#185282;">No orders yet.</p>
    <?php else: ?>
        <?php while($row = $orders->fetch_assoc()): ?>
            <?php 
                // Logic for colors
                $stat = strtolower($row['status']);
                $colorClass = 'bg-pending';
                if ($stat == 'completed' || $stat == 'delivered') $colorClass = 'bg-completed';
                if ($stat == 'cancelled') $colorClass = 'bg-cancelled';
            ?>
            <div class="order-row">
                <div>
                    <strong>
                      <?php echo !empty($row['all_products']) ? htmlspecialchars($row['all_products']) : "Product Purchase"; ?>
                    </strong><br>
                    <small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                </div>

                <div style="text-align: right; display: flex; align-items: center;">
                    <div style="margin-right: 5px;">
                        <strong>ETB <?php echo number_format($row['total_amount'], 2); ?></strong><br>
                        <span class="status-badge <?php echo $colorClass; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </div>
                    <a href="account.php?delete_id=<?php echo $row['id']; ?>" class="del-x" onclick="return confirm('Delete record?')">✕</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <div class="section-title">Account Details</div>
    <div class="address-card">
        <div class="user-name">
            <?php echo htmlspecialchars($user_data['first_name'] . " " . $user_data['last_name']); ?> 
            <span class="default-tag"><?php echo strtoupper($user_data['role']); ?></span>
        </div>
        <div class="location">
            Email ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>
        </div>
    </div>

    <a href="login.html" class="btn-outline">Return to Back</a>
    


    <br>
    <br>
    <br>
<img src="https://res.cloudinary.com/die8hxris/image/upload/v1765983301/wwa0hvys9hynad7fju9u.jpg" width="400px" height="150px"/>
<!--FAQ's-->
 <div style="   margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f7f7f7;
    color: #222;">

<div style="  padding: 20px;" class="footer">

    <h2 style="    font-size: 28px;
    font-weight: 700;
    margin-bottom: 20px;">CONNECT WITH US</h2>

    <div style="    display: flex;
    gap: 24px;
    font-size: 32px;
    margin-bottom: 24px;" class="social-icons">
        <a style=" color: #222;
    text-decoration: none;" href="https://www.facebook.com/profile.php?id=100083927815758"><i class="fab fa-facebook-f"></i></a>
        <a style=" color: #222;
    text-decoration: none;" href="#"><i class="fab fa-instagram"></i></a>
    </div>

    <!-- Ebroshop Section (CLOSED by default) -->
    <div style="   border-top: 1px solid #ddd;
    padding: 18px 0;" class="section">
        <div style=" display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;" class="section-header" onclick="toggleSection('adeeg', this)">
            <h3 style="  margin: 0;
    font-size: 24px;
    font-weight: 700;">EbRoShop</h3>
            <span style="  font-size: 26px;" class="toggle">+</span>
        </div>

        <div style="margin-top: 16px;" class="links hidden" id="adeeg">
          <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="home.html">Home</a>

          <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="about.html">About-us</a>
           
           <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="faq.html">FAQs</a>
            <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="contact.html">Contact Us</a>
        </div>
    </div>

    <!-- Store Policy -->
    <div style="   border-top: 1px solid #ddd;
    padding: 18px 0;" class="section">
        <div style=" display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;" class="section-header" onclick="toggleSection('store', this)">
            <h3 style="  margin: 0;
    font-size: 24px;
    font-weight: 700;">Store Policy</h3>
            <span style="  font-size: 26px;" class="toggle">+</span>
        </div>

        <div style="margin-top: 16px;" class="links hidden" id="store">
           <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="terms.html">Terms Of Service</a>
           
           <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="privacy.html">Privacy Policy</a>
         
            <a style="  display: block;
    text-decoration: none;
    color: #9a9a9a;
    font-size: 20px;
    margin: 14px 0;" href="returns.html">Return Policy</a>
        </div>
    </div>

    <!-- Contact Us -->
    <div style=" border-top: 1px solid #ddd;
    padding: 18px 0;" class="section">
        <div style=" display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;" class="section-header" onclick="toggleSection('contact', this)">
            <h3 style="  margin: 0;
    font-size: 24px;
    font-weight: 700;">Contact Us</h3>
            <span style="  font-size: 26px;" class="toggle">+</span>
        </div>

        <div style="margin-top: 16px;" class="links hidden" id="contact">
             <a style="  display: block;
    text-decoration: none;
    color: #000000;
    font-size: 20px;
    margin: 14px 0;" >Phone: <b style="color: #676767;">+251970130755</b></a>
           <a style="  display: block;
    text-decoration: none;
    color: #000000;
    font-size: 20px;
    margin: 14px 0;" >Email: <b style="color: #676767;">ebrohayru77@gmail.com</b></a>
        </div>
    </div>

</div>
</div>



<!--Last footer-->

<div style="  margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #ffffff;">
<div style=" text-align: center;
    padding: 40px 20px 30px;" class="footer-bottom">

    <!-- Logo -->
    <div class="footer-logo">
        <img style="  max-width: 160px;
    margin-bottom: 24px;" src="https://res.cloudinary.com/die8hxris/image/upload/v1765983370/zz6tex5swhmvjff8aphf.jpg" alt="Ebroshop Logo">
    </div>

    <!-- Copyright -->
    <div style="  color: #828080;
    font-size: 28px;
    font-weight: 400;
    margin-bottom: 40px;" class="footer-text">
        EbRoShop.Com©2025 All Rights Reserved
    </div>

    <!-- Payment / Partner Logos (Clickable) -->
    <div style=" display: flex;
    justify-content: center;
    align-items: center;
    gap: 32px;
    flex-wrap: wrap;" class="payment-logos">
        <a href="tel:*889#" target="_self">
            <img style=" height: 40px;
    object-fit: contain;
    transition: transform 0.2s ease;" src="https://res.cloudinary.com/die8hxris/image/upload/v1765983122/fqldh9ima4wy9hrsjkhv.jpg" alt="CBE">
        </a>

        <a href="tel:*127#" target="_self">
            <img style=" height: 40px;
    object-fit: contain;
    transition: transform 0.2s ease;" src="https://res.cloudinary.com/die8hxris/image/upload/v1765983555/e6oydnwyeoxag8vhar1w.jpg" alt="Telebirr">
        </a>
    </div>

</div>
</div>



<!--For bottom nav-->

<div style=" padding-bottom: calc(72px + env(safe-area-inset-bottom));">

    <nav style="  position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 72px;
    padding-bottom: env(safe-area-inset-bottom);
    background: #fff;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 9999;" class="bottom-nav">

  <a href="home.html" style="  flex: 1;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-size: 12px;
    font-family: system-ui, -apple-system, sans-serif;" class="nav-item active">
    <svg style=" width: 24px;
    height: 24px;
    display: block;
    margin: 0 auto 4px;
    stroke: #666;" viewBox="0 0 24 24" fill="none" stroke-width="2">
      <path d="M3 10.5L12 3l9 7.5"/>
      <path d="M5 10v10h14V10"/>
    </svg>
    Home
  </a>

  <a href="search.html" style="  flex: 1;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-size: 12px;
    font-family: system-ui, -apple-system, sans-serif;"  class="nav-item">
    <svg style=" width: 24px;
    height: 24px;
    display: block;
    margin: 0 auto 4px;
    stroke: #666;"  viewBox="0 0 24 24" fill="none" stroke-width="2">
      <circle cx="11" cy="11" r="8"/>
      <line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </svg>
    Search
  </a>
  <div class="icon-btn" id="searchBtn" aria-label="Search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="M21 21l-4-4"/></svg>
    </div>

  <a href="collection.html" style="  flex: 1;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-size: 12px;
    font-family: system-ui, -apple-system, sans-serif;"  class="nav-item">
    <svg style=" width: 24px;
    height: 24px;
    display: block;
    margin: 0 auto 4px;
    stroke: #666;"  viewBox="0 0 24 24" fill="none" stroke-width="2">
      <rect x="3" y="3" width="7" height="7"/>
      <rect x="14" y="3" width="7" height="7"/>
      <rect x="3" y="14" width="7" height="7"/>
      <rect x="14" y="14" width="7" height="7"/>
    </svg>
    Collection
  </a>

  <a href="login.html" style="  flex: 1;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-size: 12px;
    font-family: system-ui, -apple-system, sans-serif;"  class="nav-item">
    <svg style=" width: 24px;
    height: 24px;
    display: block;
    margin: 0 auto 4px;
    stroke: #666;"  viewBox="0 0 24 24" fill="none" stroke-width="2">
      <circle cx="12" cy="8" r="4"/>
      <path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>
    </svg>
    Account
  </a>

  <a  style="  flex: 1;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-size: 12px;
    font-family: system-ui, -apple-system, sans-serif;"  href="Cart.html" class="nav-item cart">
    <span style=" position: absolute;
    top: 8px;
    right: 28%;
    background: #000;
    color: #fff;
    font-size: 10px;
    min-width: 16px;
    height: 16px;
    line-height: 16px;
    border-radius: 50%;" class="badge">0</span>
    <svg style=" width: 24px;
    height: 24px;
    display: block;
    margin: 0 auto 4px;
    stroke: #666;"  viewBox="0 0 24 24" fill="none" stroke-width="2">
      <circle cx="9" cy="21" r="1"/>
      <circle cx="20" cy="21" r="1"/>
      <path d="M1 1h4l3.6 12.6a2 2 0 0 0 2 1.4h9.4"/>
      <path d="M7 6h15l-1.5 8H9"/>
    </svg>
    Cart
  </a>

</nav>
</div>

 <script>
    /*For signup or logout*/
     // Fetch login status from your existing session checker
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            const authLink = document.getElementById('auth-link');
            const authText = document.getElementById('auth-text');

            if (data.loggedIn) {
                // Change to Logout mode
                authLink.href = "javascript:void(0)"; // Prevent immediate jump
                authLink.onclick = function() {
                    if(confirm("Are you sure you want to log out?")) {
                        window.location.href = 'logout.php';
                    }
                };
                authText.innerText = "Logout (" + data.name + ")";
            }
        })
        .catch(err => console.error("Session check failed", err));




    /*header part*/
/* -------------------------
   Safe DOM ready wrapper
   ------------------------- */
(function(){
  // product data (editable)
  const defaultProducts = [
    { id:1, name:"Rooti Slice Cad...", price:1.00, image:"https://via.placeholder.com/600x400?text=Rooti+Slice", category:"Bread", tags:["slice","white"], description:"Fresh sliced white bread." },
    { id:2, name:"Rooti Qamadi...", price:1.00, image:"https://via.placeholder.com/600x400?text=Qamadi", category:"Bread", tags:["qamadi","brown"], description:"Soft brown qamadi bread." },
    { id:3, name:"Rooti Beegar...", price:0.50, image:"https://via.placeholder.com/600x400?text=Beegar", category:"Bread", tags:["beegar","round"], description:"Round beegar bread." },
    { id:4, name:"Ceesh (Arabic)", price:1.00, image:"https://via.placeholder.com/600x400?text=Ceesh", category:"Flatbread", tags:["arabic","ceesh"], description:"Arabic ceesh soft flatbread." }
  ];
  const extraProducts = [
    { id:5, name:"Pasta Italian", price:1.20, image:"https://via.placeholder.com/600x400?text=Pasta", category:"Food", tags:["pasta","italy"], description:"Premium Italian pasta." },
    { id:6, name:"Rice 5kg", price:5.00, image:"https://via.placeholder.com/600x400?text=Rice+5kg", category:"Rice", tags:["white","rice"], description:"High quality white rice." },
    { id:7, name:"Egg Pack", price:3.00, image:"https://via.placeholder.com/600x400?text=Eggs", category:"Dairy", tags:["egg","protein"], description:"Fresh farm eggs." }
  ];
  const allProducts = [...defaultProducts, ...extraProducts];

  // DOM elements
  const hamburger = document.getElementById("hamburgerBtn");
  const sideMenu = document.getElementById("sideMenu");
  const menuOverlay = document.getElementById("menuOverlay");
  const closeMenu = document.getElementById("closeMenu");
  const searchBtn = document.getElementById("searchBtn");
  const searchPanel = document.getElementById("searchPanel");
  const closeSearch = document.getElementById("closeSearch");
  const searchInput = document.getElementById("searchInput");
  const searchResults = document.getElementById("searchResults");
  const cartBtn = document.getElementById("cartBtn");
  const accountBtn = document.getElementById("accountBtn");
  const badge = document.getElementById("cartBadge");

  /* -------------------------
     Cart helpers (persistent)
     ------------------------- */
  function loadCart(){
    try{ return JSON.parse(localStorage.getItem("cartItems")) || []; } catch { return []; }
  }
  function saveCart(cart){
    localStorage.setItem("cartItems", JSON.stringify(cart));
  }
  function updateBadge(){
    const cart = loadCart();
    const total = cart.reduce((s,i)=> s + (i.qty||0), 0);
    if(total > 0){
      badge.style.display = "flex";
      badge.innerText = total;
    } else {
      badge.style.display = "none";
      badge.innerText = "0";
    }
    localStorage.setItem("cartCount", total);
  }
  // initialize badge on load
  updateBadge();

  /* -------------------------
     Quick add function
     ------------------------- */
  window.quickAdd = function(id){
    const product = allProducts.find(p => p.id == id);
    if(!product){ alert("Product not found"); return; }
    const cart = loadCart();
    const existing = cart.find(it => it.id == product.id);
    if(existing){
      existing.qty = (existing.qty || 0) + 1;
    } else {
      cart.push({
        id: product.id,
        name: product.name,
        img: product.image,
        price: parseFloat(product.price),
        qty: 1
      });
    }
    saveCart(cart);
    updateBadge();
    const short = product.name.length > 30 ? product.name.slice(0,28) + "…" : product.name;
    alert(short + " added to cart");
  };

  /* -------------------------
     Side menu handlers
     ------------------------- */
  function openMenu(){
    sideMenu.style.left = "0";
    menuOverlay.style.display = "block";
    sideMenu.setAttribute("aria-hidden","false");
  }
  function closeMenuFn(){
    sideMenu.style.left = "-320px";
    menuOverlay.style.display = "none";
    sideMenu.setAttribute("aria-hidden","true");
  }
  hamburger.addEventListener("click", openMenu);
  menuOverlay.addEventListener("click", closeMenuFn);
  closeMenu.addEventListener("click", closeMenuFn);

  /* -------------------------
     Search rendering
     ------------------------- */
  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function renderProducts(list){
    if(!Array.isArray(list)) list = [];
    if(list.length === 0){
      searchResults.innerHTML = "<div style='padding:18px;color:#777'>No products found</div>";
      return;
    }
    const html = list.map(p => {
      return `
        <div class="product-card" data-id="${p.id}">
          <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}">
          <div class="product-name">${escapeHtml(p.name)}</div>
          <div class="product-price">${escapeHtml(Number(p.price).toFixed(2))} birr</div>
          <button class="add-btn" onclick="quickAdd(${p.id})">Quick Add</button>
        </div>
      `;
    }).join("");
    searchResults.innerHTML = "<div class='products-grid'>"+html+"</div>";
  }

  function showDefaultProducts(){
    renderProducts(defaultProducts);
  }

  /* open/close search */
  searchBtn.addEventListener("click", function(){
    searchPanel.style.display = "block";
    searchPanel.setAttribute("aria-hidden","false");
    searchInput.value = "";
    showDefaultProducts();
    setTimeout(()=> searchInput.focus(), 120);
  });
  closeSearch.addEventListener("click", function(){
    searchPanel.style.display = "none";
    searchPanel.setAttribute("aria-hidden","true");
    searchResults.innerHTML = "";
    searchInput.value = "";
  });

  /* search input handler (match name, category, description, tags) */
  searchInput.addEventListener("input", function(){
    const q = (this.value || "").trim().toLowerCase();
    if(q === ""){
      showDefaultProducts();
      return;
    }
    const results = allProducts.filter(p => {
      return (p.name || "").toLowerCase().includes(q)
        || (p.category || "").toLowerCase().includes(q)
        || (p.description || "").toLowerCase().includes(q)
        || (p.tags || []).join(" ").toLowerCase().includes(q);
    });
    renderProducts(results);
  });

  /* -------------------------
     Navigation buttons
     ------------------------- */
  accountBtn.addEventListener("click", ()=> { window.location.href = "login.html"; });
  cartBtn.addEventListener("click", ()=> { window.location.href = "Cart.html"; });

  /* Expose updateBadge so other pages can call window.updateBadge() after adding */
  window.updateCartBadge = updateBadge;

})();





    //about part Animate only when visible on scroll
    const items = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.3 });

    items.forEach(el => observer.observe(el));



            /*for FAQ's*/
        
function toggleSection(id, header) {
    const content = document.getElementById(id);
    const icon = header.querySelector('.toggle');

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.textContent = '−';
    } else {
        content.classList.add('hidden');
        icon.textContent = '+';
    }
}
  </script>
</body>
</html>