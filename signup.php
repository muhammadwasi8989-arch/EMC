<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';

if (!$full_name || !$email || !$password) {
    header('Location: login.php?tab=signup&error=missing');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?tab=signup&error=email');
    exit();
}

if (strlen($password) < 6) {
    header('Location: login.php?tab=signup&error=short');
    exit();
}

// Check duplicate
$check = $conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header('Location: login.php?tab=signup&error=exists');
    exit();
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?,?,?,?,'user','active')");
$stmt->bind_param('ssss', $full_name, $email, $phone, $hash);

if ($stmt->execute()) {
    header('Location: login.php?registered=1');
} else {
    header('Location: login.php?tab=signup&error=db');
}
exit();
?>
