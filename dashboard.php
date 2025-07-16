<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($role == 'doctor') {
    $total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
    $total_notes = $conn->query("SELECT COUNT(*) FROM notes")->fetch_row()[0];
    $total_files = $conn->query("SELECT COUNT(*) FROM files")->fetch_row()[0];
} else {
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $patient = $res->fetch_assoc();
    $patient_id = $patient['id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notes WHERE patient_id=?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->bind_result($my_notes);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM files WHERE patient_id=?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->bind_result($my_files);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <?php if ($role == 'doctor'): ?>
        <a href="doctor_profile.php">Profile</a>
        <a href="patients.php">Patients</a>
        <a href="notes.php">Notes</a>
    <?php else: ?>
        <a href="profile.php">Profile</a>
        <a href="notes.php">Doctor Notes</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>Welcome, <?= htmlspecialchars($role) ?></h2>
    <div class="card">
        <strong>Your ID:</strong> <?= $user_id ?><br>
        <strong>Role:</strong> <?= htmlspecialchars($role) ?>
    </div>
    <?php if ($role == 'doctor'): ?>
        <div class="card">
            <strong>Total Patients:</strong> <?= $total_patients ?><br>
            <strong>Total Notes:</strong> <?= $total_notes ?><br>
            <strong>Total Files:</strong> <?= $total_files ?><br>
        </div>
        <a href="patients.php">View/Edit Patients</a><br>
        <a href="notes.php">Write/View Notes</a>
    <?php else: ?>
        <div class="card">
            <strong>Your Notes:</strong> <?= $my_notes ?><br>
            <strong>Your Files:</strong> <?= $my_files ?><br>
        </div>
        <a href="profile.php">View/Edit Profile</a><br>
        <a href="notes.php">View Doctor Notes</a>
    <?php endif; ?>
</div>
</body>
</html> 