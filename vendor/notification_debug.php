<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

// Get all notifications for this vendor's bookings
$allNotifQ = mysqli_query($conn, "
    SELECT an.*, bv.id as bv_id, b.id as b_id, b.status, c.company_name, u.name as customer_name
    FROM admin_notifications an
    JOIN bookings b ON an.booking_id = b.id
    JOIN booking_vendors bv ON b.id = bv.booking_id
    JOIN companies c ON b.company_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE bv.vendor_id = '$vendor_id'
    ORDER BY an.created_at DESC
    LIMIT 50
");

// Get unread notifications (what mailbox shows)
$mailQ = mysqli_query($conn, "
    SELECT an.*, b.id as b_id
    FROM admin_notifications an
    JOIN bookings b ON an.booking_id = b.id
    JOIN booking_vendors bv ON b.id = bv.booking_id
    WHERE an.type IN ('booking_confirmed', 'vendor_booking_added', 'payment_deposit', 'payment_balance')
    AND bv.vendor_id = '$vendor_id'
    AND an.is_read = 0
    ORDER BY an.created_at DESC
    LIMIT 10
");

$allCount = mysqli_num_rows($allNotifQ);
$mailCount = mysqli_num_rows($mailQ);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vendor Notification Debug</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Poppins, sans-serif; padding: 20px; background: #f8fafc; }
        .container { max-width: 1000px; margin: 0 auto; }
        h2 { color: #0f172a; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #eff6ff; font-weight: 700; }
        .unread { background: #f0fdf4; }
        .code { background: #f1f5f9; padding: 10px; border-radius: 6px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>📬 Vendor Notification Debug</h1>
    
    <h2>Summary</h2>
    <p><strong>Total notifications for your bookings:</strong> <?= $allCount ?></p>
    <p><strong>Unread notifications (shown in mailbox):</strong> <?= $mailCount ?></p>
    
    <h2>All Notifications</h2>
    <?php if ($allCount > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Message</th>
                <th>Booking</th>
                <th>Customer</th>
                <th>Company</th>
                <th>Read?</th>
                <th>Created</th>
            </tr>
            <?php mysqli_data_seek($allNotifQ, 0); while($row = mysqli_fetch_assoc($allNotifQ)): ?>
                <tr class="<?= $row['is_read'] == 0 ? 'unread' : '' ?>">
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= $row['type'] ?></strong></td>
                    <td><?= htmlspecialchars(substr($row['message'], 0, 50)) ?>...</td>
                    <td>#<?= $row['booking_id'] ?></td>
                    <td><?= $row['customer_name'] ?? 'N/A' ?></td>
                    <td><?= $row['company_name'] ?? 'N/A' ?></td>
                    <td><?= $row['is_read'] == 0 ? '❌ Unread' : '✓ Read' ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="color: #ef4444;"><strong>⚠️ No notifications found!</strong></p>
        <p>Check that:</p>
        <ol>
            <li>You have been assigned to bookings</li>
            <li>Customers have completed payments</li>
            <li>Notifications are being inserted to the DB</li>
        </ol>
    <?php endif; ?>

    <h2>Unread Notifications (Mailbox Feed)</h2>
    <?php if ($mailCount > 0): ?>
        <table>
            <tr>
                <th>Message</th>
                <th>Type</th>
                <th>Created</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($mailQ)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['message']) ?></td>
                    <td><?= $row['type'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="color: #64748b;">No unread notifications (check the "All Notifications" table above)</p>
    <?php endif; ?>

    <h2>Debug Info</h2>
    <p><strong>Your vendor ID:</strong> <?= $vendor_id ?></p>
    <div class="code">
        // Query used for mailbox:<br>
        SELECT an.* FROM admin_notifications an<br>
        JOIN bookings b ON an.booking_id = b.id<br>
        JOIN booking_vendors bv ON b.id = bv.booking_id<br>
        WHERE an.type IN ('booking_confirmed', 'vendor_booking_added', 'payment_deposit', 'payment_balance')<br>
        AND bv.vendor_id = '<?= $vendor_id ?>'<br>
        AND an.is_read = 0
    </div>

    <a href="dashboard.php" style="color: #2563eb; text-decoration: none; font-weight: 700;">← Back</a>
</div>
</body>
</html>
