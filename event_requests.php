<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $mysqli->query("
    SELECT er.*, u.username, u.email
    FROM event_requests er
    JOIN users u ON er.user_id = u.id
    ORDER BY er.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Requests</title>
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

<h1>Event Requests</h1>
<p><a href="index.php">Back to map</a></p>

<?php while ($row = $result->fetch_assoc()): ?>
    <div class="card">
        <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
        Email: <?php echo htmlspecialchars($row['email']); ?><br>
        Status: <?php echo htmlspecialchars($row['status']); ?><br>
        Submitted: <?php echo htmlspecialchars($row['created_at']); ?><br><br>

        <strong>Title:</strong> <?php echo htmlspecialchars($row['title']); ?><br>
        <strong>Society:</strong> <?php echo htmlspecialchars($row['society'] ?: 'N/A'); ?><br>
        <strong>Building:</strong> <?php echo htmlspecialchars($row['building']); ?><br>
        <strong>Room:</strong> <?php echo htmlspecialchars($row['room'] ?: 'N/A'); ?><br>
        <strong>Date:</strong> <?php echo htmlspecialchars($row['event_date']); ?><br>
        <strong>Time:</strong> <?php echo htmlspecialchars($row['event_time']); ?><br>
        <strong>Type:</strong> <?php echo htmlspecialchars($row['type']); ?><br>
        <strong>Description:</strong><br>
        <?php echo nl2br(htmlspecialchars($row['description'] ?: 'No description')); ?><br><br>

        <?php if ($row['status'] === 'pending'): ?>
            <a class="btn approve" href="review_event_request.php?id=<?php echo $row['id']; ?>&action=approve">Approve</a>
            <a class="btn reject" href="review_event_request.php?id=<?php echo $row['id']; ?>&action=reject">Reject</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

</body>
</html>