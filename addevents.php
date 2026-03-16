<?php
include "db.php";

$title = $_POST['title'];
$society = $_POST['society'];
$building = $_POST['building'];
$room = $_POST['room'];
$date = $_POST['event_date'];
$time = $_POST['event_time'];
$description = $_POST['description'];

$stmt = $mysqli->prepare("
    INSERT INTO events
    (title, society, building, room, event_date, event_time, description)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssss",
    $title,
    $society,
    $building,
    $room,
    $date,
    $time,
    $description
);

if ($stmt->execute()) {
    echo "Event added successfully";
} else {
    echo "Error: " . $stmt->error;
}
?>