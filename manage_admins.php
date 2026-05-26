<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'developer') {
    header("Location: index.php");
    exit();
}

$result = $mysqli->query("SELECT id, username, email, role FROM users WHERE role = 'admin' ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Admin Accounts</h1>
        <a href="index.php" class="btn btn-secondary">Back to Map</a>
    </div>

    <div class="alert alert-info">
        Developer accounts can remove admin privileges. Removed admins become normal users.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <a class="btn btn-danger btn-sm"
                               href="revoke_admin.php?id=<?php echo (int)$row['id']; ?>"
                               onclick="return confirm('Remove admin privileges from this account?');">
                                Remove Admin
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
