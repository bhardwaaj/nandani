<?php
require 'config.php';
if ($_SESSION['role'] != 'doctor') { header("Location: dashboard.php"); exit; }
$user_id = $_SESSION['user_id'];
$flash = '';
$error = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();

if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    // Check for duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND id!=?");
    $stmt->bind_param("si", $new_username, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = 'Username already taken.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $_SESSION['flash'] = 'Profile updated.';
        header("Location: doctor_profile.php");
        exit;
    }
}
if (isset($_POST['change_password'])) {
    $newpass = $_POST['new_password'];
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $hash, $user_id);
    $stmt->execute();
    $_SESSION['flash'] = 'Password changed.';
    header("Location: doctor_profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <a href="doctor_profile.php">Profile</a>
    <a href="patients.php">Patients</a>
    <a href="notes.php">Notes</a>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>Doctor Profile</h2>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <?php if ($error): ?><div class="flash error"><?= $error ?></div><?php endif; ?>
    <div class="card">
        <strong>Doctor ID:</strong> <?= $doctor['id'] ?><br>
        <strong>Username:</strong> <?= htmlspecialchars($doctor['username']) ?><br>
        <strong>Role:</strong> <?= htmlspecialchars($doctor['role']) ?><br>
    </div>
    <h3>Edit Username</h3>
    <form method="post">
        <input name="username" value="<?= htmlspecialchars($doctor['username']) ?>" required>
        <button name="update_profile" type="submit">Update Username</button>
    </form>
    <h3>Change Password</h3>
    <form method="post">
        <input name="new_password" type="password" placeholder="New Password" required>
        <button name="change_password" type="submit">Change Password</button>
    </form>
</div>
</body>
</html> 