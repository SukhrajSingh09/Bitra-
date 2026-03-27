<?php
header('Content-Type: application/json');
include "db.php";

$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

if ($type === 'all' || $type === '') {
    $stmt = $mysqli->prepare("
        SELECT id, title, society, building, room, event_date, event_time, description, type, reward_points
        FROM events
        ORDER BY event_date ASC, event_time ASC
    ");
} else {
    $stmt = $mysqli->prepare("
        SELECT id, title, society, building, room, event_date, event_time, description, type, reward_points
        FROM events
        WHERE type = ?
        ORDER BY event_date ASC, event_time ASC
    ");
    $stmt->bind_param("s", $type);
}

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "SQL prepare error: " . $mysqli->error
    ]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);

$stmt->close();
$mysqli->close();
?>