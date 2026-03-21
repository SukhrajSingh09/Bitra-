<?php
header('Content-Type: application/json');
include "db.php";

$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

if ($type === 'all' || $type === '') {
    $stmt = $mysqli->prepare("
        SELECT title, society, building, room, event_date, event_time, description, type
        FROM events
        ORDER BY event_date ASC, event_time ASC
    ");
} else {
    $stmt = $mysqli->prepare("
        SELECT title, society, building, room, event_date, event_time, description, type
        FROM events
        WHERE type = ?
        ORDER BY event_date ASC, event_time ASC
    ");
    $stmt->bind_param("s", $type);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);
?>