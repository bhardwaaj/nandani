<?php
require 'config.php';
if ($_SESSION['role'] != 'doctor') { header("Location: dashboard.php"); exit; }

// Flash message
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Delete patient
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    // Delete patient and user
    $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $uid = $row['user_id'];
        $conn->query("DELETE FROM patients WHERE id=$pid");
        $conn->query("DELETE FROM users WHERE id=$uid");
        $_SESSION['flash'] = 'Patient deleted.';
        header("Location: patients.php");
        exit;
    }
}

// Edit patient
$edit_patient = null;
if (isset($_GET['edit'])) {
    $pid = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT patients.*, users.username FROM patients JOIN users ON patients.user_id = users.id WHERE patients.id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_patient = $res->fetch_assoc();
}
if (isset($_POST['update'])) {
    $pid = intval($_POST['pid']);
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $stmt = $conn->prepare("UPDATE patients SET name=?, age=?, gender=? WHERE id=?");
    $stmt->bind_param("sisi", $name, $age, $gender, $pid);
    $stmt->execute();
    $_SESSION['flash'] = 'Patient updated.';
    header("Location: patients.php");
    exit;
}
// Add patient
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt = $conn->prepare("INSERT INTO patients (user_id, name, age, gender) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $name, $age, $gender);
    $stmt->execute();
    $_SESSION['flash'] = 'Patient added.';
    header("Location: patients.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total for pagination
if ($search !== '') {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT COUNT(*) FROM patients JOIN users ON patients.user_id = users.id WHERE patients.name LIKE ? OR users.username LIKE ? OR patients.id = ?");
    $stmt->bind_param("ssi", $like, $like, $search);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("SELECT patients.*, users.username FROM patients JOIN users ON patients.user_id = users.id WHERE patients.name LIKE ? OR users.username LIKE ? OR patients.id = ? LIMIT ? OFFSET ?");
    $stmt->bind_param("ssiii", $like, $like, $search, $per_page, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $total = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
    $stmt = $conn->prepare("SELECT patients.*, users.username FROM patients JOIN users ON patients.user_id = users.id LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
}
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patients</title>
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
    <h2>Patients</h2>
    <?php if ($_SESSION['role'] == 'doctor'): ?>
        <div style="margin-bottom:12px;">
            <a href="export_patients.php?format=csv" class="btn">Export CSV</a>
            <a href="export_patients.php?format=pdf" class="btn">Export PDF</a>
        </div>
    <?php endif; ?>
    <form method="get" style="margin-bottom:18px;">
        <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, username, or ID">
        <button type="submit">Search</button>
        <?php if ($search): ?><a href="patients.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <table>
        <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Username</th><th>Actions</th></tr>
        <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['age']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>
                    <a href="patient_detail.php?id=<?= $row['id'] ?>">View</a> |
                    <a href="patients.php?edit=<?= $row['id'] ?>">Edit</a> |
                    <a href="patients.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this patient?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php if ($total_pages > 1): ?>
        <div style="text-align:center;margin:18px 0;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="patients.php?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    <?php if ($edit_patient): ?>
        <h3>Edit Patient</h3>
        <form method="post">
            <input type="hidden" name="pid" value="<?= $edit_patient['id'] ?>">
            <input name="name" value="<?= htmlspecialchars($edit_patient['name']) ?>" required>
            <input name="age" type="number" value="<?= htmlspecialchars($edit_patient['age']) ?>" required>
            <input name="gender" value="<?= htmlspecialchars($edit_patient['gender']) ?>" required>
            <button name="update" type="submit">Update</button>
            <a href="patients.php">Cancel</a>
        </form>
    <?php else: ?>
        <h3>Add Patient</h3>
        <form method="post">
            <input name="name" placeholder="Name" required>
            <input name="age" type="number" placeholder="Age" required>
            <input name="gender" placeholder="Gender" required>
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button name="add" type="submit">Add</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html> 