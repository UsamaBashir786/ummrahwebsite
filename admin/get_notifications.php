<?php
require_once '../config/db.php'; // Include database connection

header('Content-Type: application/json');

$query = "SELECT id, type, message, related_id, created_at 
          FROM notifications 
          WHERE status = 'unread' 
          ORDER BY created_at DESC 
          LIMIT 5";
$result = $conn->query($query);
$notifications = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
  }
  $result->free();
}

$conn->close();

echo json_encode(['notifications' => $notifications]);
exit;
