<?php
// Shared admin sidebar
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fas fa-store"></i></div>
    <div>
      <div class="brand-name">SH <span>Market</span></div>
      <div class="brand-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <div class="menu-label">Overview</div>
    <a href="index.php" class="menu-item <?= $currentPage==='index.php'?'active':'' ?>">
      <i class="fas fa-chart-pie"></i> Dashboard
    </a>

    <div class="menu-label" style="margin-top:.8rem">Products</div>
    <a href="products.php" class="menu-item <?= $currentPage==='products.php'?'active':'' ?>">
      <i class="fas fa-box"></i> All Products
    </a>
    <a href="products.php?action=add" class="menu-item <?= ($currentPage==='products.php'&&($_GET['action']??'')==='add')?'active':'' ?>">
      <i class="fas fa-plus-circle"></i> Add Product
    </a>

    <div class="menu-label" style="margin-top:.8rem">Users</div>
    <a href="users.php" class="menu-item <?= $currentPage==='users.php'?'active':'' ?>">
      <i class="fas fa-users"></i> All Users
    </a>
    <a href="users.php?action=add" class="menu-item <?= ($currentPage==='users.php'&&($_GET['action']??'')==='add')?'active':'' ?>">
      <i class="fas fa-user-plus"></i> Add User
    </a>

    <div class="menu-label" style="margin-top:.8rem">Store</div>
    <a href="../user/index.php" class="menu-item" target="_blank">
      <i class="fas fa-store"></i> View Store
    </a>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
    <div class="user-info">
      <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
      <div class="role">Administrator</div>
    </div>
    <div class="sidebar-user-actions">
      <a href="../logout.php" class="icon-btn" title="Logout" onclick="return confirm('Are you sure you want to logout?')">
        <i class="fas fa-right-from-bracket"></i>
      </a>
    </div>
  </div>
</aside>
