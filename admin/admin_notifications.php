<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
$current_plan = 'standard';

if ($company_id) {
    $planQuery = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
    if ($planQuery && mysqli_num_rows($planQuery) > 0) {
        $planData = mysqli_fetch_assoc($planQuery);
        $current_plan = strtolower($planData['plan'] ?? 'standard');
    }
}

/* ========================================================
   LOGIK TINDAKAN (Hanya beri kesan kepada Syarikat Sendiri)
   ======================================================== */

// 1. Mark All Read (Hanya untuk syarikat ini)
if (isset($_POST['mark_all_read']) && $company_id) {
    mysqli_query($conn, "UPDATE admin_notifications SET is_read = 1 WHERE company_id = '$company_id'");
}

// 2. Delete Single Notification (Pastikan milik syarikat ini untuk keselamatan)
if (isset($_POST['delete_notification']) && $company_id) {
    $notification_id = intval($_POST['notification_id']);
    mysqli_query($conn, "DELETE FROM admin_notifications WHERE id='$notification_id' AND company_id = '$company_id'");
}

// 3. Clear All (Hanya padam milik syarikat ini)
if (isset($_POST['clear_all']) && $company_id) {
    mysqli_query($conn, "DELETE FROM admin_notifications WHERE company_id = '$company_id'");
}

/* ========================================================
   QUERY DATA (Ditapis dengan WHERE company_id)
   ======================================================== */

// Ambil senarai notifikasi khas untuk syarikat ini sahaja
$notificationsQ = mysqli_query($conn, "
    SELECT * FROM admin_notifications 
    WHERE company_id = '$company_id'
    ORDER BY created_at DESC 
    LIMIT 100
");

// Kira jumlah unread khas untuk syarikat ini
$unreadCount = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS count FROM admin_notifications WHERE is_read = 0 AND company_id = '$company_id'
"))['count'] ?? 0;

// Kira jumlah keseluruhan khas untuk syarikat ini
$totalCount = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS count FROM admin_notifications WHERE company_id = '$company_id'
"))['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications - SaaS Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f4f7fb;
    color: #0f172a;
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #061b12, #0b2619);
    color: white;
    position: fixed;
    inset: 0 auto 0 0;
    padding: 28px 20px;
    z-index: 10;
}

.sidebar-brand {
    font-size: 22px;
    font-weight: 900;
    margin-bottom: 40px;
}

.sidebar-brand span {
    color: #22c55e;
}

.menu-section-label {
    font-size: 11px;
    letter-spacing: 1.5px;
    color: #64748b;
    text-transform: uppercase;
    margin: 25px 0 12px;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li a {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 13px 15px;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 14px;
    font-size: 14px;
    margin-bottom: 7px;
    transition: .2s ease;
}

.sidebar-menu li a:hover,
.sidebar-menu li.active-page a {
    background: rgba(34,197,94,.16);
    color: #4ade80;
}

.sidebar-footer {
    position: absolute;
    bottom: 25px;
    left: 20px;
    right: 20px;
    border-top: 1px solid rgba(255,255,255,.08);
    padding-top: 18px;
    color: #94a3b8;
    font-size: 13px;
}

/* MAIN */
.main-workspace {
    margin-left: 260px;
    width: calc(100% - 260px);
}

.top-navbar {
    height: 72px;
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid #e5e7eb;
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 8;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 900;
    color: #0f172a;
    text-decoration: none;
}

.logo {
    width: 42px;
    height: 42px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    font-weight: 900;
}

.top-navbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
}

.nav-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    border-radius: 15px;
    text-decoration: none;
}

.user-name {
    font-size: 14px;
    font-weight: 800;
}

/* CONTENT */
.content-body {
    padding: 38px;
}

