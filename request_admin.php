<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role === 'admin') {
    echo json_encode([
        "success" => false,
        "message" => "You are already an admin."
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$message = trim($_POST['message'] ?? '');

// Check if user already has a request
$check = $mysqli->prepare("SELECT status FROM admin_requests WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->bind_result($status);
    $check->fetch();

    if ($status === 'pending') {
        echo json_encode([
            "success" => false,
            "message" => "You already have a pending admin request."
        ]);
        $check->close();
        $mysqli->close();
        exit;
    }

    if ($status === 'approved') {
        echo json_encode([
            "success" => false,
            "message" => "Your admin request has already been approved."
        ]);
        $check->close();
        $mysqli->close();
        exit;
    }

    if ($status === 'rejected') {
        $check->close();

        $update = $mysqli->prepare("
            UPDATE admin_requests
            SET message = ?, status = 'pending', created_at = CURRENT_TIMESTAMP, reviewed_at = NULL
            WHERE user_id = ?
        ");
        $update->bind_param("si", $message, $user_id);

        if ($update->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Your admin request has been resubmitted."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Could not resubmit your request."
            ]);
        }

        $update->close();
        $mysqli->close();
        exit;
    }
}

$check->close();

$stmt = $mysqli->prepare("
    INSERT INTO admin_requests (user_id, message, status)
    VALUES (?, ?, 'pending')
");
$stmt->bind_param("is", $user_id, $message);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Your admin request has been sent."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Could not send your request."
    ]);
}

$stmt->close();
$mysqli->close();
?>