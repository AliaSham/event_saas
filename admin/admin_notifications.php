<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    mysqli_query($conn, "UPDATE admin_notifications SET is_read = 1");
}

// Delete notification if requested
if (isset($_POST['delete_notification'])) {
    $notification_id = intval($_POST['notification_id']);
    mysqli_query($conn, "DELETE FROM admin_notifications WHERE id = '$notification_id'");
}

// Clear all notifications if requested
if (isset($_POST['clear_all'])) {
    mysqli_query($conn, "DELETE FROM admin_notifications");
}

// Filter by type
$type_filter = $_GET['type'] ?? '';
$where_clause = $type_filter ? "WHERE type = '" . mysqli_real_escape_string($conn, $type_filter) . "'" : "";

// Get all notifications
$notificationsQ = mysqli_query($conn, "
    SELECT * FROM admin_notifications 
    $where_clause
    ORDER BY created_at DESC 
    LIMIT 100
");

$unreadCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0"))['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .notification-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .notification-header h1 {
            margin: 0;
            color: #333;
        }

        .notification-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
        }

        .notification-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .action-buttons button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }

        .action-buttons button:hover {
            background: #f5f5f5;
        }

        .action-buttons button.danger {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .action-buttons button.danger:hover {
            background: #c82333;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .notification-row {
            padding: 15px;
            border: 1px solid #eee;
            margin-bottom: 1px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: background 0.2s;
        }

        .notification-row:hover {
            background: #f9f9f9;
        }

        .notification-row.unread {
            background: #f0f7ff;
            border-left: 4px solid #007bff;
        }

        .notification-content {
            flex: 1;
        }

        .notification-type {
            display: inline-block;
            padding: 4px 10px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .notification-type.payment {
            background: #28a745;
        }

        .notification-type.booking {
            background: #17a2b8;
        }

        .notification-message {
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .notification-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #999;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .notification-actions button {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }

        .notification-actions button:hover {
            background: #f0f0f0;
        }

        .notification-actions button.delete {
            color: #dc3545;
            border-color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="notification-container">
        <div class="notification-header">
            <h1>📬 Notifications</h1>
            <div class="notification-stats">
                <span>Unread: <strong class="badge"><?php echo $unreadCount; ?></strong></span>
            </div>
        </div>

        <div class="action-buttons">
            <form method="POST" style="display: inline;">
                <button type="submit" name="mark_all_read" value="1" <?php echo $unreadCount == 0 ? 'disabled' : ''; ?>>
                    ✓ Mark All as Read
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <button type="submit" name="clear_all" value="1" class="danger" onclick="return confirm('Delete all notifications?');">
                    🗑️ Clear All
                </button>
            </form>
            <a href="dashboard.php" style="padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 13px; display: inline-block;">
                ← Back to Dashboard
            </a>
        </div>

        <div class="notifications-list">
            <?php if (mysqli_num_rows($notificationsQ) > 0) { ?>
                <?php while ($notification = mysqli_fetch_assoc($notificationsQ)) { ?>
                    <div class="notification-row <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="notification-content">
                            <span class="notification-type <?php echo strtolower(explode('_', $notification['type'])[0]); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                            </span>
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            <div class="notification-meta">
                                <span>📅 <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></span>
                                <?php if ($notification['booking_id']) { ?>
                                    <span>📘 Booking: #<?php echo $notification['booking_id']; ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" name="delete_notification" class="delete">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <h2>No Notifications</h2>
                    <p>You're all caught up!</p>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
