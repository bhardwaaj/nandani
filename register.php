<?php
require 'config.php';
$flash = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : null;
    // Check for duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = 'Username already taken.';
    } elseif (!in_array($role, ['doctor','patient'])) {
        $error = 'Invalid role.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("sss", $username, $hash, $role);
        $stmt->execute();
        $user_id = $conn->insert_id;
        if ($role === 'patient') {
            $stmt = $conn->prepare("INSERT INTO patients (user_id, name, age, gender) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $user_id, $name, $age, $gender);
            $stmt->execute();
        }
        $flash = 'Registration submitted! Awaiting admin approval.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
    <script>
    function togglePatientFields() {
        var role = document.getElementById('role').value;
        document.getElementById('patient-fields').style.display = (role === 'patient') ? 'block' : 'none';
    }
    </script>
</head>
<body>
<div class="navbar">
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
</div>
<div class="container">
    <h2>Register</h2>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <?php if ($error): ?><div class="flash error"><?= $error ?></div><?php endif; ?>
    <form method="post">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <select name="role" id="role" onchange="togglePatientFields()" required>
            <option value="">Select Role</option>
            <option value="doctor">Doctor</option>
            <option value="patient">Patient</option>
        </select>
        <div id="patient-fields" style="display:none;">
            <input name="name" placeholder="Full Name">
            <input name="age" type="number" placeholder="Age">
            <input name="gender" placeholder="Gender">
        </div>
        <button type="submit">Register</button>
    </form>
</div>
<script>togglePatientFields();</script>
</body>
</html> 