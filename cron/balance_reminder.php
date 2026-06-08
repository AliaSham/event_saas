<?php
// Run daily (e.g. via cron) to notify companies about balance due 3 days before event
if (php_sapi_name() !== 'cli') {
    // allow web execution but limit output
    ignore_user_abort(true);
    set_time_limit(0);
}

require_once __DIR__ . '/../config/db.php';

// Find bookings with remaining balance and event date exactly 3 days from today
$query = "
    SELECT b.id as booking_id, b.event_date, b.company_id, u.email, u.name, p.remaining_amount
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE p.remaining_amount > 0
      AND DATE(b.event_date) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
";

$res = mysqli_query($conn, $query);
$created = 0;
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $booking_id = intval($row['booking_id']);
        $company_id = intval($row['company_id']);
        $customer = $row['email'] ?: ($row['name'] ?: 'Customer');
        $amount = number_format($row['remaining_amount'], 2);

        // Avoid creating duplicate reminders in the last 7 days
        $existsQ = mysqli_query($conn, "SELECT 1 FROM admin_notifications WHERE booking_id = '$booking_id' AND type = 'payment_balance_due' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1");
        if ($existsQ && mysqli_num_rows($existsQ) > 0) continue;

        $message = "Balance payment due in 3 days for booking #$booking_id. Customer: $customer | Outstanding: RM $amount";
        $ins = mysqli_query($conn, "INSERT INTO admin_notifications (type, message, company_id, booking_id, is_read, created_at) VALUES ('payment_balance_due', '" . mysqli_real_escape_string($conn, $message) . "', '$company_id', '$booking_id', 0, NOW())");
        if ($ins) $created++;
    }
}

// Output summary when run from CLI
if (php_sapi_name() === 'cli') {
    echo "Balance reminder run: $created notifications created.\n";
} else {
    echo "Balance reminder run: $created notifications created.";
}

?>
