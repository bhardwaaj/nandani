<?php
require 'config.php';
if ($_SESSION['role'] != 'patient') { header("Location: dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
$stmt = $conn->prepare("SELECT patients.* FROM patients WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();

// Handle file upload
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $allowed = ['pdf','jpg','jpeg','png'];
    $max_size = 5*1024*1024;
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        $upload_error = 'Invalid file type.';
    } elseif ($_FILES['file']['size'] > $max_size) {
        $upload_error = 'File too large (max 5MB).';
    } elseif ($_FILES['file']['error'] !== 0) {
        $upload_error = 'Upload error.';
    } else {
        $newname = uniqid('file_').'.'.$ext;
        move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/'.$newname);
        $stmt = $conn->prepare("INSERT INTO files (patient_id, uploader_id, filename, original_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $profile['id'], $_SESSION['user_id'], $newname, $_FILES['file']['name']);
        $stmt->execute();
        header("Location: profile.php");
        exit;
    }
}
// Get files for this patient
$stmt = $conn->prepare("SELECT files.*, users.username as uploader FROM files JOIN users ON files.uploader_id = users.id WHERE patient_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $profile['id']);
$stmt->execute();
$files = $stmt->get_result();

if (isset($_POST['save'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $stmt = $conn->prepare("UPDATE patients SET name=?, age=?, gender=? WHERE user_id=?");
    $stmt->bind_param("sisi", $name, $age, $gender, $user_id);
    $stmt->execute();
    $_SESSION['flash'] = 'Profile updated.';
    header("Location: profile.php");
    exit;
}
if (isset($_POST['change_password'])) {
    $newpass = $_POST['new_password'];
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $hash, $user_id);
    $stmt->execute();
    $_SESSION['flash'] = 'Password changed.';
    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="notes.php">Doctor Notes</a>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>Your Profile</h2>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <div class="card">
        <strong>Patient ID:</strong> <?= $profile['id'] ?><br>
        <strong>User ID:</strong> <?= $user_id ?>
    </div>
    <form method="post">
        <input name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
        <input name="age" type="number" value="<?= htmlspecialchars($profile['age']) ?>" required>
        <input name="gender" value="<?= htmlspecialchars($profile['gender']) ?>" required>
        <button name="save" type="submit">Save</button>
    </form>
    <h3>Change Password</h3>
    <form method="post">
        <input name="new_password" type="password" placeholder="New Password" required>
        <button name="change_password" type="submit">Change Password</button>
    </form>
    <h3>Your Files</h3>
    <?php if ($upload_error): ?><div class="flash error"><?= $upload_error ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:18px;">
        <input type="file" name="file" required>
        <button type="submit">Upload File</button>
        <span style="font-size:0.95em;color:#888;">(PDF, JPG, PNG, max 5MB)</span>
    </form>
    <table>
        <tr><th>File</th><th>Uploader</th><th>Date</th><th>Download</th></tr>
        <?php while ($f = $files->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($f['original_name']) ?></td>
                <td><?= htmlspecialchars($f['uploader']) ?></td>
                <td><?= htmlspecialchars($f['uploaded_at']) ?></td>
                <td><a href="uploads/<?= urlencode($f['filename']) ?>" target="_blank">Download</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html> 