.notification-hero {
    position: relative;
    overflow: hidden;
    border-radius: 32px;
    padding: 42px;
    color: white;
    background:
        radial-gradient(circle at top right, rgba(74,222,128,.45), transparent 32%),
        linear-gradient(135deg, #04140d, #0f5132 55%, #16a34a);
    box-shadow: 0 30px 70px rgba(15,23,42,.22);
    margin-bottom: 28px;
}

.notification-hero::after {
    content: "";
    position: absolute;
    right: -80px;
    bottom: -100px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    gap: 25px;
    align-items: center;
}

.notification-hero h1 {
    font-size: 40px;
    line-height: 1.1;
    margin: 0 0 12px;
    font-weight: 900;
}

.notification-hero p {
    margin: 0;
    color: #dcfce7;
    font-size: 15px;
}

.hero-stats {
    display: flex;
    gap: 16px;
}

.stat-card {
    min-width: 145px;
    padding: 22px;
    border-radius: 24px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(12px);
}

.stat-card h2 {
    margin: 0;
    font-size: 36px;
    font-weight: 900;
}

.stat-card span {
    font-size: 12px;
    color: #dcfce7;
}

/* CONTROL PANEL */
.control-panel {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 26px;
    padding: 20px;
    box-shadow: 0 18px 45px rgba(15,23,42,.07);
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    align-items: center;
}

.search-box {
    flex: 1;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 14px 18px;
    color: #64748b;
    font-size: 13px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons form {
    margin: 0;
}

button,
.btn-link {
    border: none;
    border-radius: 16px;
    padding: 13px 18px;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-read {
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.btn-clear {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.btn-dashboard {
    background: #0f172a;
    color: white;
}

/* NOTIFICATIONS */
.notifications-list {
    display: grid;
    gap: 18px;
}

.notification-card {
    position: relative;
    background: white;
    border-radius: 26px;
    padding: 22px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 18px 45px rgba(15,23,42,.07);
    display: grid;
    grid-template-columns: 58px 1fr auto;
    gap: 18px;
    align-items: flex-start;
    transition: .25s ease;
}

.notification-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 28px 65px rgba(15,23,42,.13);
}

.notification-card.unread {
    border-left: 7px solid #22c55e;
    background: linear-gradient(135deg, #f0fdf4, #ffffff 55%);
}

.icon-bubble {
    width: 58px;
    height: 58px;
    border-radius: 20px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    font-size: 24px;
    box-shadow: 0 12px 30px rgba(34,197,94,.30);
}

.notification-type {
    display: inline-flex;
    padding: 7px 14px;
    border-radius: 999px;
    background: #dcfce7;
    color: #166534;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.notification-message {
    font-size: 15px;
    color: #0f172a;
    font-weight: 700;
    line-height: 1.6;
    margin-bottom: 12px;
}

.notification-meta {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    color: #64748b;
    font-size: 12px;
}

.delete {
    background: #fff1f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 10px 14px;
}

.empty-state {
    background: white;
    border-radius: 30px;
    padding: 70px;
    text-align: center;
    border: 1px solid #e5e7eb;
    box-shadow: 0 18px 45px rgba(15,23,42,.07);
}

.empty-state h2 {
    margin: 0 0 8px;
    font-size: 28px;
    color: #06281d;
}

.empty-state p {
    color: #64748b;
}

@media(max-width: 900px) {
    .sidebar {
        width: 78px;
        padding: 25px 10px;
    }

    .sidebar-brand span,
    .sidebar-menu span,
    .menu-section-label,
    .sidebar-footer {
        display: none;
    }

    .main-workspace {
        margin-left: 78px;
        width: calc(100% - 78px);
    }

    .hero-content,
    .control-panel {
        flex-direction: column;
        align-items: flex-start;
    }

    .hero-stats {
        width: 100%;
    }

    .stat-card {
        flex: 1;
    }

    .notification-card {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php 
$current_file = basename($_SERVER['PHP_SELF']); 
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar">
    <div class="sidebar-brand">⚡ <span>SaaS</span> Admin</div>

    <div class="menu-section-label">Menu</div>
    <ul class="sidebar-menu">
        <li class="<?= ($current_file == 'dashboard.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/dashboard.php">📊 <span>Dashboard</span></a>
        </li>
        <li class="<?= ($current_file == 'company_profile.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/company_profile.php">🏢 <span>Company Profile</span></a>
        </li>
        <li class="<?= ($current_dir == 'category') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/category/index.php">📂 <span>Category</span></a>
        </li>
        <li class="<?= ($current_dir == 'package') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/package/index.php">📖 <span>Package</span></a>
        </li>
        <li class="<?= ($current_dir == 'calendar') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/calendar/index.php">📅 <span>Calendar</span></a>
        </li>
        <li class="<?= ($current_dir == 'vendors') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/vendors/index.php">🤝 <span>Vendors</span></a>
        </li>
        <li class="<?= ($current_dir == 'bookings') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/bookings/index.php">📋 <span>Bookings</span></a>
        </li>
    </ul>

    <div class="menu-section-label">General</div>
    <ul class="sidebar-menu">
        <li class="<?= ($current_file == 'payment_management.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/payment_management.php">💳 <span>Payments</span></a>
        </li>
        <li class="<?= ($current_file == 'admin_notifications.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/admin_notifications.php">🔔 <span>Notifications</span></a>
        </li>
        <?php if ($current_plan == 'premium' || $current_file == 'premium_dashboard.php'): ?>
        <li class="<?= ($current_file == 'premium_dashboard.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/premium_dashboard.php">📈 <span>Premium Intel</span></a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">Active Session</div>
</aside>

<main class="main-workspace">

<header class="top-navbar">
    <a href="/event_saas/admin/dashboard.php" class="brand">
        <div class="logo">S</div>
        <div>SaaSAdmin</div>
    </a>

    <div class="top-navbar-right">
        <a href="/event_saas/admin/admin_notifications.php" class="nav-icon">🔔</a>
        <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
    </div>
</header>

<div class="content-body">

    <section class="notification-hero">
        <div class="hero-content">
            <div>
                <h1>Notifications Center</h1>
                <p>Monitor customer payments, booking alerts and admin updates in a modern control panel.</p>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <h2><?= $totalCount ?></h2>
                    <span>Total Alerts</span>
                </div>

                <div class="stat-card">
                    <h2><?= $unreadCount ?></h2>
                    <span>Unread Alerts</span>
                </div>
            </div>
        </div>
    </section>

    <section class="control-panel">
        <div class="search-box">
            🔎 Notification Management / Latest 100 alerts
        </div>

        <div class="action-buttons">
            <form method="POST">
                <button class="btn-read" type="submit" name="mark_all_read" value="1" <?= $unreadCount == 0 ? 'disabled' : '' ?>>
                    ✓ Mark All Read
                </button>
            </form>

            <form method="POST">
                <button class="btn-clear" type="submit" name="clear_all" value="1" onclick="return confirm('Delete all notifications?');">
                    🗑 Clear All
                </button>
            </form>

            <a href="dashboard.php" class="btn-link btn-dashboard">← Dashboard</a>
        </div>
    </section>

    <section class="notifications-list">

        <?php if (mysqli_num_rows($notificationsQ) > 0) { ?>
            <?php while ($notification = mysqli_fetch_assoc($notificationsQ)) { ?>

                <div class="notification-card <?= $notification['is_read'] == 0 ? 'unread' : '' ?>">
                    <div class="icon-bubble">
                        <?= strpos($notification['type'], 'payment') !== false ? '💳' : '🔔' ?>
                    </div>

                    <div>
                        <span class="notification-type">
                            <?= ucfirst(str_replace('_', ' ', $notification['type'])) ?>
                        </span>

                        <div class="notification-message">
                            <?= htmlspecialchars($notification['message']) ?>
                        </div>

                        <div class="notification-meta">
                            <span>📅 <?= date('M d, Y • H:i', strtotime($notification['created_at'])) ?></span>

                            <?php if ($notification['booking_id']) { ?>
                                <span>📘 Booking #<?= $notification['booking_id'] ?></span>
                            <?php } ?>

                            <span><?= $notification['is_read'] == 0 ? '🟢 Unread' : '⚪ Read' ?></span>
                        </div>
                    </div>

                    <div>
                        <form method="POST">
                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                            <button type="submit" name="delete_notification" class="delete">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

            <?php } ?>
        <?php } else { ?>

            <div class="empty-state">
                <h2>🔔 No Notifications</h2>
                <p>You are all caught up. New system alerts will appear here.</p>
            </div>

        <?php } ?>

    </section>

</div>

</main>
</body>
</html>