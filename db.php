<?php
// =============================================
// DATABASE CONFIG
// Railway injects these env vars automatically.
// Fallback = local XAMPP settings.
// =============================================
define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'sh_market');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', (int)DB_PORT);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#e8380d">
        <h2>Database Connection Failed</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p><small>Railway env vars needed: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE</small></p>
    </div>');
}

@$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin','user') DEFAULT 'user',
    status ENUM('active','inactive') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    category VARCHAR(100),
    stock INT DEFAULT 0,
    image VARCHAR(500) DEFAULT NULL,
    featured TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$check = $conn->query("SELECT id FROM users WHERE email='admin@shmarket.com'");
if ($check && $check->num_rows === 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (full_name, email, password, role, status)
                  VALUES ('Super Admin', 'admin@shmarket.com', '$hash', 'admin', 'active')");
}

$pcheck = $conn->query("SELECT COUNT(*) as cnt FROM products");
if ($pcheck) {
    $prow = $pcheck->fetch_assoc();
    if ((int)$prow['cnt'] === 0) {
        $conn->query("INSERT INTO products (name, description, price, category, stock, featured, image) VALUES
            ('Premium Wireless Headphones', 'Noise-cancelling headphones with 40hr battery', 8999, 'Electronics', 25, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400'),
            ('Running Sneakers', 'Lightweight shoes with memory foam sole', 4500, 'Footwear', 40, 1, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'),
            ('Leather Wallet', 'Slim genuine leather bifold wallet', 1800, 'Accessories', 60, 0, 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=400'),
            ('Smart Watch', 'Fitness tracker with heart rate monitor and GPS', 15500, 'Electronics', 15, 1, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'),
            ('Cotton T-Shirt', 'Premium 100% cotton round neck t-shirt', 1200, 'Clothing', 100, 0, 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=400'),
            ('Sunglasses', 'UV400 protection polarized sunglasses', 2200, 'Accessories', 35, 0, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=400')
        ");
    }
}
?>
