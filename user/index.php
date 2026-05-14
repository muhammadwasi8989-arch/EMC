<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit();
}
require_once '../db.php';

// Fetch products
$products = $conn->query("SELECT * FROM products WHERE status='active' ORDER BY featured DESC, created_at DESC");
$allProducts = [];
while ($p = $products->fetch_assoc()) $allProducts[] = $p;

// Categories count
$catQuery = $conn->query("SELECT category, COUNT(*) as cnt FROM products WHERE status='active' GROUP BY category");
$categories = [];
while ($c = $catQuery->fetch_assoc()) $categories[] = $c;

// Featured products
$featured = array_filter($allProducts, fn($p) => $p['featured']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SH Market — Premium Online Shopping</title>
<link rel="stylesheet" href="css/user.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar">
  <a href="index.php" class="nav-brand">
    <div class="brand-ic"><i class="fas fa-store"></i></div>
    <div class="brand-nm">SH <span>Market</span></div>
  </a>

  <ul class="nav-links">
    <li><a href="#" class="active">Home</a></li>
    <li><a href="#productsSection">Products</a></li>
    <li><a href="#categories">Categories</a></li>
    <li><a href="#about">About</a></li>
  </ul>

  <div class="nav-right">
    <div class="nav-user">
      <div class="nav-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
      <span style="display:none" id="userName"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
    <button class="cart-toggle" onclick="toggleCart()">
      <i class="fas fa-bag-shopping"></i>
      <span class="cart-count">0</span>
    </button>
    <?php if($_SESSION['role']==='admin'): ?>
    <a href="../admin/index.php" class="logout-btn" style="border-color:rgba(232,56,13,.4);color:var(--primary)">
      <i class="fas fa-gauge"></i> Admin
    </a>
    <?php endif; ?>
    <a href="../logout.php" class="logout-btn" onclick="return confirm('Sign out?')">
      <i class="fas fa-right-from-bracket"></i> Logout
    </a>
    <button class="hamburger" onclick="toggleMobileNav()"><i class="fas fa-bars"></i></button>
  </div>
</nav>

<!-- MOBILE NAV -->
<div class="nav-mobile" id="mobileNav">
  <a href="#">Home</a>
  <a href="#productsSection">Products</a>
  <a href="#categories">Categories</a>
  <a href="#about">About</a>
  <?php if($_SESSION['role']==='admin'): ?><a href="../admin/index.php">Admin Panel</a><?php endif; ?>
  <a href="../logout.php">Logout</a>
</div>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-tag"><i class="fas fa-star"></i> Pakistan's #1 Online Store</div>
    <h1>Shop <em>Premium</em><br>Products Online</h1>
    <p>Discover thousands of quality products — Electronics, Fashion, Accessories & more. Best prices, fast delivery across Pakistan.</p>
    <div class="hero-btns">
      <a href="#productsSection" class="btn-hero btn-hero-primary" onclick="scrollToProducts()">
        <i class="fas fa-arrow-right"></i> Shop Now
      </a>
      <a href="#categories" class="btn-hero btn-hero-outline">
        Browse Categories
      </a>
    </div>
    <div class="hero-stats">
      <div>
        <div class="h-stat-num"><?= count($allProducts) ?>+</div>
        <div class="h-stat-label">Products</div>
      </div>
      <div>
        <div class="h-stat-num">10K+</div>
        <div class="h-stat-label">Happy Customers</div>
      </div>
      <div>
        <div class="h-stat-num">48hr</div>
        <div class="h-stat-label">Delivery</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== CATEGORIES ===== -->
<section class="section" id="categories">
  <div class="sec-head">
    <div>
      <h2 class="sec-title">Shop by <span>Category</span></h2>
      <p class="sec-subtitle">Find exactly what you're looking for</p>
    </div>
    <a href="#productsSection" class="sec-link">View All →</a>
  </div>
  <div class="cats-grid">
    <?php
    $icons = ['Electronics'=>'fas fa-microchip','Clothing'=>'fas fa-shirt','Footwear'=>'fas fa-shoe-prints','Accessories'=>'fas fa-gem','Home & Kitchen'=>'fas fa-house','Sports'=>'fas fa-dumbbell','Books'=>'fas fa-book','Beauty'=>'fas fa-sparkles','Other'=>'fas fa-grid-2'];
    foreach($categories as $cat):
      $icon = $icons[$cat['category']] ?? 'fas fa-tag';
    ?>
    <a href="#productsSection" class="cat-card" onclick="filterProducts('<?= htmlspecialchars($cat['category']) ?>', document.querySelector('[data-cat=\'<?= htmlspecialchars($cat['category']) ?>\']'))">
      <div class="cat-icon"><i class="<?= $icon ?>"></i></div>
      <div class="cat-name"><?= htmlspecialchars($cat['category']) ?></div>
      <div class="cat-count"><?= $cat['cnt'] ?> items</div>
    </a>
    <?php endforeach; ?>
    <?php if(empty($categories)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted)">No categories yet.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ===== FEATURED ===== -->
<?php if(!empty($featured)): ?>
<section class="section section-dark">
  <div class="sec-head">
    <div>
      <h2 class="sec-title">⭐ <span>Featured</span> Products</h2>
      <p class="sec-subtitle">Hand-picked top selections</p>
    </div>
  </div>
  <div class="products-grid">
    <?php foreach(array_slice($featured, 0, 4) as $p): ?>
    <div class="product-card" data-category="<?= htmlspecialchars($p['category']) ?>">
      <div class="product-img-wrap">
        <?php if($p['image']): ?>
        <img src="<?= htmlspecialchars($p['image']) ?>" class="product-img"
             onerror="this.src='https://via.placeholder.com/400x300/1a1a1a/666?text=No+Image'" alt="<?= htmlspecialchars($p['name']) ?>">
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted)"><i class="fas fa-image" style="font-size:2rem"></i></div>
        <?php endif; ?>
        <div class="product-overlay">
          <button class="overlay-btn" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['sale_price'] ?? $p['price'] ?>, '<?= addslashes($p['image']??'') ?>')">
            <i class="fas fa-bag-shopping"></i> Add to Cart
          </button>
        </div>
        <span class="product-badge">⭐ Featured</span>
        <?php if($p['sale_price']): ?><span class="product-badge sale" style="top:10px;left:auto;right:10px">SALE</span><?php endif; ?>
      </div>
      <div class="product-body">
        <div class="product-cat"><?= htmlspecialchars($p['category']) ?></div>
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="product-desc"><?= htmlspecialchars(substr($p['description']??'',0,70)) ?><?= strlen($p['description']??'')>70?'...':'' ?></div>
        <div class="product-footer">
          <div class="product-price">
            <div class="price-main">Rs. <?= number_format($p['sale_price'] ?? $p['price']) ?></div>
            <?php if($p['sale_price']): ?><div class="price-old">Rs. <?= number_format($p['price']) ?></div><?php endif; ?>
          </div>
          <button class="add-btn" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['sale_price'] ?? $p['price'] ?>, '<?= addslashes($p['image']??'') ?>')">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="stock-badge">
          <span class="stock-dot <?= $p['stock']===0?'out':($p['stock']<10?'low':'') ?>"></span>
          <?= $p['stock']===0 ? 'Out of stock' : ($p['stock']<10 ? "Only {$p['stock']} left" : 'In stock') ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== ALL PRODUCTS ===== -->
