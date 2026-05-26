<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messages.php");
    exit();
}

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($receiver_id <= 0 || $subject === '' || $message === '') {
    header("Location: messages.php");
    exit();
}

$stmt = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $sender_id, $receiver_id, $subject, $message);
$stmt->execute();
$stmt->close();

$mysqli->close();
header("Location: messages.php");
exit();
?>
