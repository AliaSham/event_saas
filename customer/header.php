<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:'Poppins',sans-serif;
}

.customer-navbar{
    width:100%;
    height:78px;
    padding:0 42px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:9999;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(18px);
    border-bottom:1px solid rgba(226,232,240,.9);
    box-shadow:0 12px 35px rgba(15,23,42,.08);
}

.customer-brand{
    display:flex;
    align-items:center;
    gap:13px;
    text-decoration:none;
}

.brand-icon{
    width:44px;
    height:44px;
    border-radius:16px;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    font-size:20px;
    background:linear-gradient(135deg,#1d4ed8,#7c3aed,#06b6d4);
    box-shadow:0 12px 25px rgba(59,130,246,.35);
}

.brand-text{
    line-height:1.1;
}

.brand-title{
    font-size:22px;
    font-weight:800;
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.brand-subtitle{
    font-size:11px;
    color:#64748b;
    margin-top:3px;
}

.customer-menu{
    display:flex;
    align-items:center;
    gap:10px;
}

.customer-menu a{
    text-decoration:none;
    color:#334155;
    font-size:14px;
    font-weight:600;
    padding:12px 16px;
    border-radius:999px;
    transition:.25s ease;
}

.customer-menu a:hover{
    background:#eef2ff;
    color:#4f46e5;
    transform:translateY(-1px);
}

.customer-menu a.active{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:white;
    box-shadow:0 12px 25px rgba(79,70,229,.25);
}

.logout-link{
    color:#ef4444 !important;
    background:#fff1f2;
}

.logout-link:hover{
    background:#ef4444 !important;
    color:white !important;
}

@media(max-width:768px){
    .customer-navbar{
        height:auto;
        padding:14px 18px;
        flex-direction:column;
        gap:14px;
        align-items:flex-start;
    }

    .customer-menu{
        width:100%;
        overflow-x:auto;
        padding-bottom:4px;
    }

    .customer-menu a{
        white-space:nowrap;
        font-size:13px;
        padding:10px 13px;
    }
}
</style>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="customer-navbar">

    <a href="/event_saas/customer/dashboard.php" class="customer-brand">
        <div class="brand-icon">🎉</div>

        <div class="brand-text">
            <div class="brand-title">Event Booking</div>
            <div class="brand-subtitle">Premium Event System</div>
        </div>
    </a>

    <div class="customer-menu">

        <a href="/event_saas/customer/dashboard.php"
           class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            Browse
        </a>

        <a href="/event_saas/customer/my_bookings.php"
           class="<?= $current_page == 'my_bookings.php' ? 'active' : '' ?>">
            My Bookings
        </a>

        <a href="/event_saas/customer/account.php"
           class="<?= $current_page == 'account.php' ? 'active' : '' ?>">
            My Account
        </a>

        <a href="/event_saas/auth/logout.php" class="logout-link">
            Logout
        </a>

    </div>

</div>