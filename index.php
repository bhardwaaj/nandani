<?php
// Redirect to dashboard if already logged in
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clinic App - Welcome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="index.php">Home</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
</div>
<div class="container">
    <h2>Welcome to the Clinic App</h2>
    <p>This is a demo two-tier application for doctors and patients.<br>
    Please login or register to continue.</p>
    <div style="margin-top:32px;">
        <a href="login.php" class="btn">Login</a>
        <a href="register.php" class="btn">Register</a>
    </div>
</div>
</body>
</html> 