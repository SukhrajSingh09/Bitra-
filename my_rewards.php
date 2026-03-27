<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$userStmt = $mysqli->prepare("SELECT username, points FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$historyStmt = $mysqli->prepare("
    SELECT e.title, e.event_date, e.event_time, e.building, ea.points_awarded, ea.attended_at
    FROM event_attendance ea
    JOIN events e ON ea.event_id = e.id
    WHERE ea.user_id = ?
    ORDER BY ea.attended_at DESC
");
$historyStmt->bind_param("i", $user_id);
$historyStmt->execute();
$history = $historyStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">My Rewards</h1>
        <a href="index.php" class="btn btn-secondary">Back to Map</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="mb-0"><strong>Total Points:</strong> <?php echo (int) $user['points']; ?></p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="h5 mb-3">Attendance History</h3>

            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Building</th>
                        <th>Points Earned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['building']); ?></td>
                            <td><?php echo (int) $row['points_awarded']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>