<section class="section" id="productsSection">
  <div class="sec-head">
    <div>
      <h2 class="sec-title">All <span>Products</span></h2>
      <p class="sec-subtitle"><?= count($allProducts) ?> products available</p>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <button class="filter-chip active" onclick="filterProducts('all',this)">All</button>
    <?php foreach($categories as $cat): ?>
    <button class="filter-chip" data-cat="<?= htmlspecialchars($cat['category']) ?>"
            onclick="filterProducts('<?= htmlspecialchars($cat['category']) ?>',this)">
      <?= htmlspecialchars($cat['category']) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- PRODUCTS GRID -->
  <div class="products-grid" id="productsGrid">
    <?php if(empty($allProducts)): ?>
    <div class="empty-products" style="grid-column:1/-1">
      <i class="fas fa-box-open"></i>
      <p>No products available yet.<br>Check back soon!</p>
    </div>
    <?php else: ?>
    <?php foreach($allProducts as $p): ?>
    <div class="product-card" data-category="<?= htmlspecialchars($p['category']) ?>">
      <div class="product-img-wrap">
        <?php if($p['image']): ?>
        <img src="<?= htmlspecialchars($p['image']) ?>" class="product-img"
             onerror="this.src='https://via.placeholder.com/400x300/1a1a1a/666?text=No+Image'" alt="<?= htmlspecialchars($p['name']) ?>">
        <?php else: ?>
        <div style="width:100%;height:220px;display:flex;align-items:center;justify-content:center;color:var(--muted);background:var(--card2)"><i class="fas fa-image" style="font-size:2.5rem"></i></div>
        <?php endif; ?>
        <div class="product-overlay">
          <button class="overlay-btn" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['sale_price'] ?? $p['price'] ?>, '<?= addslashes($p['image']??'') ?>')">
            <i class="fas fa-bag-shopping"></i> Add to Cart
          </button>
        </div>
        <?php if($p['featured']): ?><span class="product-badge">⭐ Featured</span><?php endif; ?>
        <?php if($p['sale_price']): ?><span class="product-badge sale" style="<?= $p['featured']?'top:10px;left:auto;right:10px':'' ?>">SALE</span><?php endif; ?>
      </div>
      <div class="product-body">
        <div class="product-cat"><?= htmlspecialchars($p['category']) ?></div>
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="product-desc"><?= htmlspecialchars(substr($p['description']??'',0,70)) ?><?= strlen($p['description']??'')>70?'...':'' ?></div>
        <div class="product-footer">
          <div class="product-price">
            <div class="price-main">Rs. <?= number_format($p['sale_price'] ?? $p['price']) ?></div>
            <?php if($p['sale_price']): ?><div class="price-old">Rs. <?= number_format($p['price']) ?></div><?php endif; ?>
          </div>
          <button class="add-btn" title="Add to cart"
            onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['sale_price'] ?? $p['price'] ?>, '<?= addslashes($p['image']??'') ?>')">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="stock-badge">
          <span class="stock-dot <?= $p['stock']===0?'out':($p['stock']<10?'low':'') ?>"></span>
          <?= $p['stock']===0 ? 'Out of stock' : ($p['stock']<10 ? "Only {$p['stock']} left" : 'In stock') ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ===== ABOUT STRIP ===== -->
