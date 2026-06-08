<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification_id = intval($_POST['notification_id'] ?? 0);

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Ensure this notification belongs to one of this user's bookings
$check = mysqli_query($conn, "SELECT an.id FROM admin_notifications an JOIN bookings b ON an.booking_id = b.id WHERE an.id = '$notification_id' AND b.user_id = '{$_SESSION['user_id']}' LIMIT 1");
if (!$check || mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

$stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
$stmt->bind_param('i', $notification_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}

?>
