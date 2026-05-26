<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$isStaff = in_array($role, ['admin', 'developer'], true);

if ($isStaff) {
    $users = $mysqli->query("SELECT id, username, email, role FROM users WHERE id != $user_id ORDER BY username ASC");
} else {
    $users = $mysqli->query("SELECT id, username, email, role FROM users WHERE role IN ('admin', 'developer') ORDER BY role DESC, username ASC");
}

$stmt = $mysqli->prepare("\n    SELECT m.id, m.subject, m.message, m.is_read, m.created_at,\n           sender.username AS sender_name, receiver.username AS receiver_name\n    FROM messages m\n    JOIN users sender ON m.sender_id = sender.id\n    JOIN users receiver ON m.receiver_id = receiver.id\n    WHERE m.sender_id = ? OR m.receiver_id = ?\n    ORDER BY m.created_at DESC\n");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Messages</h1>
        <a href="index.php" class="btn btn-secondary">Back to Map</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Send Message</h2>
            <form action="send_message.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Send To</label>
                    <select name="receiver_id" class="form-select" required>
                        <option value="">Select recipient</option>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <option value="<?php echo (int)$u['id']; ?>">
                                <?php echo htmlspecialchars($u['username'] . ' (' . $u['role'] . ') - ' . $u['email']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Inbox / Sent Messages</h2>
            <?php while ($m = $messages->fetch_assoc()): ?>
                <div class="border rounded p-3 mb-3 bg-white">
                    <strong><?php echo htmlspecialchars($m['subject']); ?></strong><br>
                    <small>
                        From: <?php echo htmlspecialchars($m['sender_name']); ?> |
                        To: <?php echo htmlspecialchars($m['receiver_name']); ?> |
                        <?php echo htmlspecialchars($m['created_at']); ?>
                    </small>
                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>
