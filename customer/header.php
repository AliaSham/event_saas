<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<div style="
    width:100%;
    height:68px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 50px;
    background:#ffffff;
    box-shadow:0 2px 12px rgba(0,0,0,0.05);
    box-sizing:border-box;
    position:sticky;
    top:0;
    z-index:9999;
    font-family:'Poppins', sans-serif;
">
    <div style="
        font-size:22px;
        font-weight:600;
        color:#1677ff;
    ">
        Event Booking
    </div>

    <div style="
        display:flex;
        align-items:center;
        gap:32px;
    ">
        <a href="/event_saas/customer/dashboard.php" style="text-decoration:none; color:#111; font-size:16px; font-weight:500;">Browse</a>
        <a href="/event_saas/customer/my_bookings.php" style="text-decoration:none; color:#111; font-size:16px; font-weight:500;">My Bookings</a>
        <a href="/event_saas/customer/account.php" style="text-decoration:none; color:#111; font-size:16px; font-weight:500;">My Account</a>
        <a href="/event_saas/auth/logout.php" style="text-decoration:none; color:#ff4d4f; font-size:16px; font-weight:500;">Logout</a>
    </div>
</div>