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

// Sediakan juga variable $unread_count (huruf kecil & underscore) jika fail header.php anda menggunakannya
$unread_count = $unreadCount; 

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
/* KANDUNGAN NOTIFIKASI */
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.hero-stats {
    display: flex;
    gap: 15px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 20px 25px;
    border-radius: 20px;
    text-align: center;
    min-width: 130px;
}

.stat-card h2 {
    margin: 0;
    font-size: 32px;
    font-weight: 800;
}

.stat-card span {
    font-size: 12px;
    opacity: 0.8;
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
// Memanggil header selepas data perkiraan unread selesai diproses di atas
include('header.php'); 
?>

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

</body>
</html>