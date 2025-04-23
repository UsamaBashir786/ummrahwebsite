<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
$rooms = [];

if ($hotel_id) {
    $stmt = $conn->prepare("SELECT room_id FROM hotel_rooms WHERE hotel_id = ? AND status = 'available'");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt->close();
}

echo json_encode($rooms);
$conn->close();
?>