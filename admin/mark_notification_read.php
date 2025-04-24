<?php
require_once '../config/db.php'; // Include database connection

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
  $notification_id = (int)$_POST['notification_id'];
  
  $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
  $stmt->bind_param("i", $notification_id);
  
  if ($stmt->execute() && $stmt->affected_rows > 0) {
    $response['success'] = true;
  } else {
    $response['error'] = 'Failed to mark notification as read';
  }
  
  $stmt->close();
  $conn->close();
} else {
  $response['error'] = 'Invalid request';
}

echo json_encode($response);
exit;
?>