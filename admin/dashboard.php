<?php
session_start();
include('../config/db.php');

// 1. Semakan Asas: Pastikan pengguna sudah log masuk dan mempunyai role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

/* ========================================================
    LIVE CHECK: SEMAK STATUS LANGGANAN ADMIN & SYARIKAT
======================================================== */
$statusQuery = mysqli_query($conn, "
    SELECT subscription_status 
    FROM users 
    WHERE id = '$user_id'
");
$userData = mysqli_fetch_assoc($statusQuery);

// SEKATAN: Jika status dalam pangkalan data TIDAK AKTIF atau selain 'Active', tendang keluar!
if (!$userData || $userData['subscription_status'] !== 'Active') {
    session_destroy(); // Hancurkan semua session aktif pelayar
    echo "<script>
        alert('Sesi anda ditamatkan! Akaun langganan syarikat anda telah disekat atau tidak aktif.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

/* =========================
   GET COMPANY PLAN
========================= */
$companyQuery = mysqli_query($conn, "
    SELECT plan
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);

$plan = $companyData['plan'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<!-- NAVBAR -->
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

<!-- CONTENT -->
<div class="container admin-dashboard">

    <h2>Welcome, Admin</h2>

    <p class="company-id">
        Company ID: <?= $company_id ?>
    </p>

    <p class="company-id">
        Current Plan:
        <b><?= strtoupper($plan) ?></b>
    </p>

    <div class="admin-grid">

        <!-- COMPANY PROFILE -->
        <div class="admin-card">

            <div>
                <div class="card-icon">🏢</div>

                <h3>Company Profile</h3>

                <p>
                    Update your company details shown to customers.
                </p>
            </div>

            <a href="company_profile.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- CATEGORY -->
        <div class="admin-card">

            <div>
                <div class="card-icon">📂</div>

                <h3>Manage Categories</h3>

                <p>
                    Create and organize event types.
                </p>
            </div>

            <a href="category/index.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- PACKAGE -->
        <div class="admin-card">

            <div>
                <div class="card-icon">📖</div>

                <h3>Manage Packages</h3>

                <p>
                    Create packages with SaaS limits.
                </p>
            </div>

            <a href="package/index.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- BOOKINGS -->
        <div class="admin-card">

            <div>
                <div class="card-icon">📋</div>

                <h3>Manage Bookings</h3>

                <p>
                    Review customer bookings and approve or reject requests.
                </p>
            </div>

            <a href="bookings/index.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- CALENDAR -->
        <div class="admin-card">

            <div>
                <div class="card-icon">📅</div>

                <h3>Manage Calendar</h3>

                <p>
                    Close unavailable dates for booking.
                </p>
            </div>

            <a href="calendar/index.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- VENDORS -->
        <div class="admin-card">

            <div>
                <div class="card-icon">🤝</div>

                <h3>Manage Vendors</h3>

                <p>
                    Select vendors based on your subscription plan.
                </p>
            </div>

            <a href="vendors/index.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <!-- PREMIUM DASHBOARD -->
        <?php if($plan == 'premium'): ?>

        <div class="admin-card">

            <div>
                <div class="card-icon">📊</div>

                <h3>Premium Dashboard</h3>

                <p>
                    Advanced analytics, charts and revenue reports.
                </p>
            </div>

            <a href="premium_dashboard.php" class="btn btn-primary">
                Open
            </a>

        </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>