<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($request_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header("Location: event_requests.php");
    exit();
}

$stmt = $mysqli->prepare("
    SELECT user_id, title, society, building, room, event_date, event_time, description, type, status
    FROM event_requests
    WHERE id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($user_id, $title, $society, $building, $room, $event_date, $event_time, $description, $type, $status);
$stmt->fetch();
$stmt->close();

if (!$user_id || $status !== 'pending') {
    header("Location: event_requests.php");
    exit();
}

if ($action === 'approve') {
    $insert = $mysqli->prepare("
        INSERT INTO events (title, society, building, room, event_date, event_time, description, type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insert) {
        die("Insert prepare error: " . $mysqli->error);
    }

    $insert->bind_param(
        "ssssssss",
        $title,
        $society,
        $building,
        $room,
        $event_date,
        $event_time,
        $description,
        $type
    );
    $insert->execute();
    $insert->close();

    $update = $mysqli->prepare("
        UPDATE event_requests
        SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $update->bind_param("i", $request_id);
    $update->execute();
    $update->close();
} else {
    $update = $mysqli->prepare("
        UPDATE event_requests
        SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $update->bind_param("i", $request_id);
    $update->execute();
    $update->close();
}

$mysqli->close();
header("Location: event_requests.php");
exit();
?>