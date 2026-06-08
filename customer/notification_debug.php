<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all notifications for this user's bookings
$allNotifQ = mysqli_query($conn, "
    SELECT an.*, b.id as b_id, b.status, c.company_name
    FROM admin_notifications an
    LEFT JOIN bookings b ON an.booking_id = b.id
    LEFT JOIN companies c ON b.company_id = c.id
    WHERE b.user_id = '$user_id'
    ORDER BY an.created_at DESC
    LIMIT 50
");

// Get unread payment notifications (what mailbox shows)
$mailQ = mysqli_query($conn, "
    SELECT an.*, b.id as b_id
    FROM admin_notifications an
    JOIN bookings b ON an.booking_id = b.id
    WHERE an.type IN ('payment_deposit', 'payment_balance', 'payment_balance_due')
    AND b.user_id = '$user_id'
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
    <title>Notification Debug</title>
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
    <h1>📬 Notification Debug</h1>
    
    <h2>Summary</h2>
    <p><strong>Total notifications for your bookings:</strong> <?= $allCount ?></p>
    <p><strong>Unread payment reminders (shown in mailbox):</strong> <?= $mailCount ?></p>
    
    <h2>All Notifications</h2>
    <?php if ($allCount > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Message</th>
                <th>Booking</th>
                <th>Status</th>
                <th>Company</th>
                <th>Read?</th>
                <th>Created</th>
            </tr>
            <?php mysqli_data_seek($allNotifQ, 0); while($row = mysqli_fetch_assoc($allNotifQ)): ?>
                <tr class="<?= $row['is_read'] == 0 ? 'unread' : '' ?>">
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= $row['type'] ?></strong></td>
                    <td><?= htmlspecialchars(substr($row['message'], 0, 60)) ?>...</td>
                    <td>#<?= $row['booking_id'] ?></td>
                    <td><?= $row['status'] ?? 'N/A' ?></td>
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
            <li>You have bookings in the system</li>
            <li>You completed a deposit payment (payment_status = 'Completed')</li>
            <li>The notification was inserted (check MySQL logs)</li>
        </ol>
    <?php endif; ?>

    <h2>Unread Payment Reminders (Mailbox Feed)</h2>
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
        <p style="color: #64748b;">No unread payment reminders (check the "All Notifications" table above)</p>
    <?php endif; ?>

    <h2>Debug Info</h2>
    <p><strong>Your user ID:</strong> <?= $user_id ?></p>
    <div class="code">
        // Query used for mailbox:<br>
        SELECT an.* FROM admin_notifications an<br>
        JOIN bookings b ON an.booking_id = b.id<br>
        WHERE an.type IN ('payment_deposit', 'payment_balance', 'payment_balance_due')<br>
        AND b.user_id = '<?= $user_id ?>'<br>
        AND an.is_read = 0
    </div>

    <a href="dashboard.php" style="color: #2563eb; text-decoration: none; font-weight: 700;">← Back</a>
</div>
</body>
</html>
