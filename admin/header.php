<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<div class="navbar">

    <!-- LEFT SIDE -->
    <div class="nav-left">
        <a href="/event_saas/admin/dashboard.php">Dashboard</a>
        <a href="/event_saas/admin/company_profile.php">Company Profile</a>
        <a href="/event_saas/admin/category/index.php">Category</a>
        <a href="/event_saas/admin/package/index.php">Package</a>
        <a href="/event_saas/admin/calendar/index.php">Calendar</a>
        <a href="/event_saas/admin/vendors/index.php">Vendors</a>
        <a href="/event_saas/admin/bookings/index.php">Bookings</a>
        <a href="/event_saas/admin/payment_management.php">Payments</a>
    </div>

    <!-- RIGHT SIDE -->
    <div class="nav-right">
        <?php include 'notification_widget.php'; ?>
        <a href="/event_saas/auth/logout.php">Logout</a>
    </div>

</div>