<section class="section section-dark" id="about">
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;text-align:center">
    <?php $perks = [['fas fa-truck','Fast Delivery','Nationwide delivery in 48 hours'],['fas fa-shield-halved','Secure Payment','100% safe & encrypted payments'],['fas fa-rotate-left','Easy Returns','7-day hassle-free returns'],['fas fa-headset','24/7 Support','Always here to help you']]; ?>
    <?php foreach($perks as [$ic,$title,$desc]): ?>
    <div>
      <div style="width:52px;height:52px;background:var(--primary-light);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.2rem;color:var(--primary)"><i class="<?= $ic ?>"></i></div>
      <div style="font-weight:700;margin-bottom:.3rem"><?= $title ?></div>
      <div style="font-size:.82rem;color:var(--muted)"><?= $desc ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
  <div class="footer-brand">SH <span>Market</span></div>
  <p>Pakistan's trusted online marketplace 🇵🇰</p>
  <p style="margin-top:.8rem">Logged in as: <strong style="color:var(--primary)"><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
    — <a href="../logout.php" style="color:var(--muted);font-size:.85rem" onclick="return confirm('Logout?')">Sign Out</a>
    <?php if($_SESSION['role']==='admin'): ?>
    | <a href="../admin/index.php" style="color:var(--primary);font-size:.85rem">Admin Panel</a>
    <?php endif; ?>
  </p>
  <p style="margin-top:1rem;color:var(--muted);font-size:.78rem">© <?= date('Y') ?> SH Market. All rights reserved.</p>
</footer>

<!-- ===== CART DRAWER ===== -->
<div class="cart-backdrop" id="cartBackdrop"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-hd">
    <h3><i class="fas fa-bag-shopping" style="color:var(--primary)"></i> Your Cart</h3>
    <button class="cart-close" onclick="toggleCart()"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="cart-items" id="cartItems"></div>
  <div class="cart-ft">
    <div class="cart-subtotal">
      <span>Subtotal</span>
      <span id="cartSubtotal">Rs. 0</span>
    </div>
    <div class="cart-total-row">
      <span>Total</span>
      <span id="cartTotal">Rs. 0</span>
    </div>
    <button class="checkout-btn" onclick="checkout()">
      <i class="fas fa-arrow-right"></i> Place Order
    </button>
  </div>
</div>

<!-- ===== TOAST ===== -->
<div class="toast" id="toast">
  <span class="toast-dot"></span>
  <span></span>
</div>

<script src="js/user.js"></script>
</body>
</html>
