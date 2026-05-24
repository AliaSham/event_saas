<?php
/**
 * Admin Notifications Display Component
 * Include this file in admin header to show notifications
 */

// Get unread notifications count
$notificationCountQ = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM admin_notifications 
    WHERE is_read = 0
");
$notificationCount = mysqli_fetch_assoc($notificationCountQ)['count'];

// Get recent unread notifications
$notificationsQ = mysqli_query($conn, "
    SELECT * FROM admin_notifications 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>

<style>
    .notification-bell {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    .notification-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        min-width: 350px;
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .notification-dropdown.show {
        display: block;
    }

    .notification-item {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
    }

    .notification-item:hover {
        background: #f9f9f9;
    }

    .notification-item.unread {
        background: #f0f7ff;
    }

    .notification-item-time {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }

    .notification-item-type {
        display: inline-block;
        padding: 3px 8px;
        background: #007bff;
        color: white;
        border-radius: 4px;
        font-size: 11px;
        margin-bottom: 5px;
    }

    .notification-footer {
        padding: 12px;
        text-align: center;
        border-top: 1px solid #f0f0f0;
    }

    .notification-footer a {
        color: #007bff;
        text-decoration: none;
        font-size: 14px;
    }

    .notification-footer a:hover {
        text-decoration: underline;
    }
</style>

<div class="notification-bell" onclick="toggleNotificationDropdown()">
    <span class="notification-badge" id="notificationBadge"><?php echo $notificationCount; ?></span>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
    </svg>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <?php if ($notificationCount > 0) { ?>
            <?php while ($notification = mysqli_fetch_assoc($notificationsQ)) { ?>
                <div class="notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                     onclick="markNotificationRead(<?php echo $notification['id']; ?>)">
                    <span class="notification-item-type"><?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?></span>
                    <div><?php echo $notification['message']; ?></div>
                    <div class="notification-item-time">
                        <?php echo timeAgo($notification['created_at']); ?>
                    </div>
                </div>
            <?php } ?>
            <div class="notification-footer">
                <a href="admin_notifications.php">View All Notifications</a>
            </div>
        <?php } else { ?>
            <div class="notification-item">
                <p style="margin: 0; text-align: center; color: #999;">No new notifications</p>
            </div>
        <?php } ?>
    </div>
</div>

<script>
function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');
    if (!bell.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

function markNotificationRead(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php
// Helper function to display time ago
function timeAgo($time) {
    $time = strtotime($time);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $time);
}
?>
