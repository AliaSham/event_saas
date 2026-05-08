<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
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
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</div>

<!-- CONTENT -->
<div class="container admin-dashboard">

    <h2>Welcome, Admin</h2>
    <p class="company-id">Company ID: <?= $company_id ?></p>

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
                <h3>View Bookings</h3>
                <p>Monitor all customer bookings.</p>
            </div>
            <a href="bookings/index.php" class="btn btn-primary">View</a>
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
    </div>

</div>

</body>
</html>