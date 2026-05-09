<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<div class="vendor-navbar">
    <div class="vendor-left">
        <a href="/event_saas/vendor/dashboard.php">Dashboard</a>
        <a href="/event_saas/vendor/profile.php">Profile</a>
        <a href="/event_saas/vendor/calendar/index.php">Calendar</a>
        <a href="/event_saas/vendor/jobs/index.php">Jobs</a>
    </div>

    <div class="vendor-right">
        <a href="/event_saas/auth/logout.php" class="logout">Logout</a>
    </div>
</div>