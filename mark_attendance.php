<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$event_id = (int) ($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event."
    ]);
    exit();
}

$stmt = $mysqli->prepare("SELECT id, reward_points FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    echo json_encode([
        "success" => false,
        "message" => "Event not found."
    ]);
    exit();
}

$reward_points = (int) $event['reward_points'];

$check = $mysqli->prepare("SELECT id FROM event_attendance WHERE user_id = ? AND event_id = ?");
$check->bind_param("ii", $user_id, $event_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    echo json_encode([
        "success" => false,
        "message" => "You have already attended this event."
    ]);
    exit();
}
$check->close();

$mysqli->begin_transaction();

try {
    $insert = $mysqli->prepare("
        INSERT INTO event_attendance (user_id, event_id, points_awarded)
        VALUES (?, ?, ?)
    ");
    $insert->bind_param("iii", $user_id, $event_id, $reward_points);
    $insert->execute();
    $insert->close();

    $update = $mysqli->prepare("
        UPDATE users
        SET points = points + ?
        WHERE id = ?
    ");
    $update->bind_param("ii", $reward_points, $user_id);
    $update->execute();
    $update->close();

    $mysqli->commit();

    $_SESSION['points'] = ($_SESSION['points'] ?? 0) + $reward_points;

    echo json_encode([
        "success" => true,
        "message" => "Attendance recorded. You earned {$reward_points} points!"
    ]);
} catch (Exception $e) {
    $mysqli->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Could not record attendance."
    ]);
}

$mysqli->close();
?>