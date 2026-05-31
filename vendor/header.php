<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<style>
.vendor-navbar{
    position:sticky;
    top:0;
    z-index:9999;

    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:0 40px;
    height:75px;

    background:#ffffff;
    border-bottom:1px solid #e5e7eb;

    box-shadow:0 4px 20px rgba(0,0,0,0.05);
    font-family:'Poppins',sans-serif;
}

.vendor-logo{
    display:flex;
    align-items:center;
    gap:12px;
}

.logo-icon{
    width:42px;
    height:42px;
    border-radius:12px;

    background:linear-gradient(
        135deg,
        #0f766e,
        #14b8a6,
        #2563eb
    );

    display:flex;
    align-items:center;
    justify-content:center;

    color:#fff;
    font-size:18px;
    font-weight:700;
}

.logo-text{
    font-size:20px;
    font-weight:700;
    color:#111827;
}

.vendor-menu{
    display:flex;
    align-items:center;
    gap:12px;
}

.vendor-menu a{
    text-decoration:none;
    color:#475569;
    font-size:14px;
    font-weight:600;

    padding:10px 18px;
    border-radius:12px;

    transition:.25s;
}

.vendor-menu a:hover{
    background:#f1f5f9;
    color:#0f766e;
}

.vendor-menu a.active{
    background:#ecfeff;
    color:#0f766e;
}

.vendor-right{
    display:flex;
    align-items:center;
    gap:15px;
}

.vendor-user{
    text-align:right;
}

.vendor-user span{
    display:block;
    font-size:12px;
    color:#94a3b8;
}

.vendor-user strong{
    font-size:14px;
    color:#111827;
}

.logout-btn{
    text-decoration:none;
    background:#ef4444;
    color:white;

    padding:10px 18px;
    border-radius:12px;

    font-size:13px;
    font-weight:600;

    transition:.25s;
}

.logout-btn:hover{
    background:#dc2626;
    transform:translateY(-2px);
}

@media(max-width:900px){

    .vendor-navbar{
        flex-direction:column;
        height:auto;
        padding:20px;
        gap:15px;
    }

    .vendor-menu{
        flex-wrap:wrap;
        justify-content:center;
    }

}
</style>

<div class="vendor-navbar">

    <div class="vendor-logo">
        <div class="logo-icon">
            E
        </div>

        <div class="logo-text">
            Event Booking
        </div>
    </div>

    <div class="vendor-menu">

        <a href="/event_saas/vendor/dashboard.php">
            🏠 Dashboard
        </a>

        <a href="/event_saas/vendor/profile.php">
            👤 Profile
        </a>

        <a href="/event_saas/vendor/calendar/index.php">
            📅 Calendar
        </a>

        <a href="/event_saas/vendor/jobs/index.php">
            📋 Jobs
        </a>

    </div>

    <div class="vendor-right">

        <div class="vendor-user">
            <span>Vendor Account</span>
            <strong><?= $_SESSION['name'] ?? 'Vendor'; ?></strong>
        </div>

        <a href="/event_saas/auth/logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</div>