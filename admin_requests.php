<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $mysqli->query("
    SELECT ar.id, ar.user_id, ar.message, ar.status, ar.created_at, u.username, u.email
    FROM admin_requests ar
    JOIN users u ON ar.user_id = u.id
    ORDER BY ar.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            margin-right: 8px;
        }

        .approve {
            background: #28a745;
        }

        .reject {
            background: #dc3545;
        }
    </style>
</head>
<body>

<h1>Admin Requests</h1>
<p><a href="index.php">Back to map</a></p>

<?php while ($row = $result->fetch_assoc()): ?>
    <div class="card">
        <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
        Email: <?php echo htmlspecialchars($row['email']); ?><br>
        Status: <?php echo htmlspecialchars($row['status']); ?><br>
        Requested: <?php echo htmlspecialchars($row['created_at']); ?><br><br>

        <strong>Reason:</strong><br>
        <?php echo nl2br(htmlspecialchars($row['message'] ?: 'No message provided.')); ?><br><br>

        <?php if ($row['status'] === 'pending'): ?>
            <a class="btn approve" href="review_admin_request.php?id=<?php echo $row['id']; ?>&action=approve">Approve</a>
            <a class="btn reject" href="review_admin_request.php?id=<?php echo $row['id']; ?>&action=reject">Reject</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

</body>
</html>