<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}
require_once '../db.php';

$msg = ''; $msgType = '';

// ===== ADD USER =====
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_action']??'')==='add') {
    $full_name = trim($_POST['full_name']??'');
    $email     = trim($_POST['email']??'');
    $phone     = trim($_POST['phone']??'');
    $address   = trim($_POST['address']??'');
    $role      = $_POST['role']??'user';
    $status    = $_POST['status']??'active';
    $password  = $_POST['password']??'';

    if (!$full_name || !$email || !$password) {
        $msg = 'Full name, email, and password are required.'; $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email format.'; $msgType = 'error';
    } elseif (strlen($password) < 6) {
        $msg = 'Password must be at least 6 characters.'; $msgType = 'error';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param('s',$email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $msg = 'This email is already registered.'; $msgType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name,email,phone,address,password,role,status) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss',$full_name,$email,$phone,$address,$hash,$role,$status);
            if ($stmt->execute()) { $msg='User added successfully!'; $msgType='success'; }
            else { $msg='Database error. Try again.'; $msgType='error'; }
        }
    }
}

// ===== EDIT USER =====
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_action']??'')==='edit') {
    $id        = intval($_POST['user_id']??0);
    $full_name = trim($_POST['full_name']??'');
    $email     = trim($_POST['email']??'');
    $phone     = trim($_POST['phone']??'');
    $address   = trim($_POST['address']??'');
    $role      = $_POST['role']??'user';
    $status    = $_POST['status']??'active';
    $password  = $_POST['password']??'';

    if (!$id || !$full_name || !$email) {
        $msg='Required fields missing.'; $msgType='error';
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,password=?,role=?,status=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sssssssi',$full_name,$email,$phone,$address,$hash,$role,$status,$id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,role=?,status=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ssssssi',$full_name,$email,$phone,$address,$role,$status,$id);
        }
        if ($stmt->execute()) { $msg='User updated successfully!'; $msgType='success'; }
        else { $msg='Update failed.'; $msgType='error'; }
    }
}

// ===== DELETE USER =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id == $_SESSION['user_id']) {
        $msg='You cannot delete your own account.'; $msgType='error';
    } else {
        $conn->prepare("DELETE FROM users WHERE id=?")->bind_param('i',$id) && 
        $conn->prepare("DELETE FROM users WHERE id=?")->execute();
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i',$id); $stmt->execute();
        $msg='User deleted.'; $msgType='success';
    }
}

// ===== VIEW USER =====
$viewUser = null;
if (isset($_GET['view'])) {
    $vid = intval($_GET['view']);
    $vs = $conn->prepare("SELECT * FROM users WHERE id=?");
    $vs->bind_param('i',$vid); $vs->execute();
    $viewUser = $vs->get_result()->fetch_assoc();
}

// ===== EDIT PREFILL =====
$editUser = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $es = $conn->prepare("SELECT * FROM users WHERE id=?");
    $es->bind_param('i',$eid); $es->execute();
    $editUser = $es->get_result()->fetch_assoc();
}

