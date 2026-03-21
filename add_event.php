<?php
session_start();
ob_start();

header('Content-Type: application/json');
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit;
}

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    echo json_encode([
        "success" => false,
        "message" => "Only admins can add events."
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

$user_id = $_SESSION['user_id'];

$title = trim($_POST['title'] ?? '');
$society = trim($_POST['society'] ?? '');
$building = trim($_POST['building'] ?? '');
$room = trim($_POST['room'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$event_time = trim($_POST['event_time'] ?? '');
$description = trim($_POST['description'] ?? '');
$type = trim($_POST['type'] ?? '');

$allowedBuildings = ['MD', 'MX', 'MI', 'MC', 'MB', 'MA'];
$allowedTypes = ['study', 'sports'];

if ($title === '' || $building === '' || $event_date === '' || $event_time === '' || $type === '') {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit;
}

if (!in_array($building, $allowedBuildings, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid building selected."
    ]);
    exit;
}

if (!in_array($type, $allowedTypes, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event type selected."
    ]);
    exit;
}

$dateCheck = DateTime::createFromFormat('Y-m-d', $event_date);
$timeCheck = DateTime::createFromFormat('H:i', $event_time);

if (!$dateCheck || $dateCheck->format('Y-m-d') !== $event_date) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event date format."
    ]);
    exit;
}

if (!$timeCheck || $timeCheck->format('H:i') !== $event_time) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event time format."
    ]);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO events (user_id, title, society, building, room, event_date, event_time, description, type)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "SQL prepare error: " . $mysqli->error
    ]);
    exit;
}

$stmt->bind_param(
    "issssssss",
    $user_id,
    $title,
    $society,
    $building,
    $room,
    $event_date,
    $event_time,
    $description,
    $type
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Event added successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database execute error: " . $stmt->error
    ]);
}

$stmt->close();
$mysqli->close();
ob_end_flush();
?>