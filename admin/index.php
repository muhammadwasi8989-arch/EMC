<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../db.php';

// Stats
$totalProducts = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$activeProducts = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$activeUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND status='active'")->fetch_assoc()['c'];

// Recent products
$recentProducts = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");

// Recent users
$recentUsers = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SH Market Admin</title>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
      <div>
        <div class="page-title">Dashboard</div>
        <div class="breadcrumb">Welcome back, <span><?= htmlspecialchars($_SESSION['full_name']) ?></span></div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="../user/index.php" class="topbar-btn" target="_blank"><i class="fas fa-eye"></i> View Store</a>
      <a href="../logout.php" class="topbar-btn danger" onclick="return confirm('Logout?')"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <div class="content">
    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box-open"></i></div>
        <div class="stat-val"><?= $totalProducts ?></div>
        <div class="stat-label">Total Products</div>
        <div class="stat-change"><i class="fas fa-circle-check"></i> <?= $activeProducts ?> active</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-val"><?= $totalUsers ?></div>
        <div class="stat-label">Registered Users</div>
        <div class="stat-change"><i class="fas fa-circle-check"></i> <?= $activeUsers ?> active</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-layer-group"></i></div>
        <?php $cats = $conn->query("SELECT COUNT(DISTINCT category) as c FROM products")->fetch_assoc()['c']; ?>
        <div class="stat-val"><?= $cats ?></div>
        <div class="stat-label">Categories</div>
        <div class="stat-change">Unique categories</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warn"><i class="fas fa-star"></i></div>
        <?php $featured = $conn->query("SELECT COUNT(*) as c FROM products WHERE featured=1")->fetch_assoc()['c']; ?>
        <div class="stat-val"><?= $featured ?></div>
        <div class="stat-label">Featured Products</div>
        <div class="stat-change">Shown on homepage</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

      <!-- RECENT PRODUCTS -->
      <div class="table-card">
        <div class="table-head">
          <div class="table-title"><i class="fas fa-box" style="color:var(--primary)"></i> Recent Products</div>
          <a href="products.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>Product</th><th>Price</th><th>Status</th></tr></thead>
            <tbody>
              <?php while($p = $recentProducts->fetch_assoc()): ?>
              <tr>
                <td>
                  <img src="<?= htmlspecialchars($p['image']??'') ?>" class="product-thumb" 
                       onerror="this.style.display='none'" style="margin-right:.5rem">
                  <?= htmlspecialchars($p['name']) ?>
                </td>
                <td>Rs. <?= number_format($p['price']) ?></td>
                <td><span class="badge badge-<?= $p['status']==='active'?'green':'red' ?>"><?= ucfirst($p['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- RECENT USERS -->
      <div class="table-card">
        <div class="table-head">
          <div class="table-title"><i class="fas fa-users" style="color:var(--info)"></i> Recent Users</div>
          <a href="users.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead>
            <tbody>
              <?php while($u = $recentUsers->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['status']==='active'?'green':'red' ?>"><?= ucfirst($u['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>

<script src="js/admin.js"></script>
</body>
</html>
