<?php
header('Content-Type: application/json');
include "db.php";

if (!isset($_GET['building'])) {
    echo json_encode([]);
    exit;
}

$building = $_GET['building'];

$stmt = $mysqli->prepare("
    SELECT title, society, building, room, event_date, event_time, description
    FROM events
    WHERE building = ?
    ORDER BY event_date ASC, event_time ASC
");

$stmt->bind_param("s", $building);
$stmt->execute();

$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);
?>