<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

// Already logged in redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') header('Location: admin/index.php');
    else header('Location: user/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Contact admin.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'admin') header('Location: admin/index.php');
                else header('Location: user/index.php');
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SH Market — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --primary: #e8380d;
    --primary-dark: #c42e09;
    --primary-light: #ff5a30;
    --bg: #0d0d0d;
    --bg2: #141414;
    --card: #1a1a1a;
    --border: #2a2a2a;
    --text: #f5f5f5;
    --muted: #888;
    --success: #22c55e;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family:'Outfit',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    overflow:hidden;
  }

  /* Background grid */
  body::before {
    content:'';
    position:fixed; inset:0;
    background-image: linear-gradient(rgba(232,56,13,.04) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(232,56,13,.04) 1px, transparent 1px);
    background-size: 50px 50px;
    pointer-events:none;
  }

  /* Glowing orbs */
  .orb {
    position:fixed;
    border-radius:50%;
    filter:blur(80px);
    pointer-events:none;
    opacity:.25;
  }
  .orb-1 { width:400px;height:400px;background:var(--primary);top:-100px;right:-100px; animation:float1 8s ease-in-out infinite; }
  .orb-2 { width:300px;height:300px;background:#3b82f6;bottom:-80px;left:-80px; animation:float2 10s ease-in-out infinite; }

  @keyframes float1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-30px,20px)} }
  @keyframes float2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(20px,-30px)} }

  .auth-wrapper {
    display:grid;
    grid-template-columns:1fr 1fr;
    max-width:900px;
    width:95%;
    background:var(--card);
    border:1px solid var(--border);
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 30px 80px rgba(0,0,0,.6);
    position:relative;
    z-index:2;
    animation: slideUp .6s cubic-bezier(.4,0,.2,1);
  }
  @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

  /* LEFT BRAND PANEL */
  .brand-panel {
    background: linear-gradient(135deg, #1a0800 0%, #2d0f00 50%, #1a0800 100%);
    padding:3rem;
    display:flex;
    flex-direction:column;
    justify-content:center;
    position:relative;
    overflow:hidden;
  }
  .brand-panel::before {
    content:'';
    position:absolute;
    width:300px;height:300px;
    background:radial-gradient(circle, rgba(232,56,13,.3) 0%, transparent 70%);
    bottom:-100px; right:-100px;
    border-radius:50%;
  }
  .brand-logo {
    display:flex; align-items:center; gap:.8rem;
    margin-bottom:2.5rem;
  }
  .logo-icon-wrap {
    width:50px;height:50px;
    background:var(--primary);
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.3rem;
    box-shadow:0 8px 20px rgba(232,56,13,.4);
  }
  .logo-text-big { font-size:1.6rem;font-weight:800; }
  .logo-text-big span { color:var(--primary); }
  .brand-tagline { font-size:1.8rem;font-weight:700;line-height:1.4;margin-bottom:1rem; }
  .brand-tagline span { color:var(--primary); }
  .brand-desc { color:var(--muted);font-size:.9rem;line-height:1.7;margin-bottom:2rem; }
  .brand-features { display:flex;flex-direction:column;gap:.8rem; }
  .brand-feature { display:flex;align-items:center;gap:.8rem;font-size:.85rem;color:#aaa; }
  .brand-feature i { color:var(--primary);width:16px; }

  /* RIGHT FORM PANEL */
  .form-panel { padding:3rem; }
  .form-header { margin-bottom:2rem; }
  .form-header h2 { font-size:1.8rem;font-weight:700;margin-bottom:.4rem; }
  .form-header p { color:var(--muted);font-size:.9rem; }

  /* Tabs */
  .auth-tabs {
    display:flex;
    background:var(--bg2);
    border-radius:12px;
    padding:4px;
    margin-bottom:2rem;
    border:1px solid var(--border);
  }
  .tab-btn {
    flex:1;padding:.65rem;border:none;cursor:pointer;
    background:transparent;color:var(--muted);
    border-radius:9px;font-size:.9rem;font-weight:600;
    font-family:'Outfit',sans-serif;
    transition:.25s;
  }
  .tab-btn.active { background:var(--primary);color:white;box-shadow:0 4px 12px rgba(232,56,13,.4); }

  .form-section { display:none; }
  .form-section.active { display:block; animation:fadeIn .3s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateX(10px)} to{opacity:1;transform:translateX(0)} }

  .form-group { margin-bottom:1.2rem; }
  .form-group label { display:block;font-size:.8rem;font-weight:600;color:#ccc;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
  .input-wrap { position:relative; }
  .input-wrap i { position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.9rem; }
  .input-wrap input {
    width:100%;
    background:var(--bg2);
    border:1px solid var(--border);
    color:var(--text);
    padding:.85rem 1rem .85rem 2.8rem;
    border-radius:10px;
    font-size:.92rem;
    font-family:'Outfit',sans-serif;
    transition:.2s;
    outline:none;
  }
  .input-wrap input:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(232,56,13,.15); }
  .pw-toggle {
    position:absolute;right:1rem;top:50%;transform:translateY(-50%);
    background:none;border:none;cursor:pointer;color:var(--muted);font-size:.9rem;
    transition:.2s;
  }
  .pw-toggle:hover { color:var(--primary); }

  .submit-btn {
    width:100%;
    background:linear-gradient(135deg, var(--primary), var(--primary-dark));
    color:white;border:none;cursor:pointer;
    padding:1rem;border-radius:12px;
    font-size:1rem;font-weight:700;
    font-family:'Outfit',sans-serif;
    transition:.3s;
    display:flex;align-items:center;justify-content:center;gap:.5rem;
    box-shadow:0 6px 20px rgba(232,56,13,.4);
    margin-top:.5rem;
  }
  .submit-btn:hover { background:linear-gradient(135deg, var(--primary-light), var(--primary));transform:translateY(-2px);box-shadow:0 10px 30px rgba(232,56,13,.5); }

  .alert {
    padding:.8rem 1rem;border-radius:10px;font-size:.85rem;
    margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;
  }
  .alert-error { background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171; }
  .alert-success { background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80; }

  .demo-box {
    background:rgba(232,56,13,.05);
    border:1px dashed rgba(232,56,13,.3);
    border-radius:10px;padding:.8rem 1rem;
    font-size:.78rem;color:var(--muted);margin-top:1.5rem;
    line-height:1.8;
  }
  .demo-box strong { color:var(--primary); }

  @media(max-width:700px) {
    .auth-wrapper { grid-template-columns:1fr; }
    .brand-panel { display:none; }
    .form-panel { padding:2rem 1.5rem; }
  }
</style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
  <!-- BRAND PANEL -->
  <div class="brand-panel">
    <div class="brand-logo">
      <div class="logo-icon-wrap"><i class="fas fa-store"></i></div>
      <div class="logo-text-big">SH <span>Market</span></div>
    </div>
    <div class="brand-tagline">Your <span>Premium</span> Shopping Destination</div>
    <p class="brand-desc">Discover thousands of products at the best prices. Fast delivery, secure payments, and 24/7 support.</p>
    <div class="brand-features">
      <div class="brand-feature"><i class="fas fa-check-circle"></i> Secure & encrypted checkout</div>
      <div class="brand-feature"><i class="fas fa-truck"></i> Fast nationwide delivery</div>
      <div class="brand-feature"><i class="fas fa-headset"></i> 24/7 customer support</div>
      <div class="brand-feature"><i class="fas fa-undo"></i> Easy returns & refunds</div>
    </div>
  </div>

  <!-- FORM PANEL -->
  <div class="form-panel">
    <div class="form-header">
      <h2>Welcome Back 👋</h2>
      <p>Sign in to your account or create a new one</p>
    </div>

    <!-- TABS -->
    <div class="auth-tabs">
      <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
      <button class="tab-btn" onclick="switchTab('signup')">Create Account</button>
    </div>

    <!-- LOGIN FORM -->
    <div class="form-section active" id="loginSection">
      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Account created! Please sign in.</div>
      <?php endif; ?>
      <?php if (isset($_GET['loggedout'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> You have been logged out successfully.</div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="loginPw" placeholder="Enter password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('loginPw',this)"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="submit-btn"><i class="fas fa-arrow-right-to-bracket"></i> Sign In</button>
      </form>

      <div class="demo-box">
        <strong>Demo Admin:</strong> admin@shmarket.com / admin123<br>
        <strong>Register below</strong> to create a user account
      </div>
    </div>

    <!-- SIGNUP FORM (redirect to signup.php) -->
    <div class="form-section" id="signupSection">
      <form method="POST" action="signup.php">
        <div class="form-group">
          <label>Full Name</label>
          <div class="input-wrap">
            <i class="fas fa-user"></i>
            <input type="text" name="full_name" placeholder="Your full name" required>
          </div>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="your@email.com" required>
          </div>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <div class="input-wrap">
            <i class="fas fa-phone"></i>
            <input type="tel" name="phone" placeholder="03XX-XXXXXXX">
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="signupPw" placeholder="Min 6 characters" required minlength="6">
            <button type="button" class="pw-toggle" onclick="togglePw('signupPw',this)"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="submit-btn"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>
    </div>
  </div>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', (i===0&&tab==='login')||(i===1&&tab==='signup')));
  document.getElementById('loginSection').classList.toggle('active', tab==='login');
  document.getElementById('signupSection').classList.toggle('active', tab==='signup');
}
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.innerHTML = `<i class="fas fa-eye${isText?'':'-slash'}"></i>`;
}
<?php if (isset($_GET['tab']) && $_GET['tab']==='signup'): ?>
switchTab('signup');
<?php endif; ?>
</script>
</body>
</html>