// ===== FETCH ALL USERS =====
$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — SH Market Admin</title>
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
        <div class="page-title">User Management</div>
        <div class="breadcrumb">Admin / <span>Users</span></div>
      </div>
    </div>
    <div class="topbar-right">
      <button class="topbar-btn" onclick="openModal('addUserModal')"><i class="fas fa-user-plus"></i> Add User</button>
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

    <div class="table-card">
      <div class="table-head">
        <div class="table-title"><i class="fas fa-users" style="color:var(--info)"></i> All Users</div>
        <div class="table-actions">
          <input type="text" class="search-box" id="userSearch" placeholder="🔍 Search users...">
          <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">
            <i class="fas fa-plus"></i> Add User
          </button>
        </div>
      </div>
      <div class="tbl-wrap">
        <table id="usersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i=1;
            while($u = $users->fetch_assoc()):
            ?>
            <tr>
              <td style="color:var(--muted)"><?= $i++ ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.7rem">
                  <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--primary),#ff6b35);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">
                    <?= strtoupper(substr($u['full_name'],0,1)) ?>
                  </div>
                  <?= htmlspecialchars($u['full_name']) ?>
                </div>
              </td>
              <td style="color:var(--muted2);font-size:.85rem"><?= htmlspecialchars($u['email']) ?></td>
              <td style="color:var(--muted2);font-size:.85rem"><?= htmlspecialchars($u['phone']??'—') ?></td>
              <td><span class="badge badge-<?= $u['status']==='active'?'green':'red' ?>"><?= ucfirst($u['status']) ?></span></td>
              <td style="color:var(--muted);font-size:.82rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <a href="?view=<?= $u['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                <a href="?edit=<?= $u['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete <?= htmlspecialchars($u['full_name']) ?>? This cannot be undone.')">
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

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="addUserModal" <?= (($_GET['action']??'')==='add' || ($msgType==='error'&&!isset($_GET['edit'])))?'style="display:flex"':'' ?>>
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-user-plus" style="color:var(--primary)"></i> Add New User</h3>
      <button class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="form_action" value="add">
        <div class="form-row">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" placeholder="e.g. Ali Hassan" required>
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="user@email.com" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="03XX-XXXXXXX">
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" placeholder="Min 6 characters" required minlength="6">
          </div>
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" placeholder="Full address (optional)"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="user">Customer</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT USER MODAL -->
<?php if ($editUser): ?>
<div class="modal-overlay" id="editUserModal" style="display:flex">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-pen" style="color:var(--warning)"></i> Edit User</h3>
      <a href="users.php" class="modal-close"><i class="fas fa-xmark"></i></a>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="form_action" value="edit">
        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
        <div class="form-row">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($editUser['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($editUser['phone']??'') ?>">
          </div>
          <div class="form-group">
            <label>New Password <small style="color:var(--muted)">(leave blank to keep)</small></label>
            <input type="password" name="password" placeholder="Enter new password">
          </div>
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address"><?= htmlspecialchars($editUser['address']??'') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="user" <?= $editUser['role']==='user'?'selected':'' ?>>Customer</option>
              <option value="admin" <?= $editUser['role']==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= $editUser['status']==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= $editUser['status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="users.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- VIEW USER MODAL -->
<?php if ($viewUser): ?>
<div class="modal-overlay" id="viewUserModal" style="display:flex">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-eye" style="color:var(--info)"></i> User Details</h3>
      <a href="users.php" class="modal-close"><i class="fas fa-xmark"></i></a>
    </div>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:1.5rem">
        <div style="width:70px;height:70px;background:linear-gradient(135deg,var(--primary),#ff6b35);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;margin:0 auto .8rem">
          <?= strtoupper(substr($viewUser['full_name'],0,1)) ?>
        </div>
        <h3><?= htmlspecialchars($viewUser['full_name']) ?></h3>
        <p style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($viewUser['email']) ?></p>
        <span class="badge badge-<?= $viewUser['status']==='active'?'green':'red' ?>" style="margin-top:.5rem"><?= ucfirst($viewUser['status']) ?></span>
      </div>
      <?php $fields = [['Phone',$viewUser['phone']??'—','fas fa-phone'],['Role',ucfirst($viewUser['role']),'fas fa-shield'],['Address',$viewUser['address']??'—','fas fa-location-dot'],['Joined',date('d M Y', strtotime($viewUser['created_at'])),'fas fa-calendar']]; ?>
      <?php foreach($fields as [$label,$val,$icon]): ?>
      <div style="display:flex;align-items:start;gap:.8rem;padding:.7rem 0;border-bottom:1px solid var(--border)">
        <i class="<?= $icon ?>" style="color:var(--primary);margin-top:.2rem;width:16px"></i>
        <div><div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em"><?= $label ?></div>
        <div style="font-size:.9rem;margin-top:.15rem"><?= htmlspecialchars($val) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="modal-footer">
      <a href="users.php" class="btn btn-outline">Close</a>
      <a href="?edit=<?= $viewUser['id'] ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Edit</a>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="js/admin.js"></script>
<script>tableSearch('userSearch','usersTable');</script>
</body>
</html>
