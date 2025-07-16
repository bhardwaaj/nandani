<?php
require 'config.php';
if ($_SESSION['role'] != 'doctor') { header("Location: dashboard.php"); exit; }
if (!isset($_GET['id'])) { header("Location: patients.php"); exit; }
$pid = intval($_GET['id']);
// Get patient info
$stmt = $conn->prepare("SELECT patients.*, users.username FROM patients JOIN users ON patients.user_id = users.id WHERE patients.id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$res = $stmt->get_result();
$patient = $res->fetch_assoc();
if (!$patient) { header("Location: patients.php"); exit; }
// Get notes for this patient
$stmt = $conn->prepare("SELECT notes.*, users.username as doctor, users.id as doctor_id FROM notes JOIN users ON notes.doctor_id = users.id WHERE patient_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $pid);
$stmt->execute();
$notes = $stmt->get_result();
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
        $stmt->bind_param("iiss", $pid, $_SESSION['user_id'], $newname, $_FILES['file']['name']);
        $stmt->execute();
        header("Location: patient_detail.php?id=$pid");
        exit;
    }
}
// Get files for this patient
$stmt = $conn->prepare("SELECT files.*, users.username as uploader FROM files JOIN users ON files.uploader_id = users.id WHERE patient_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $pid);
$stmt->execute();
$files = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Detail</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <a href="patients.php">Patients</a>
    <a href="notes.php">Notes</a>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>Patient Detail</h2>
    <div class="card">
        <strong>Patient ID:</strong> <?= $patient['id'] ?><br>
        <strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?><br>
        <strong>Username:</strong> <?= htmlspecialchars($patient['username']) ?><br>
        <strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?><br>
        <strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?><br>
    </div>
    <h3>Notes for this Patient</h3>
    <table>
        <tr><th>Doctor ID</th><th>Doctor</th><th>Note</th><th>Date</th></tr>
        <?php while ($n = $notes->fetch_assoc()): ?>
            <tr>
                <td><?= $n['doctor_id'] ?></td>
                <td><?= htmlspecialchars($n['doctor']) ?></td>
                <td><?= htmlspecialchars($n['note']) ?></td>
                <td><?= htmlspecialchars($n['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <h3>Files for this Patient</h3>
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
    <a href="patients.php">&larr; Back to Patients</a>
</div>
</body>
</html> 