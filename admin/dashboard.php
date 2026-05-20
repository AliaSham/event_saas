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

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .expiry-banner {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="navbar">

    <div>
        <b>Admin Dashboard</b>
    </div>

    <div>
        <a href="../auth/logout.php" class="logout">
            Logout
        </a>
    </div>

</div>

<div class="container admin-dashboard">

    <h2>Welcome, Admin</h2>

    <?php if (!empty($expiry_alert_message)): ?>
        <div class="expiry-banner">
            <?= $expiry_alert_message ?>
        </div>
    <?php endif; ?>

    <p class="company-id">
        Company ID: <?= $company_id ?>
    </p>

    <p class="company-id">
        Current Plan:
        <b><?= strtoupper($plan) ?></b>
    </p>

    <div class="admin-grid">

        <div class="admin-card">
            <div>
                <div class="card-icon">🏢</div>
                <h3>Company Profile</h3>
                <p>Update your company details shown to customers.</p>
            </div>
            <a href="company_profile.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div>
                <div class="card-icon">📂</div>
                <h3>Manage Categories</h3>
                <p>Create and organize event types.</p>
            </div>
            <a href="category/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div>
                <div class="card-icon">📖</div>
                <h3>Manage Packages</h3>
                <p>Create packages with SaaS limits.</p>
            </div>
            <a href="package/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div>
                <div class="card-icon">📋</div>
                <h3>Manage Bookings</h3>
                <p>Review customer bookings and approve or reject requests.</p>
            </div>
            <a href="bookings/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div>
                <div class="card-icon">📅</div>
                <h3>Manage Calendar</h3>
                <p>Close unavailable dates for booking.</p>
            </div>
            <a href="calendar/index.php" class="btn btn-primary">Open</a>
        </div>

        <div class="admin-card">
            <div>
                <div class="card-icon">🤝</div>
                <h3>Manage Vendors</h3>
                <p>Select vendors based on your subscription plan.</p>
            </div>
            <a href="vendors/index.php" class="btn btn-primary">Open</a>
        </div>

        <?php if($plan == 'premium'): ?>
        <div class="admin-card">
            <div>
                <div class="card-icon">📊</div>
                <h3>Premium Dashboard</h3>
                <p>Advanced analytics, charts and revenue reports.</p>
            </div>
            <a href="premium_dashboard.php" class="btn btn-primary">Open</a>
        </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>