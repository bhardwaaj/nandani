<?php
require 'config.php';
// For demo, allow access if logged in as doctor (replace with admin role in real app)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') { die('Access denied'); }
$flash = '';
if (isset($_GET['approve'])) {
    $uid = intval($_GET['approve']);
    $conn->query("UPDATE users SET status='active' WHERE id=$uid");
    $flash = 'User approved.';
}
if (isset($_GET['reject'])) {
    $uid = intval($_GET['reject']);
    $conn->query("UPDATE users SET status='rejected' WHERE id=$uid");
    $flash = 'User rejected.';
}
$res = $conn->query("SELECT * FROM users ORDER BY status, id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Approvals</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <a href="dashboard.php">Dashboard</a>
    <a href="admin_users.php">User Approvals</a>
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h2>User Approvals</h2>
    <?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>
    <table>
        <tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr>
        <?php while ($u = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['status']) ?></td>
                <td>
                    <?php if ($u['status'] == 'pending'): ?>
                        <a href="admin_users.php?approve=<?= $u['id'] ?>">Approve</a> |
                        <a href="admin_users.php?reject=<?= $u['id'] ?>">Reject</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html> 