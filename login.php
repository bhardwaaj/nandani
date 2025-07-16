<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['status'] !== 'active') {
            $error = "Account not approved. Please wait for admin approval.";
        } elseif (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="login.php">Login</a>
</div>
<div class="container">
    <h2>Login</h2>
    <?php if ($error): ?><div class="flash error"><?= $error ?></div><?php endif; ?>
    <form method="post">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html> 