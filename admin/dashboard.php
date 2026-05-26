<?php
session_start();
include('../config/db.php');

// 1. Basic Check: Ensure user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

/* ========================================================
    LIVE CHECK 1: CHECK ADMIN ACCOUNT SUBSCRIPTION STATUS
======================================================== */
$statusQuery = mysqli_query($conn, "
    SELECT subscription_status 
    FROM users 
    WHERE id = '$user_id'
");
$userData = mysqli_fetch_assoc($statusQuery);

// RESTRICTION: If status in database is not active, kick them out!
if (!$userData || $userData['subscription_status'] !== 'Active') {
    session_destroy(); // Destroy all active browser sessions
    echo "<script>
        alert('Your session has been terminated! Your company subscription account is inactive or suspended. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

/* ========================================================
    LIVE CHECK 2: GET COMPANY PLAN & RECORD DATE
======================================================== */
$companyQuery = mysqli_query($conn, "
    SELECT plan, subscription_status, created_at
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);
$plan = $companyData['plan'];
$created_at = $companyData['created_at']; 

// RESTRICTION: Kick out if the company subscription status itself is changed to Inactive
if (!$companyData || $companyData['subscription_status'] !== 'Active') {
    session_destroy();
    echo "<script>
        alert('Your session has been terminated! This company subscription is inactive. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

/* ========================================================
    REAL-TIME LOGIC: CALCULATE EXPIRY & ENFORCE HARD BLOCK
======================================================== */
$expiry_alert_message = "";
$days_left = null;

if (!empty($created_at)) {
    $today = new DateTime(); // Current computer/server date time
    
    // SaaS Logic: Subscription lasts for 30 days from company creation date
    $registration_date = new DateTime($created_at);
    $expiry_date = clone $registration_date;
    $expiry_date->modify('+30 days'); // Automatically add 30 days
    
    $formatted_expiry = $expiry_date->format('Y-m-d');

    // SEMAKAN SEKATAN: Jika tarikh hari ini SUDAH MELEBIHI atau SAMA dengan tarikh luput
    if ($today >= $expiry_date) {
        session_destroy(); // Hancurkan session serta-merta!
        echo "<script>
            alert('CRITICAL: Your subscription expired on $formatted_expiry! Access denied. Please contact admin.');
            window.location.href = '../auth/login.php';
        </script>";
        exit;
    } else {
        // Jika belum luput, kira baki hari untuk paparan amaran banner
        $interval = $today->diff($expiry_date);
        $days_left = $interval->days; // Get total days remaining
        
        // IF DAYS REMAINING IS 3 DAYS OR LESS, SET WARNING BANNER
        if ($days_left <= 3) {
            $expiry_alert_message = "⚠️ WARNING: Your company's " . strtoupper($plan) . " plan subscription will expire in <b>$days_left days</b> ($formatted_expiry). Please renew immediately or please contact admin to avoid system disruption!";
        }
    }
}
?>

<?php include('header.php'); ?>

<div class="page-header" style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 600;">Welcome, Admin</h2>
    <p style="margin: 0; color: #6b7280; font-size: 14px;">An easy way to manage sales and event operations with care and precision.</p>
</div>

<?php if (!empty($expiry_alert_message)): ?>
    <div class="expiry-banner" style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 15px 20px; margin-bottom: 30px; border-radius: 10px; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <?= $expiry_alert_message ?>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 15px 25px; flex: 1; min-width: 200px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column;">
        <span style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">SaaS Company ID</span>
        <span style="font-size: 18px; font-weight: 600; color: #1f2937; margin-top: 2px;">#<?= $company_id ?></span>
    </div>
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 15px 25px; flex: 1; min-width: 200px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column;">
        <span style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Current Tier Plan</span>
        <span style="font-size: 18px; font-weight: 600; color: #2563eb; margin-top: 2px;"><?= strtoupper($plan) ?></span>
    </div>
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 15px 25px; flex: 1; min-width: 200px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column;">
        <span style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Subscription Status</span>
        <span style="font-size: 18px; font-weight: 600; color: #22c55e; margin-top: 2px;">Active</span>
    </div>
</div>

<div class="admin-grid">

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #3b82f6;">🏢</div>
            <h3>Company Profile</h3>
            <p>Update your company details shown to customers seamlessly.</p>
        </div>
        <a href="company_profile.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #a855f7;">📂</div>
            <h3>Manage Categories</h3>
            <p>Create, format, and organize diverse event classifications.</p>
        </div>
        <a href="category/index.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #f59e0b;">📖</div>
            <h3>Manage Packages</h3>
            <p>Create scalable client packages limited by SaaS dynamic allocations.</p>
        </div>
        <a href="package/index.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #10b981;">📋</div>
            <h3>Manage Bookings</h3>
            <p>Review standard customer bookings, verify details, and handle approval flows.</p>
        </div>
        <a href="bookings/index.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #ec4899;">📅</div>
            <h3>Manage Calendar</h3>
            <p>Maintain timeline accuracy by closing unavailable calendar booking slots.</p>
        </div>
        <a href="calendar/index.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #6366f1;">🤝</div>
            <h3>Manage Vendors</h3>
            <p>Incorporate global outsourced vendor profiles matching system policies.</p>
        </div>
        <a href="vendors/index.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #14b8a6;">💳</div>
            <h3>Manage Payments</h3>
            <p>Audit incoming client security deposits, record balances, and manage slips.</p>
        </div>
        <a href="payment_management.php" class="btn btn-primary">Open Module</a>
    </div>

    <div class="admin-card">
        <div>
            <div class="card-icon" style="color: #f43f5e;">🔔</div>
            <h3>Manage Notifications</h3>
            <p>Track general transactional activity logs and security alerts instantly.</p>
        </div>
        <a href="admin_notifications.php" class="btn btn-primary">Open Module</a>
    </div>

    <?php if($plan == 'premium'): ?>
    <div class="admin-card" style="border: 1px dashed #22c55e; background-color: rgba(74, 222, 128, 0.02);">
        <div>
            <div class="card-icon" style="background-color: #152d22; color: #22c55e;">📊</div>
            <h3>Premium Dashboard</h3>
            <p>Gain absolute workspace clarity using intelligence graphs and performance charts.</p>
        </div>
        <a href="premium_dashboard.php" class="btn btn-primary" style="background-color: #22c55e; color: #ffffff;">Unlock Insights</a>
    </div>
    <?php endif; ?>

</div>

<?php include('footer.php'); ?>