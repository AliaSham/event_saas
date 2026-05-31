<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../auth/login.php");
    exit;
}

$profileQ = mysqli_query($conn, "
    SELECT * FROM vendor_profiles 
    WHERE user_id='$vendor_id'
");

$profile = mysqli_fetch_assoc($profileQ);

$isProfileComplete = $profile && !empty($profile['service_type']) && !empty($profile['price']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .main-wrapper {
            min-height: 100vh;
            padding: 35px 60px;
        }

        .hero {
            background: linear-gradient(135deg, #0f766e, #14b8a6, #2563eb);
            border-radius: 28px;
            padding: 38px 42px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(15, 118, 110, 0.25);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.13);
            right: -70px;
            top: -80px;
        }

        .hero h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
        }

        .hero p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: .95;
        }

        .status-pill {
            display: inline-block;
            margin-top: 22px;
            padding: 9px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .alert {
            margin-top: 25px;
            padding: 18px 22px;
            border-radius: 18px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 14px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.04);
        }

        .stats-row {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 22px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.07);
            border: 1px solid #eef2f7;
        }

        .stat-card h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        .stat-card h2 {
            margin: 10px 0 0;
            font-size: 26px;
            color: #0f172a;
        }

        .dashboard-grid {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 26px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
            border: 1px solid #eef2f7;
            transition: .25s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.13);
        }

        .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 22px;
            background: #ecfeff;
        }

        .dashboard-card h3 {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
        }

        .dashboard-card p {
            font-size: 14px;
            line-height: 1.7;
            color: #64748b;
            margin: 12px 0 26px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 13px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .25s ease;
        }

        .btn-primary {
            background: #0f766e;
            color: white;
        }

        .btn-primary:hover {
            background: #115e59;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn-blue:hover {
            background: #1d4ed8;
        }

        .btn-dark {
            background: #0f172a;
            color: white;
        }

        .btn-dark:hover {
            background: #1e293b;
        }

        @media (max-width: 900px) {
            .main-wrapper {
                padding: 25px;
            }

            .stats-row,
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="main-wrapper">

    <div class="hero">
        <h1>Vendor Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($user['name']) ?>. Manage your vendor profile, calendar and customer bookings in one place.</p>

        <div class="status-pill">
            <?= $isProfileComplete ? 'Profile Completed' : 'Profile Incomplete' ?>
        </div>
    </div>

    <?php if (!$isProfileComplete) { ?>
        <div class="alert">
            Your vendor profile is not complete yet. Please complete your service details before customers can select your service.
        </div>
    <?php } ?>

    <div class="stats-row">
        <div class="stat-card">
            <h4>Account Status</h4>
            <h2>Active</h2>
        </div>

        <div class="stat-card">
            <h4>Profile Status</h4>
            <h2><?= $isProfileComplete ? 'Complete' : 'Pending' ?></h2>
        </div>

        <div class="stat-card">
            <h4>Vendor Role</h4>
            <h2>Service</h2>
        </div>
    </div>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <div class="icon-box">👤</div>
            <h3>Vendor Profile</h3>
            <p>Update your service type, price, company details and vendor information.</p>

            <a href="profile.php" class="btn btn-primary">
                <?= $profile ? 'Edit Profile' : 'Complete Profile' ?>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="icon-box">📅</div>
            <h3>Vendor Calendar</h3>
            <p>Close unavailable dates and manage your schedule professionally.</p>

            <a href="calendar/index.php" class="btn btn-blue">
                Open Calendar
            </a>
        </div>

        <div class="dashboard-card">
            <div class="icon-box">📋</div>
            <h3>Assigned Bookings</h3>
            <p>View customer bookings where your service has been selected.</p>

            <a href="jobs/index.php" class="btn btn-dark">
                View Jobs
            </a>
        </div>

    </div>

</div>

</body>
</html>