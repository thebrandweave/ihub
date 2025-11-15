<?php
// Connect to database and check customer authentication
require_once __DIR__ . '/auth/customer_auth.php';

// Fetch categories
$categories = [];
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC LIMIT 4");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Fetch trending products (active products with stock)
$trendingProducts = [];
try {
    $trendStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'active' AND p.stock > 0
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $trendingProducts = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trendingProducts = [];
}

// Fetch popular products (active products)
$popularProducts = [];
try {
    $popStmt = $pdo->query("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $popularProducts = $popStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popularProducts = [];
}

// Helper function to get product image URL
function getProductImage($product, $basePath = 'uploads/products/') {
    if (!empty($product['primary_image'])) {
        return $basePath . $product['primary_image'];
    }
    if (!empty($product['thumbnail'])) {
        return $basePath . $product['thumbnail'];
    }
    return 'https://via.placeholder.com/300';
}

// Helper function to calculate price with discount
function getFinalPrice($price, $discount) {
    if ($discount > 0) {
        return $price - ($price * $discount / 100);
    }
    return $price;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aceno Camping — Bootstrap Landing</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body{font-family:Arial, sans-serif; color:#0f172a;}
    .hero{padding:80px 0;}
    .product-card img{height:150px;object-fit:cover;border-radius:10px;}
    .card img{height:240px;object-fit:cover;}
    .newsletter{background:linear-gradient(90deg,#0ea5a3,#06b6d4);padding:40px;color:white;border-radius:12px;}

    /* ========= MOBILE NAVBAR ========== */
    @media (max-width: 768px) {

      /* Hide desktop navbar parts on mobile */
      .desktop-nav,
      .bottom-nav,
      .top-info-bar {
        display: none !important;
      }

      /* MOBILE HEADER */
      .mobile-header {
        background: #283b8f;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }

      .mobile-logo {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
      }

      .mh-icon {
        color: #fff;
        font-size: 24px;
        cursor: pointer;
      }

      /* MOBILE SEARCH */
      .mobile-search {
        background: #1a2b6d;
        padding: 12px;
      }

      /* MOBILE OFFCANVAS MENU */
      .offcanvas-custom {
        width: 280px;
        background: #ffffff;
      }

      .offcanvas-header {
        background:#283b8f;
        color:#fff;
      }

      .menu-item {
        font-size:18px;
        padding:12px 0;
        border-bottom:1px solid #f2f2f2;
        cursor:pointer;
      }
    }
  </style>
</head>
<body>

<!-- ===========================
     📱 MOBILE NAVBAR
=========================== -->
<div class="mobile-header d-md-none">

  <!-- Hamburger -->
  <i class="bi bi-list mh-icon" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"></i>

  <!-- Logo -->
  <span class="mobile-logo">Ucham</span>

  <!-- Cart -->
  <div class="position-relative">
    <i class="bi bi-bag mh-icon"></i>
    <span class="badge bg-light text-dark position-absolute top-0 start-100 translate-middle p-1"><?= $cart_count ?></span>
  </div>
</div>

<!-- Mobile Search -->
<div class="mobile-search d-md-none">
  <div class="input-group">
    <input type="text" class="form-control" placeholder="Enter keywords to search...">
    <button class="btn btn-dark">SEARCH</button>
  </div>
</div>

<!-- Mobile Menu Drawer -->
<div class="offcanvas offcanvas-start offcanvas-custom" id="mobileMenu">
  <div class="offcanvas-header">
    <h5 class="mb-0">MENU</h5>
    <button class="btn text-white" data-bs-dismiss="offcanvas">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="offcanvas-body">

    <div class="menu-item d-flex justify-content-between">
      <span>Home</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="menu-item d-flex justify-content-between">
      <span>Shop</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="menu-item d-flex justify-content-between">
      <span>Product</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="menu-item d-flex justify-content-between">
      <span>Collection</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="menu-item d-flex justify-content-between">
      <span>Blog</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="menu-item d-flex justify-content-between">
      <span>Pages</span><i class="bi bi-chevron-right"></i>
    </div>

    <div class="mt-4">
      <?php if ($customer_logged_in): ?>
        <div class="menu-item">
          <i class="bi bi-person me-2"></i> <?= htmlspecialchars($customer_name ?? 'Customer') ?>
        </div>
        <div class="menu-item">
          <a href="auth/customer_logout.php" class="text-decoration-none text-dark"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
      <?php else: ?>
        <div class="menu-item" data-bs-toggle="modal" data-bs-target="#loginModal" style="cursor:pointer;">
          <i class="bi bi-person me-2"></i> Sign in/Register
        </div>
      <?php endif; ?>
      <div class="menu-item">
        <i class="bi bi-heart me-2"></i> Wishlist <span class="badge bg-primary"><?= $wishlist_count ?></span>
      </div>
    </div>

    <div class="mt-auto pt-4">
      <div class="d-flex justify-content-between">
        <span>Currency</span>
        <select class="border-0 bg-transparent">
          <option>$ - USD</option>
        </select>
      </div>

      <div class="d-flex justify-content-between mt-2">
        <span>Language</span>
        <select class="border-0 bg-transparent">
          <option>English</option>
        </select>
      </div>
    </div>

  </div>
</div>

<!-- Navbar -->
<nav class="container-fluid p-0 desktop-nav">

  <!-- TOP BAR -->
  <div class="w-100 py-1 text-white top-info-bar" style="background:#1a2b6d;font-size:14px;">
    <div class="container d-flex justify-content-between">
      <span>Free standard shipping for orders <strong>over $340.</strong></span>
      <div class="d-flex gap-3">
        <a href="#" class="text-white text-decoration-none">Store Locator</a>
        <a href="#" class="text-white text-decoration-none">Order Tracking</a>
        <a href="#" class="text-white text-decoration-none">FAQs</a>
        <select class="bg-transparent text-white border-0">
          <option>$ - USD</option>
        </select>
        <select class="bg-transparent text-white border-0">
          <option>English</option>
        </select>
      </div>
    </div>
  </div>

  <!-- MIDDLE BAR -->
  <div class="w-100 py-3" style="background:#283b8f;">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">

      <a class="navbar-brand fw-bold text-white fs-3" href="#">Ucham</a>

      <div class="d-flex flex-grow-1 mx-4" style="max-width:700px;">
        <input type="text" class="form-control rounded-0" placeholder="Enter keywords to search...">
        <button class="btn btn-dark rounded-0 px-4">SEARCH</button>
      </div>

      <div class="d-flex gap-4 text-white align-items-center">
        <?php if ($customer_logged_in): ?>
          <div class="text-center" style="cursor:pointer;" data-bs-toggle="dropdown">
            <i class="bi bi-person fs-4"></i>
            <small class="d-block"><?= htmlspecialchars($customer_name ?? 'Customer') ?></small>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="auth/customer_logout.php">Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <div class="text-center" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-person fs-4"></i>
            <small class="d-block">Account</small>
          </div>
        <?php endif; ?>
        <div class="text-center" style="cursor:pointer;">
          <i class="bi bi-heart fs-4"></i><span class="badge bg-light text-dark ms-1"><?= $wishlist_count ?></span>
          <small class="d-block">Wishlist</small>
        </div>
        <div class="text-center" style="cursor:pointer;">
          <i class="bi bi-bag fs-4"></i><span class="badge bg-light text-dark ms-1"><?= $cart_count ?></span>
          <small class="d-block">My cart</small>
        </div>
      </div>

    </div>
  </div>

  <!-- BOTTOM NAV -->
  <div class="bg-white border-top shadow-sm bottom-nav">
    <div class="container d-flex flex-wrap align-items-center py-2 gap-4">
      <div class="dropdown">
        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-grid"></i> All Departments
        </button>
        <ul class="dropdown-menu">
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <li><a class="dropdown-item" href="#"><?= htmlspecialchars($cat['name']) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a class="dropdown-item" href="#">Camping</a></li>
            <li><a class="dropdown-item" href="#">Hiking</a></li>
            <li><a class="dropdown-item" href="#">Accessories</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <ul class="nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="shop.php">Shop</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="product.php">Product</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="collection.php">Collection</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="blog.php">Blog</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link" href="pages.php">Pages</a>
        </li>
      </ul>

      <div class="ms-auto d-flex align-items-center gap-4">
        <span>Get our theme now!</span>
        <span><i class="bi bi-envelope"></i> ucham@email.com</span>
        <span><i class="bi bi-telephone"></i> +222-1800-2628</span>
      </div>
    </div>
  </div>

</nav>

<!-- Hero Section -->
<section class="hero container-fluid bg-light">
  <div class="container py-5">
    <div class="row align-items-center">

      <div class="col-lg-6 mb-4">
        <h1 class="fw-bold display-5">Gear up for adventure — built for every journey</h1>
        <p class="text-secondary mt-3">Lightweight tents, thermal sleeping bags, portable stoves & more. Free shipping over ₹2,499.</p>

        <div class="d-flex gap-3 mt-4">
          <a class="btn btn-primary px-4" href="#products">Shop Bestsellers</a>
          <a class="btn btn-outline-secondary px-4" href="#features">Learn More</a>
        </div>

        <div class="row mt-4 g-2">
          <div class="col-4"><div class="p-2 bg-white shadow-sm rounded text-center">Waterproof</div></div>
          <div class="col-4"><div class="p-2 bg-white shadow-sm rounded text-center">Lightweight</div></div>
          <div class="col-4"><div class="p-2 bg-white shadow-sm rounded text-center">2-Year Warranty</div></div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="bg-white p-3 rounded shadow-sm mb-3 d-flex align-items-center">
          <img src="https://via.placeholder.com/150" class="me-3" alt="Tent">
          <div class="flex-grow-1">
            <h6 class="fw-bold mb-0">Summit 3P Tent</h6>
            <small class="text-secondary">3-person, quick-pitch</small>
          </div>
          <button class="btn btn-primary btn-sm">Add</button>
        </div>

        <div class="bg-white p-3 rounded shadow-sm d-flex align-items-center">
          <img src="https://via.placeholder.com/150" class="me-3" alt="Sleeping bag">
          <div class="flex-grow-1">
            <h6 class="fw-bold mb-0">Aurora Thermal Bag</h6>
            <small class="text-secondary">Comfort to -5°C</small>
          </div>
          <button class="btn btn-primary btn-sm">Add</button>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Top Categories -->
<section class="py-5 container-fluid bg-white">
  <div class="container">
    <h2 class="fw-bold mb-4">Top Categories</h2>
    <div class="row g-4">
      <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $cat): ?>
          <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded text-center shadow-sm">
              <img src="https://via.placeholder.com/150" class="w-50 mb-2">
              <h6 class="fw-bold"><?= htmlspecialchars($cat['name']) ?></h6>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded text-center shadow-sm">
            <img src="https://via.placeholder.com/150" class="w-50 mb-2">
            <h6 class="fw-bold">Smartphones</h6>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded text-center shadow-sm">
            <img src="https://via.placeholder.com/150" class="w-50 mb-2">
            <h6 class="fw-bold">Laptops</h6>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded text-center shadow-sm">
            <img src="https://via.placeholder.com/150" class="w-50 mb-2">
            <h6 class="fw-bold">Smartwatches</h6>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded text-center shadow-sm">
            <img src="https://via.placeholder.com/150" class="w-50 mb-2">
            <h6 class="fw-bold">Audio Devices</h6>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Trending Now -->
<section class="py-5 container-fluid bg-light">
  <div class="container">
    <h2 class="fw-bold mb-4">Trending Electronics</h2>
    <div class="row g-4">
      <?php if (!empty($trendingProducts)): ?>
        <?php foreach ($trendingProducts as $product): ?>
          <div class="col-md-3">
            <div class="p-3 bg-white rounded shadow-sm">
              <img src="<?= htmlspecialchars(getProductImage($product)) ?>" class="w-100 rounded mb-2" style="height:160px; object-fit:cover;" alt="<?= htmlspecialchars($product['name']) ?>">
              <h6 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h6>
              <?php 
                $finalPrice = getFinalPrice($product['price'], $product['discount'] ?? 0);
              ?>
              <p class="text-danger fw-bold mb-1">₹<?= number_format($finalPrice, 2) ?>
                <?php if ($product['discount'] > 0): ?>
                  <span class="text-secondary text-decoration-line-through ms-2">₹<?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Fallback placeholder cards -->
        <div class="col-md-3">
          <div class="p-3 bg-white rounded shadow-sm">
            <img src="https://via.placeholder.com/300" class="w-100 rounded mb-2" style="height:160px; object-fit:cover;">
            <h6 class="fw-bold">iPhone 15 Pro</h6>
            <p class="text-danger fw-bold mb-1">$999</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 bg-white rounded shadow-sm">
            <img src="https://via.placeholder.com/300" class="w-100 rounded mb-2" style="height:160px; object-fit:cover;">
            <h6 class="fw-bold">Samsung S24 Ultra</h6>
            <p class="text-danger fw-bold mb-1">$1199</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 bg-white rounded shadow-sm">
            <img src="https://via.placeholder.com/300" class="w-100 rounded mb-2" style="height:160px; object-fit:cover;">
            <h6 class="fw-bold">MacBook Pro M3</h6>
            <p class="text-danger fw-bold mb-1">$1899</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 bg-white rounded shadow-sm">
            <img src="https://via.placeholder.com/300" class="w-100 rounded mb-2" style="height:160px; object-fit:cover;">
            <h6 class="fw-bold">Sony WH-1000XM5</h6>
            <p class="text-danger fw-bold mb-1">$349</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Advertisement Banner -->
<section class="container-fluid py-4">
  <div class="container">
    <div class="rounded overflow-hidden shadow-sm">
      <img src="https://via.placeholder.com/1200x300" class="w-100 rounded" style="object-fit:cover;">
    </div>
  </div>
</section>

<!-- Products -->
<section id="products" class="py-5 container-fluid">
  <div class="container">
    <div class="d-flex justify-content-between mb-4">
      <h2 class="fw-bold">Popular Products</h2>
      <span class="text-secondary">Showing <?= count($popularProducts) ?> results</span>
    </div>

    <div class="row g-4">
      <?php if (!empty($popularProducts)): ?>
        <?php foreach ($popularProducts as $product): ?>
          <div class="col-md-4">
            <div class="p-3 bg-white rounded shadow-sm border-0" style="border-radius:12px; position:relative;">
              <?php if ($product['discount'] > 0): ?>
                <!-- Discount Badge -->
                <span class="badge text-white position-absolute" style="top:10px; left:10px; background:#ff4d4d; font-size:12px; padding:6px 10px; border-radius:6px;">-<?= number_format($product['discount'], 0) ?>%</span>
              <?php endif; ?>

              <!-- Product Image -->
              <img src="<?= htmlspecialchars(getProductImage($product)) ?>" class="w-100 rounded mb-3" style="object-fit:cover; height:220px;" alt="<?= htmlspecialchars($product['name']) ?>" />

              <!-- Vendor + Rating -->
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-primary"><?= htmlspecialchars($product['category_name'] ?? 'Electronics') ?></span>
                <span class="text-warning"><i class="bi bi-star-fill"></i> 0.00 (0)</span>
              </div>

              <!-- Product Title -->
              <h6 class="fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h6>

              <!-- Price -->
              <div class="mb-3">
                <?php 
                  $finalPrice = getFinalPrice($product['price'], $product['discount'] ?? 0);
                ?>
                <span class="fw-bold text-danger fs-5">₹<?= number_format($finalPrice, 2) ?></span>
                <?php if ($product['discount'] > 0): ?>
                  <span class="text-secondary text-decoration-line-through ms-2">₹<?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
              </div>

              <!-- Add to Cart Button -->
              <?php if ($customer_logged_in): ?>
                <button class="btn w-100 text-white add-to-cart-btn" data-product-id="<?= $product['product_id'] ?>" style="background:#283b8f; border-radius:8px; padding:10px 0;">Add to Cart</button>
              <?php else: ?>
                <button class="btn w-100 text-white" data-bs-toggle="modal" data-bs-target="#loginModal" style="background:#283b8f; border-radius:8px; padding:10px 0;">Add to Cart</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Fallback placeholder card -->
        <div class="col-md-4">
          <div class="p-3 bg-white rounded shadow-sm border-0" style="border-radius:12px; position:relative;">
            <span class="badge text-white position-absolute" style="top:10px; left:10px; background:#ff4d4d; font-size:12px; padding:6px 10px; border-radius:6px;">-6%</span>
            <img src="https://via.placeholder.com/600x400" class="w-100 rounded mb-3" style="object-fit:cover; height:220px;" />
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-primary">Aceno Vendor</span>
              <span class="text-warning"><i class="bi bi-star-fill"></i> 0.00 (0)</span>
            </div>
            <h6 class="fw-bold mb-2">Velatheme Demo Icon Pack</h6>
            <div class="mb-3">
              <span class="fw-bold text-danger fs-5">$188.00</span>
              <span class="text-secondary text-decoration-line-through ms-2">$200.00</span>
            </div>
            <button class="btn w-100 text-white" style="background:#283b8f; border-radius:8px; padding:10px 0;">Add to Cart</button>
          </div>
        </div>
      <?php endif; ?>
    </div>

<!-- Features -->
<section id="features" class="py-5 bg-light container-fluid">
  <div class="container">
    <h2 class="fw-bold mb-4">Why campers choose Aceno</h2>
    <div class="row g-3">
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Durable materials — lab tested</div></div>
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Sustainable packaging</div></div>
      <div class="col-md-4"><div class="p-4 bg-white shadow-sm rounded">Fast support & warranty</div></div>
    </div>
  </div>
</section>

<!-- Reviews -->
<section id="reviews" class="py-5 container-fluid">
  <div class="container">
    <h2 class="fw-bold mb-4">What customers say</h2>
    <div class="row g-3">
      <div class="col-md-6"><div class="p-4 bg-white shadow-sm rounded">“Tent survived monsoon rain. Light & super easy to set up.” — <strong>Ravi K.</strong></div></div>
      <div class="col-md-6"><div class="p-4 bg-white shadow-sm rounded">“Sleeping bag is warmer than expected. Quick delivery.” — <strong>Asha P.</strong></div></div>
    </div>
  </div>
</section>

<!-- Newsletter -->
<section class="py-5 container-fluid">
  <div class="container">
    <div class="newsletter d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
      <div>
        <h3 class="fw-bold">Join newsletter — 10% off first order</h3>
        <p class="mb-0">Exclusive deals & gear guides weekly.</p>
      </div>
      <form class="d-flex gap-2 flex-wrap">
        <input class="form-control" type="email" placeholder="Enter your email">
        <button class="btn btn-dark px-4">Subscribe</button>
      </form>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="container-fluid bg-white pt-5 pb-3">
  <div class="container">
    <div class="row g-5">

      <!-- Contact -->
      <div class="col-md-3">
        <h5 class="fw-bold mb-3">Contact</h5>
        <p class="mb-2"><i class="bi bi-geo-alt me-2"></i>16122 Collins street, Melbourne, Australia</p>
        <p class="mb-2"><i class="bi bi-telephone me-2"></i>(603) 555-0123</p>
        <p class="mb-3"><i class="bi bi-envelope me-2"></i>@example.com</p>

        <div class="d-flex gap-2 mt-3">
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-facebook"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-twitter"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-instagram"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-tiktok"></i></a>
          <a href="#" class="btn btn-outline-secondary rounded-circle p-2"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <!-- Shop -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">Shop</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2">Camping</li>
          <li class="mb-2">Climbing</li>
          <li class="mb-2">Accessories</li>
          <li class="mb-2">Clothing</li>
          <li class="mb-2">Running</li>
        </ul>
      </div>

      <!-- Info -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">Information</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2">Register</li>
          <li class="mb-2">Login</li>
          <li class="mb-2">My Cart</li>
          <li class="mb-2">My Order</li>
          <li class="mb-2">Wishlist</li>
        </ul>
      </div>

      <!-- About -->
      <div class="col-md-2">
        <h5 class="fw-bold mb-3">About</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2">Theme features</li>
          <li class="mb-2">Blog</li>
          <li class="mb-2">About</li>
          <li class="mb-2">FAQs</li>
          <li class="mb-2">Contact</li>
        </ul>
      </div>

      <!-- Services -->
      <div class="col-md-3">
        <h5 class="fw-bold mb-3">Services</h5>
        <ul class="list-unstyled text-secondary">
          <li class="mb-2">Order history</li>
          <li class="mb-2">Customer support</li>
          <li class="mb-2">Terms & conditions</li>
          <li class="mb-2">Returns & exchanges</li>
          <li class="mb-2">Shipping & delivery</li>
        </ul>
      </div>

    </div>

    <!-- Bottom Bar -->
    <hr class="my-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
      <p class="mb-0 text-secondary">Copyright © <span class="fw-bold text-danger">ACENO.</span> All rights reserved.</p>

      <div class="d-flex gap-3 align-items-center">
        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" height="26">
        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" height="24">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Google_Pay_Logo.svg" height="24">
        <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_2021.svg" height="24">
        <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" height="24">
      </div>
    </div>

    <!-- Scroll to top -->
    <a href="#" class="btn btn-primary rounded-circle position-fixed" style="right:20px; bottom:20px; width:48px; height:48px;">
      <i class="bi bi-arrow-up text-white"></i>
    </a>
  </div>
</footer>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Customer Login</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="loginError" class="alert alert-danger d-none" role="alert"></div>
        <form id="loginForm">
          <div class="mb-3">
            <label for="loginEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="loginEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label for="loginPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="loginPassword" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="mt-3 text-center">
          <p class="mb-0">Don't have an account? <a href="auth/signup.php">Sign up here</a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Handle login form submission
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('ajax', '1');
  
  const errorDiv = document.getElementById('loginError');
  errorDiv.classList.add('d-none');
  
  try {
    const response = await fetch('auth/customer_login.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Reload page to show logged in state
      window.location.reload();
    } else {
      errorDiv.textContent = data.error || 'Login failed';
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    errorDiv.textContent = 'An error occurred. Please try again.';
    errorDiv.classList.remove('d-none');
  }
});

// Handle add to cart (if logged in)
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const productId = this.getAttribute('data-product-id');
    // TODO: Implement add to cart functionality
    alert('Add to cart functionality will be implemented');
  });
});
</script>
</body>
</html>
