<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}
require_once '../db.php';

$msg = ''; $msgType = '';

// ===== ADD PRODUCT =====
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_action']??'')==='add') {
    $name        = trim($_POST['name']??'');
    $description = trim($_POST['description']??'');
    $price       = floatval($_POST['price']??0);
    $sale_price  = $_POST['sale_price']!=='' ? floatval($_POST['sale_price']) : null;
    $category    = trim($_POST['category']??'');
    $stock       = intval($_POST['stock']??0);
    $image       = trim($_POST['image']??'');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $status      = $_POST['status']??'active';

    if (!$name || !$price || !$category) {
        $msg='Name, price, and category are required.'; $msgType='error';
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name,description,price,sale_price,category,stock,image,featured,status) VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssddsisis',$name,$description,$price,$sale_price,$category,$stock,$image,$featured,$status);
        if($stmt->execute()){ $msg='Product added successfully!'; $msgType='success'; }
        else { $msg='Failed to add product.'; $msgType='error'; }
    }
}

// ===== EDIT PRODUCT =====
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_action']??'')==='edit') {
    $id          = intval($_POST['product_id']??0);
    $name        = trim($_POST['name']??'');
    $description = trim($_POST['description']??'');
    $price       = floatval($_POST['price']??0);
    $sale_price  = $_POST['sale_price']!=='' ? floatval($_POST['sale_price']) : null;
    $category    = trim($_POST['category']??'');
    $stock       = intval($_POST['stock']??0);
    $image       = trim($_POST['image']??'');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $status      = $_POST['status']??'active';

    if (!$id || !$name || !$price) {
        $msg='Required fields missing.'; $msgType='error';
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,sale_price=?,category=?,stock=?,image=?,featured=?,status=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ssddsisisi',$name,$description,$price,$sale_price,$category,$stock,$image,$featured,$status,$id);
        if($stmt->execute()){ $msg='Product updated!'; $msgType='success'; }
        else { $msg='Update failed.'; $msgType='error'; }
    }
}

// ===== DELETE PRODUCT =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $msg='Product deleted.'; $msgType='success';
}

// ===== TOGGLE STATUS =====
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE products SET status=IF(status='active','inactive','active') WHERE id=$id");
    header('Location: products.php'); exit();
}

// ===== EDIT / VIEW PREFILL =====
$editProduct = null;
if (isset($_GET['edit'])) {
    $s=$conn->prepare("SELECT * FROM products WHERE id=?");
    $s->bind_param('i',intval($_GET['edit'])); $s->execute();
    $editProduct=$s->get_result()->fetch_assoc();
}
$viewProduct = null;
if (isset($_GET['view'])) {
    $s=$conn->prepare("SELECT * FROM products WHERE id=?");
    $s->bind_param('i',intval($_GET['view'])); $s->execute();
    $viewProduct=$s->get_result()->fetch_assoc();
}

// ===== ALL PRODUCTS =====
$filter = $_GET['filter']??'all';
$sql = "SELECT * FROM products";
if ($filter==='active') $sql.=" WHERE status='active'";
elseif ($filter==='inactive') $sql.=" WHERE status='inactive'";
elseif ($filter==='featured') $sql.=" WHERE featured=1";
$sql.=" ORDER BY created_at DESC";
$products = $conn->query($sql);

