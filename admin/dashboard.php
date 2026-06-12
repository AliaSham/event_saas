<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

$statusQuery = mysqli_query($conn, "
    SELECT subscription_status 
    FROM users 
    WHERE id = '$user_id'
");
$userData = mysqli_fetch_assoc($statusQuery);

if (!$userData || $userData['subscription_status'] !== 'Active') {
    session_destroy();
    echo "<script>
        alert('Your session has been terminated! Your company subscription account is inactive or suspended. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

$companyQuery = mysqli_query($conn, "
    SELECT plan, subscription_status, created_at
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);
$plan = strtolower($companyData['plan'] ?? 'basic');
$created_at = $companyData['created_at'] ?? null;

if (!$companyData || $companyData['subscription_status'] !== 'Active') {
    session_destroy();
    echo "<script>
        alert('Your session has been terminated! This company subscription is inactive. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

$expiry_alert_message = "";
$days_left = null;
$formatted_expiry = "N/A";

if (!empty($created_at)) {
    $today = new DateTime();
    $registration_date = new DateTime($created_at);
    $expiry_date = clone $registration_date;
    $expiry_date->modify('+30 days');
    $formatted_expiry = $expiry_date->format('Y-m-d');

    if ($today >= $expiry_date) {
        session_destroy();
        echo "<script>
            alert('CRITICAL: Your subscription expired on $formatted_expiry! Access denied. Please contact admin.');
            window.location.href = '../auth/login.php';
        </script>";
        exit;
    } else {
        $interval = $today->diff($expiry_date);
        $days_left = $interval->days;

        if ($days_left <= 3) {
            $expiry_alert_message = "⚠️ WARNING: Your company's " . strtoupper($plan) . " plan subscription will expire in <b>$days_left days</b> ($formatted_expiry). Please renew immediately!";
        }
    }
}

// AMBIL DATA REVENUE (Dikira untuk semua pelan supaya pemampatan data konsisten)
$query_revenue = mysqli_query($conn, "
    SELECT SUM(total_price) as total_revenue 
    FROM bookings 
    WHERE company_id = '$company_id'
");
$data_revenue = mysqli_fetch_assoc($query_revenue);
$total_revenue = $data_revenue['total_revenue'] ?? 0;

/* 📊 PERBAIKAN SQL STATUS: Menyesuaikan status mengikut standard database */
if ($plan === 'premium') {
    // Kira Pending Bookings
    $query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND UPPER(status) = 'PENDING'");
    $data_pending = mysqli_fetch_assoc($query_pending);
    $total_pending = $data_pending['total'] ?? 0;

    // 🔥 DIUBAH: Mengira status APPROVED atau COMPLETED sebagai Active Events supaya tidak keluar 0
    $query_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND (UPPER(status) = 'COMPLETED' OR UPPER(status) = 'APPROVED' OR UPPER(status) = 'PAID')");
    $data_active = mysqli_fetch_assoc($query_active);
    $total_active = $data_active['total'] ?? 0;

    // Kira Jumlah Packages
    $query_packages_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM packages WHERE company_id = '$company_id'");
    $data_packages_count = mysqli_fetch_assoc($query_packages_count);
    $total_packages = $data_packages_count['total'] ?? 0;

    // Ambil 5 Tempahan Terbaru
    $recent_bookings = mysqli_query($conn, "
        SELECT b.*, p.package_name, u.name as customer_name 
        FROM bookings b
        LEFT JOIN packages p ON b.package_id = p.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.company_id = '$company_id' 
        ORDER BY b.id DESC 
        LIMIT 5
    ");
}

include('header.php'); 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php if ($plan === 'basic' || $plan === 'standard'): ?>

<style>
.standard-dashboard-wrapper {
    padding: 32px 28px 60px;
    max-width: 1180px;
    margin: 0 auto;
    font-family: 'Poppins', sans-serif;
}

.standard-hero {
    background:
        radial-gradient(circle at top right, rgba(34,197,94,.25), transparent 30%),
        linear-gradient(135deg, #064e3b, #0f172a);
    color: white;
    border-radius: 26px;
    padding: 34px;
    margin-bottom: 24px;
    box-shadow: 0 22px 55px rgba(15,23,42,.16);
}

.standard-hero h2 {
    margin: 0 0 10px;
    font-size: 30px;
    font-weight: 850;
}

.standard-hero p {
    margin: 0;
    color: #d1fae5;
    font-size: 14px;
    line-height: 1.7;
}

.plan-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 16px;
}

.expiry-banner {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    padding: 15px 18px;
    margin-bottom: 22px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
}

.standard-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.summary-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 10px 28px rgba(15,23,42,.05);
}

.summary-box span {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}

.summary-box strong {
    color: #0f172a;
    font-size: 22px;
    font-weight: 850;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.admin-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 22px !important;
    padding: 24px !important;
    box-shadow: 0 14px 35px rgba(15,23,42,.055) !important;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 205px;
    transition: .25s ease;
}

.admin-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 22px 50px rgba(15,23,42,.09) !important;
}

.subscription-card-status {
    background: linear-gradient(135deg, #052e16, #064e3b) !important;
    color: #ffffff !important;
    border: none !important;
}

.subscription-card-status h3 {
    color: #ffffff !important;
}

.card-icon {
    width: 54px;
    height: 54px;
    border-radius: 17px;
    background: #dcfce7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.admin-card h3 {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 18px;
    font-weight: 800;
}

.admin-card p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
}

.sub-meta {
    font-size: 13px;
    color: #d1fae5;
    margin-bottom: 6px;
}

.sub-meta b {
    color: #86efac;
}

.sub-btn-group {
    display: flex;
    gap: 9px;
    margin-top: 18px;
}

.btn-sub-action {
    flex: 1;
    text-align: center;
    padding: 11px;
    font-size: 12px;
    font-weight: 800;
    border-radius: 12px;
    text-decoration: none;
    transition: .2s;
}

.btn-renew {
    background: #10b981;
    color: white;
}

.btn-upgrade-live {
    background: #8b5cf6;
    color: white;
}

.btn.btn-primary {
    margin-top: 20px;
    background: #059669 !important;
    color: #ffffff !important;
    border-radius: 12px !important;
    padding: 11px 14px !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    border: none !important;
    text-decoration: none !important;
    text-align: center;
}

@media (max-width: 900px) {
    .standard-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="standard-dashboard-wrapper">

    <div class="standard-hero">
        <div class="plan-pill">
            <i class="fa-solid fa-layer-group"></i>
            <?= strtoupper($plan) ?> ACCESS DASHBOARD
        </div>

        <h2>Welcome, Admin</h2>
        <p>
            You are currently using the <?= strtoupper($plan) ?> package. 
            This dashboard focuses on essential event management features with limited access based on your subscription plan.
        </p>
    </div>

    <?php if (!empty($expiry_alert_message)): ?>
        <div class="expiry-banner"><?= $expiry_alert_message ?></div>
    <?php endif; ?>

    <div class="standard-summary">
        <div class="summary-box">
            <span>Company ID</span>
            <strong><?= $company_id ?></strong>
        </div>

        <div class="summary-box">
            <span>Current Plan</span>
            <strong><?= strtoupper($plan) ?></strong>
        </div>

        <div class="summary-box">
            <span>Days Left</span>
            <strong><?= $days_left ?? 0 ?> Days</strong>
        </div>
    </div>

    <div class="admin-grid">

        <div class="admin-card subscription-card-status">
            <div>
                <div class="card-icon">💳</div>
                <h3>Subscription Status</h3>
                <div class="sub-meta">Plan: <b><?= strtoupper($plan) ?></b></div>
                <div class="sub-meta">Expired: <b style="color:#fca5a5;"><?= $formatted_expiry ?></b></div>
                <div class="sub-meta">Days Left: <b><?= $days_left ?? 0 ?> Days</b></div>
            </div>

            <div class="sub-btn-group">
                <a href="subscription/upgrade.php" class="btn-sub-action btn-renew">
                    <i class="fa-solid fa-rotate"></i> Renew
                </a>
                <a href="subscription/upgrade.php" class="btn-sub-action btn-upgrade-live">
                    <i class="fa-solid fa-crown"></i> Upgrade
                </a>
            </div>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">🏢</div><h3>Company Profile</h3><p>Update company details and business information.</p></div>
            <a href="company_profile.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">📂</div><h3>Manage Categories</h3><p>Standard plan allows limited category management.</p></div>
            <a href="category/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">📖</div><h3>Manage Packages</h3><p>Create and manage packages based on SaaS limits.</p></div>
            <a href="package/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">📋</div><h3>Manage Bookings</h3><p>Review customer bookings and booking records.</p></div>
            <a href="bookings/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">📅</div><h3>Manage Calendar</h3><p>Close unavailable dates for customer bookings.</p></div>
            <a href="calendar/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">🤝</div><h3>Manage Vendors</h3><p>Select vendors according to your package limit.</p></div>
            <a href="vendors/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">💳</div><h3>Manage Payments</h3><p>View deposits and payment status.</p></div>
            <a href="payment_management.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div><div class="card-icon">🔔</div><h3>Manage Notifications</h3><p>View system and booking notifications.</p></div>
            <a href="admin_notifications.php" class="btn btn-primary">Open</a>
        </div>

    </div>

    </div>

<?php else: ?>

<style>
.premium-dashboard {
    max-width: 1320px;
    margin: 0 auto;
    padding: 34px 24px 75px;
    font-family: 'Poppins', sans-serif;
}

/* PREMIUM HERO GREEN THEME */
.premium-hero {
    background:
        radial-gradient(circle at 85% 15%, rgba(34,197,94,.20), transparent 25%),
        radial-gradient(circle at 10% 90%, rgba(16,185,129,.28), transparent 28%),
        radial-gradient(circle at 55% 10%, rgba(74,222,128,.18), transparent 30%),
        linear-gradient(135deg, #001b12 0%, #022c22 45%, #064e3b 100%);
    color: white;
    border-radius: 34px;
    padding: 38px;
    margin-bottom: 26px;
    box-shadow: 0 32px 90px rgba(6,95,70,.35);
    position: relative;
    overflow: hidden;
}

.premium-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .32;
}

.premium-hero::after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    right: -80px;
    bottom: -110px;
    border-radius: 50%;
    background: rgba(34,197,94,.18);
    filter: blur(4px);
}

.premium-hero-content {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1.5fr .7fr;
    gap: 30px;
    align-items: center;
}

.premium-pill {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    width: fit-content;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(187,247,208,.35);
    padding: 9px 16px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    color: #bbf7d0;
    margin-bottom: 18px;
    box-shadow: 0 0 24px rgba(34,197,94,.18);
}

.premium-hero h2 {
    margin: 0 0 12px;
    font-size: 38px;
    line-height: 1.08;
    font-weight: 950;
    letter-spacing: -1px;
    color: #ffffff;
    text-shadow: 0 3px 18px rgba(0,0,0,.24);
}

.premium-hero p {
    margin: 0;
    max-width: 720px;
    color: #d1fae5;
    font-size: 14px;
    line-height: 1.8;
}

.hero-revenue-card {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(187,247,208,.28);
    border-radius: 26px;
    padding: 24px;
    backdrop-filter: blur(14px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
}

.hero-revenue-card span {
    color: #d1fae5;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.hero-revenue-card strong {
    display: block;
    margin-top: 10px;
    color: #86efac;
    font-size: 30px;
    font-weight: 950;
}

.glowing-banner {
    background: #fffaf0;
    border: 1px solid #feebc8;
    color: #dd6b20;
    border-radius: 16px;
    padding: 18px 22px;
    margin-bottom: 26px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.premium-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 26px;
}

.premium-stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 26px;
    padding: 23px;
    box-shadow: 0 18px 48px rgba(15,23,42,.08);
    position: relative;
    overflow: hidden;
    transition: .25s ease;
}

.premium-stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 26px 70px rgba(15,23,42,.14);
}

.premium-stat-card::after {
    content: "";
    position: absolute;
    width: 120px;
    height: 120px;
    right: -48px;
    top: -48px;
    border-radius: 50%;
    background: rgba(34,197,94,.12);
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 17px;
    display: grid;
    place-items: center;
    color: white;
    margin-bottom: 16px;
    box-shadow: 0 14px 30px rgba(15,23,42,.18);
}

.bg-orange { background: linear-gradient(135deg, #f97316, #dc2626); }
.bg-green { background: linear-gradient(135deg, #10b981, #047857); }
.bg-purple { background: linear-gradient(135deg, #22c55e, #064e3b); }
.bg-gold { background: linear-gradient(135deg, #84cc16, #059669); }

.premium-stat-card span {
    display: block;
    font-size: 11px;
    color: #64748b;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.premium-stat-card strong {
    display: block;
    margin-top: 9px;
    font-size: 26px;
    color: #020617;
    font-weight: 950;
}

.premium-layout {
    display: grid;
    grid-template-columns: 1.8fr 1fr;
    gap: 24px;
    margin-bottom: 26px;
}

.premium-panel,
.analytics-box {
    background: rgba(255,255,255,.94);
    border: 1px solid #e5e7eb;
    border-radius: 28px;
    padding: 28px;
    box-shadow: 0 20px 55px rgba(15,23,42,.07);
}

.panel-title {
    margin: 0 0 22px;
    font-size: 17px;
    font-weight: 950;
    color: #020617;
    display: flex;
    align-items: center;
    gap: 10px;
}

.booking-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border: 1px solid #e2e8f0;
    padding: 16px;
    border-radius: 20px;
    margin-bottom: 13px;
}

.booking-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.booking-avatar {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #047857;
}

.booking-item h4 {
    margin: 0;
    font-size: 13px;
    font-weight: 900;
    color: #020617;
}

.booking-item p {
    margin: 4px 0 0;
    font-size: 12px;
    color: #64748b;
}

.badge-status {
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 950;
    text-transform: uppercase;
}

.badge-pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}

.badge-approved {
    background: #d1fae5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.action-btn-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 15px 16px;
    border-radius: 16px;
    color: #0f172a;
    text-decoration: none;
    font-size: 13px;
    font-weight: 850;
    transition: .22s ease;
}

.action-btn-link:hover {
    transform: translateX(6px);
    background: #ecfdf5;
    color: #047857;
    border-color: #86efac;
}

.premium-sub-box {
    margin-top: 18px;
    background:
        radial-gradient(circle at top right, rgba(34,197,94,.25), transparent 34%),
        radial-gradient(circle at bottom left, rgba(16,185,129,.30), transparent 35%),
        linear-gradient(135deg, #001b12, #064e3b);
    color: white;
    border-radius: 24px;
    padding: 23px;
    box-shadow: 0 22px 55px rgba(6,95,70,.26);
}

.premium-sub-box h4 {
    margin: 0 0 13px;
    font-size: 14px;
    font-weight: 950;
}

.premium-sub-box p {
    margin: 8px 0;
    font-size: 12px;
    color: #d1fae5;
}

.premium-sub-box b {
    color: #86efac;
}

.premium-sub-action-btn {
    display: block;
    margin-top: 16px;
    text-align: center;
    background: linear-gradient(135deg, #22c55e, #059669);
    color: #ffffff;
    padding: 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 950;
    box-shadow: 0 16px 35px rgba(34,197,94,.28);
}

.premium-sub-action-btn:hover {
    background: linear-gradient(135deg, #16a34a, #047857);
}

.analytics-box {
    height: 540px;
}

.analytics-frame {
    width: 100%;
    height: calc(100% - 44px);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

@media (max-width: 1050px) {
    .premium-hero-content,
    .premium-layout {
        grid-template-columns: 1fr;
    }

    .premium-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 650px) {
    .premium-stats {
        grid-template-columns: 1fr;
    }

    .premium-hero h2 {
        font-size: 27px;
    }
}
</style>

<div class="premium-dashboard">

    <div class="premium-hero">
        <div class="premium-hero-content">
            <div>
                <div class="premium-pill">
                    <i class="fa-solid fa-crown"></i>
                    PREMIUM ACTIVE • EXECUTIVE MODE
                </div>

                <h2>Premium Intelligence Command Center</h2>
                <p>
                    Manage your event business with advanced revenue tracking, booking pipeline monitoring,
                    premium shortcuts and business intelligence analytics in one exclusive dashboard.
                </p>
            </div>

            <div class="hero-revenue-card">
                <span>Total Revenue Generated</span>
                <strong>RM <?= number_format($total_revenue, 2) ?></strong>
            </div>
        </div>
    </div>

    <?php if (!empty($expiry_alert_message)): ?>
        <div class="glowing-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?= $expiry_alert_message ?></div>
        </div>
    <?php endif; ?>

    <div class="premium-stats">
        <div class="premium-stat-card">
            <div class="stat-icon bg-orange"><i class="fa-solid fa-clock"></i></div>
            <span>Pending Action</span>
            <strong><?= $total_pending ?></strong>
        </div>

        <div class="premium-stat-card">
            <div class="stat-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
            <span>Active Events</span>
            <strong><?= $total_active ?></strong>
        </div>

        <div class="premium-stat-card">
            <div class="stat-icon bg-purple"><i class="fa-solid fa-box-open"></i></div>
            <span>Total Packages</span>
            <strong><?= $total_packages ?></strong>
        </div>

        <div class="premium-stat-card">
            <div class="stat-icon bg-gold"><i class="fa-solid fa-gem"></i></div>
            <span>Subscription</span>
            <strong><?= strtoupper($plan) ?></strong>
        </div>
    </div>

    <div class="premium-layout">
        <div class="premium-panel">
            <h3 class="panel-title">
                <i class="fa-solid fa-list-check" style="color:#059669;"></i>
                Premium Booking Activity Pipeline
            </h3>

            <?php if (mysqli_num_rows($recent_bookings) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($recent_bookings)): ?>
                    <div class="booking-item">
                        <div class="booking-left">
                            <div class="booking-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>

                            <div>
                                <h4><?= htmlspecialchars($row['customer_name'] ?? 'Guest') ?></h4>
                                <p>
                                    <?= htmlspecialchars($row['package_name'] ?? 'Package') ?> • 
                                    <?= htmlspecialchars($row['event_date']) ?>
                                </p>
                            </div>
                        </div>

                        <span class="badge-status <?= (strtoupper($row['status'] ?? 'PENDING') == 'PENDING') ? 'badge-pending' : 'badge-approved' ?>">
                            <?= ucfirst(strtolower($row['status'] ?? 'Pending')) ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:45px 20px; color:#64748b;">
                    <i class="fa-solid fa-calendar-xmark" style="font-size:32px; margin-bottom:10px;"></i>
                    <h4>No Recent Bookings</h4>
                </div>
            <?php endif; ?>
        </div>

        <div class="premium-panel">
            <h3 class="panel-title">
                <i class="fa-solid fa-bolt" style="color:#059669;"></i>
                Premium Control Panel
            </h3>

            <div class="shortcut-list">
                <a href="bookings/index.php" class="action-btn-link">
                    <span>Review Applications</span>
                    <i class="fa-solid fa-arrow-right-long"></i>
                </a>

                <a href="calendar/index.php" class="action-btn-link">
                    <span>Config Locked Dates</span>
                    <i class="fa-solid fa-arrow-right-long"></i>
                </a>

                <a href="package/index.php" class="action-btn-link">
                    <span>Inventory Packages (<?= $total_packages ?>)</span>
                    <i class="fa-solid fa-arrow-right-long"></i>
                </a>

                <a href="premium_dashboard.php" class="action-btn-link">
                    <span>Premium Intel</span>
                    <i class="fa-solid fa-chart-line"></i>
                </a>
            </div>

            <div class="premium-sub-box">
                <h4><i class="fa-solid fa-credit-card"></i> Premium Subscription Tracker</h4>
                <p>Expiry Date: <b><?= $formatted_expiry ?></b></p>
                <p>Remaining: <b><?= $days_left ?? 0 ?> Days</b></p>

                <a href="subscription/upgrade.php" class="premium-sub-action-btn">
                    <i class="fa-solid fa-rotate"></i> Continue / Renew Premium
                </a>
            </div>
        </div>
    </div>

    <div class="analytics-box">
        <h3 class="panel-title" style="margin-bottom:15px;">
            <i class="fa-solid fa-chart-pie" style="color:#059669;"></i>
            Executive Business Intelligence Analytics
        </h3>

        <div class="analytics-frame">
            <iframe 
                src="https://datastudio.google.com/embed/reporting/107ea33c-d1cd-4995-97f7-15cf16a5e891/page/qUPyF" 
                style="width:100%; height:100%; border:none;"
                allowfullscreen>
            </iframe>
        </div>
    </div>

</div>

<?php endif; ?>

<?php include('footer.php'); ?>