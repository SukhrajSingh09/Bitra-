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
    header("Location: admin_requests.php");
    exit();
}

$stmt = $mysqli->prepare("SELECT user_id FROM admin_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    header("Location: admin_requests.php");
    exit();
}

if ($action === 'approve') {
    $updateUser = $mysqli->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
    $updateUser->bind_param("i", $user_id);
    $updateUser->execute();
    $updateUser->close();

    $updateRequest = $mysqli->prepare("
        UPDATE admin_requests
        SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateRequest->bind_param("i", $request_id);
    $updateRequest->execute();
    $updateRequest->close();
} else {
    $updateRequest = $mysqli->prepare("
        UPDATE admin_requests
        SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateRequest->bind_param("i", $request_id);
    $updateRequest->execute();
    $updateRequest->close();
}

$mysqli->close();
header("Location: admin_requests.php");
exit();
?>