$totalAll    = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$totalActive = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
$totalFeatured = $conn->query("SELECT COUNT(*) as c FROM products WHERE featured=1")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — SH Market Admin</title>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.filter-tabs { display:flex; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.filter-tab {
  padding:.45rem 1rem; border-radius:8px; border:1px solid var(--border);
  background:var(--card); color:var(--muted2); font-size:.82rem; font-weight:600;
  text-decoration:none; transition:.2s; font-family:'Outfit',sans-serif; cursor:pointer;
}
.filter-tab:hover { border-color:var(--primary); color:var(--primary); }
.filter-tab.active { background:var(--primary); color:white; border-color:var(--primary); }
.featured-star { color:#f59e0b; }
.stock-low { color:#ef4444; font-weight:600; }
.img-preview { width:100%; max-height:160px; object-fit:cover; border-radius:10px; margin-top:.5rem; display:none; border:1px solid var(--border); }
.checkbox-row { display:flex; align-items:center; gap:.6rem; font-size:.9rem; cursor:pointer; }
.checkbox-row input[type=checkbox] { width:16px; height:16px; accent-color:var(--primary); cursor:pointer; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
      <div>
        <div class="page-title">Product Management</div>
        <div class="breadcrumb">Admin / <span>Products</span></div>
      </div>
    </div>
    <div class="topbar-right">
      <button class="topbar-btn" onclick="openModal('addProductModal')"><i class="fas fa-plus"></i> Add Product</button>
      <a href="../logout.php" class="topbar-btn danger" onclick="return confirm('Logout?')"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <div class="content">
    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>" data-autohide>
        <i class="fas fa-<?= $msgType==='success'?'check-circle':'circle-exclamation' ?>"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
      <a href="products.php" class="filter-tab <?= $filter==='all'?'active':'' ?>">All <span style="opacity:.6">(<?= $totalAll ?>)</span></a>
      <a href="products.php?filter=active" class="filter-tab <?= $filter==='active'?'active':'' ?>">Active <span style="opacity:.6">(<?= $totalActive ?>)</span></a>
      <a href="products.php?filter=inactive" class="filter-tab <?= $filter==='inactive'?'active':'' ?>">Inactive <span style="opacity:.6">(<?= $totalAll-$totalActive ?>)</span></a>
      <a href="products.php?filter=featured" class="filter-tab <?= $filter==='featured'?'active':'' ?>">⭐ Featured <span style="opacity:.6">(<?= $totalFeatured ?>)</span></a>
    </div>

    <div class="table-card">
      <div class="table-head">
        <div class="table-title"><i class="fas fa-box" style="color:var(--primary)"></i> Products</div>
        <div class="table-actions">
          <input type="text" class="search-box" id="prodSearch" placeholder="🔍 Search products...">
          <button class="btn btn-primary btn-sm" onclick="openModal('addProductModal')">
            <i class="fas fa-plus"></i> Add Product
          </button>
        </div>
      </div>
      <div class="tbl-wrap">
        <table id="productsTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Featured</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i=1;
            while($p = $products->fetch_assoc()):
            ?>
            <tr>
              <td style="color:var(--muted)"><?= $i++ ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.8rem">
                  <?php if($p['image']): ?>
                  <img src="<?= htmlspecialchars($p['image']) ?>" class="product-thumb"
                       onerror="this.src='https://via.placeholder.com/42x42/1a1a1a/666?text=?'">
                  <?php else: ?>
                  <div style="width:42px;height:42px;background:var(--card2);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--muted)"><i class="fas fa-image"></i></div>
                  <?php endif; ?>
                  <div>
                    <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if($p['sale_price']): ?>
                    <div style="font-size:.72rem;color:var(--success)">Sale: Rs. <?= number_format($p['sale_price']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($p['category']) ?></span></td>
              <td style="font-weight:600">Rs. <?= number_format($p['price']) ?></td>
              <td class="<?= $p['stock']<10?'stock-low':'' ?>"><?= $p['stock'] ?></td>
              <td><?= $p['featured']?'<i class="fas fa-star featured-star"></i>':'<i class="fas fa-star" style="color:var(--border)"></i>' ?></td>
              <td>
                <a href="?toggle=<?= $p['id'] ?>" title="Click to toggle">
                  <span class="badge badge-<?= $p['status']==='active'?'green':'red' ?>"><?= ucfirst($p['status']) ?></span>
                </a>
              </td>
              <td>
                <a href="?view=<?= $p['id'] ?>" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" title="Delete"
                   onclick="return confirm('Delete &quot;<?= htmlspecialchars($p['name']) ?>&quot;? Cannot be undone.')">
                   <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- ADD PRODUCT MODAL -->
<div class="modal-overlay" id="addProductModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Add New Product</h3>
      <button class="modal-close" onclick="closeModal('addProductModal')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="form_action" value="add">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" placeholder="e.g. Samsung Galaxy S24" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Price (Rs.) *</label>
            <input type="number" name="price" placeholder="e.g. 85000" min="1" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Sale Price (Rs.) <small style="color:var(--muted)">(optional)</small></label>
            <input type="number" name="sale_price" placeholder="e.g. 79000" min="0" step="0.01">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
              <option value="">-- Select --</option>
              <option>Electronics</option>
              <option>Clothing</option>
              <option>Footwear</option>
              <option>Accessories</option>
              <option>Home & Kitchen</option>
              <option>Sports</option>
              <option>Books</option>
              <option>Beauty</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock" placeholder="0" min="0" value="0">
          </div>
        </div>
        <div class="form-group">
          <label>Image URL</label>
          <input type="url" name="image" id="addImageUrl" placeholder="https://example.com/image.jpg">
          <img id="addImagePreview" class="img-preview" alt="Preview">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" placeholder="Product description..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.3rem">
            <label class="checkbox-row">
              <input type="checkbox" name="featured" value="1">
              ⭐ Mark as Featured
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addProductModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<?php if ($editProduct): ?>
<div class="modal-overlay" id="editProductModal" style="display:flex">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3><i class="fas fa-pen" style="color:var(--warning)"></i> Edit Product</h3>
      <a href="products.php" class="modal-close"><i class="fas fa-xmark"></i></a>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="form_action" value="edit">
        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Price (Rs.) *</label>
            <input type="number" name="price" value="<?= $editProduct['price'] ?>" min="1" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Sale Price (Rs.)</label>
            <input type="number" name="sale_price" value="<?= $editProduct['sale_price']??'' ?>" min="0" step="0.01">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
              <?php foreach(['Electronics','Clothing','Footwear','Accessories','Home & Kitchen','Sports','Books','Beauty','Other'] as $cat): ?>
              <option <?= $editProduct['category']===$cat?'selected':'' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" value="<?= $editProduct['stock'] ?>" min="0">
          </div>
        </div>
        <div class="form-group">
          <label>Image URL</label>
          <input type="url" name="image" id="editImageUrl" value="<?= htmlspecialchars($editProduct['image']??'') ?>" placeholder="https://...">
          <img id="editImagePreview" class="img-preview" src="<?= htmlspecialchars($editProduct['image']??'') ?>"
               style="<?= $editProduct['image']?'display:block':'' ?>" alt="Preview">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description"><?= htmlspecialchars($editProduct['description']??'') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= $editProduct['status']==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= $editProduct['status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.3rem">
            <label class="checkbox-row">
              <input type="checkbox" name="featured" value="1" <?= $editProduct['featured']?'checked':'' ?>>
              ⭐ Featured Product
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="products.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- VIEW PRODUCT MODAL -->
<?php if ($viewProduct): ?>
<div class="modal-overlay" id="viewProductModal" style="display:flex">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h3><i class="fas fa-box" style="color:var(--primary)"></i> Product Details</h3>
      <a href="products.php" class="modal-close"><i class="fas fa-xmark"></i></a>
    </div>
    <div class="modal-body">
      <?php if($viewProduct['image']): ?>
      <img src="<?= htmlspecialchars($viewProduct['image']) ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;margin-bottom:1.2rem" onerror="this.style.display='none'">
      <?php endif; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.8rem">
        <h3 style="font-size:1.1rem"><?= htmlspecialchars($viewProduct['name']) ?></h3>
        <?php if($viewProduct['featured']): ?><span style="color:#f59e0b;font-size:.8rem">⭐ Featured</span><?php endif; ?>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:1rem">
        <div style="background:var(--card2);padding:.8rem;border-radius:10px;text-align:center">
          <div style="font-size:.7rem;color:var(--muted);margin-bottom:.2rem">PRICE</div>
          <div style="font-weight:800;color:var(--primary)">Rs. <?= number_format($viewProduct['price']) ?></div>
          <?php if($viewProduct['sale_price']): ?><div style="font-size:.75rem;color:var(--success)">Sale: Rs. <?= number_format($viewProduct['sale_price']) ?></div><?php endif; ?>
        </div>
        <div style="background:var(--card2);padding:.8rem;border-radius:10px;text-align:center">
          <div style="font-size:.7rem;color:var(--muted);margin-bottom:.2rem">STOCK</div>
          <div style="font-weight:800;color:<?= $viewProduct['stock']<10?'var(--danger)':'var(--success)' ?>"><?= $viewProduct['stock'] ?></div>
        </div>
      </div>
      <?php foreach([['Category',$viewProduct['category'],'fas fa-tag'],['Status',ucfirst($viewProduct['status']),'fas fa-circle'],['Added',date('d M Y', strtotime($viewProduct['created_at'])),'fas fa-calendar']] as [$l,$v,$ic]): ?>
      <div style="display:flex;align-items:center;gap:.8rem;padding:.6rem 0;border-bottom:1px solid var(--border)">
        <i class="<?= $ic ?>" style="color:var(--primary);width:16px"></i>
        <span style="font-size:.8rem;color:var(--muted);width:70px"><?= $l ?></span>
        <span style="font-size:.88rem"><?= htmlspecialchars($v) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if($viewProduct['description']): ?>
      <div style="margin-top:1rem;padding:1rem;background:var(--card2);border-radius:10px;font-size:.85rem;color:var(--muted2);line-height:1.6">
        <?= htmlspecialchars($viewProduct['description']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="modal-footer">
      <a href="products.php" class="btn btn-outline">Close</a>
      <a href="?edit=<?= $viewProduct['id'] ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Edit</a>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="js/admin.js"></script>
<script>
  tableSearch('prodSearch','productsTable');
  previewImage('addImageUrl','addImagePreview');
  previewImage('editImageUrl','editImagePreview');
</script>
</body>
</html>
