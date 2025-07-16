<?php
require 'config.php';
$role = $_SESSION['role'];
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
if ($role == 'doctor') {
    // Add note
    if (isset($_POST['add'])) {
        $patient_id = $_POST['patient_id'];
        $note = $_POST['note'];
        $doctor_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO notes (patient_id, doctor_id, note) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patient_id, $doctor_id, $note);
        $stmt->execute();
        $_SESSION['flash'] = 'Note added.';
        header("Location: notes.php");
        exit;
    }
    // Get all patients
    $patients = $conn->query("SELECT * FROM patients");
    // Get all notes (with search)
    if ($search !== '') {
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notes JOIN patients ON notes.patient_id = patients.id WHERE patients.name LIKE ? OR notes.note LIKE ? OR patients.id = ?");
        $stmt->bind_param("ssi", $like, $like, $search);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        $stmt = $conn->prepare("SELECT notes.*, patients.name, patients.id as patient_id FROM notes JOIN patients ON notes.patient_id = patients.id WHERE patients.name LIKE ? OR notes.note LIKE ? OR patients.id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ssiii", $like, $like, $search, $per_page, $offset);
        $stmt->execute();
        $notes = $stmt->get_result();
    } else {
        $total = $conn->query("SELECT COUNT(*) FROM notes")->fetch_row()[0];
        $stmt = $conn->prepare("SELECT notes.*, patients.name, patients.id as patient_id FROM notes JOIN patients ON notes.patient_id = patients.id ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $notes = $stmt->get_result();
    }
} else {
    // Get patient id
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $patient = $res->fetch_assoc();
    $patient_id = $patient['id'];
    // Get notes for this patient (with search)
    if ($search !== '') {
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notes JOIN users ON notes.doctor_id = users.id WHERE patient_id=? AND (users.username LIKE ? OR notes.note LIKE ? OR users.id = ?)");
        $stmt->bind_param("issi", $patient_id, $like, $like, $search);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        $stmt = $conn->prepare("SELECT notes.*, users.username as doctor, users.id as doctor_id FROM notes JOIN users ON notes.doctor_id = users.id WHERE patient_id=? AND (users.username LIKE ? OR notes.note LIKE ? OR users.id = ?) ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("issiii", $patient_id, $like, $like, $search, $per_page, $offset);
        $stmt->execute();
        $notes = $stmt->get_result();
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notes WHERE patient_id=?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        $stmt = $conn->prepare("SELECT notes.*, users.username as doctor, users.id as doctor_id FROM notes JOIN users ON notes.doctor_id = users.id WHERE patient_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $patient_id, $per_page, $offset);
        $stmt->execute();
        $notes = $stmt->get_result();
    }
}
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <?php if ($role == 'doctor'): ?>
        <a href="patients.php">Patients</a>
        <a href="notes.php">Notes</a>
    <?php else: ?>
        <a href="profile.php">Profile</a>
        <a href="notes.php">Doctor Notes</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>Notes</h2>
    <form method="get" style="margin-bottom:18px;">
        <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search notes">
        <button type="submit">Search</button>
        <?php if ($search): ?><a href="notes.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <?php if ($role == 'doctor'): ?>
        <div style="margin-bottom:12px;">
            <a href="export_notes.php?format=csv" class="btn">Export CSV</a>
            <a href="export_notes.php?format=pdf" class="btn">Export PDF</a>
        </div>
        <h3>Add Note</h3>
        <form method="post">
            <select name="patient_id" required>
                <option value="">Select Patient</option>
                <?php while ($p = $patients->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>">ID: <?= $p['id'] ?> - <?= htmlspecialchars($p['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <textarea name="note" placeholder="Write note" required></textarea>
            <button name="add" type="submit">Add Note</button>
        </form>
        <h3>All Notes</h3>
        <table>
            <tr><th>Patient ID</th><th>Patient Name</th><th>Note</th><th>Date</th></tr>
            <?php while ($n = $notes->fetch_assoc()): ?>
                <tr>
                    <td><?= $n['patient_id'] ?></td>
                    <td><?= htmlspecialchars($n['name']) ?></td>
                    <td><?= htmlspecialchars($n['note']) ?></td>
                    <td><?= htmlspecialchars($n['created_at']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <?php if ($total_pages > 1): ?>
            <div style="text-align:center;margin:18px 0;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <strong><?= $i ?></strong>
                    <?php else: ?>
                        <a href="notes.php?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h3>Your Notes</h3>
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
    <?php endif; ?>
</div>
</body>
</html> 