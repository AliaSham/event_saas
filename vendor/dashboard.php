<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

/* Get vendor info */
$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../auth/login.php");
    exit;
}

/* Check profile */
$profileQ = mysqli_query($conn, "
    SELECT * FROM vendor_profiles 
    WHERE user_id='$vendor_id'
");

$profile = mysqli_fetch_assoc($profileQ);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Vendor Dashboard</h2>
            <p class="muted">Welcome, <?= $user['name'] ?></p>
        </div>
    </div>

    <?php if (!$profile || empty($profile['service_type']) || empty($profile['price'])) { ?>
        <div class="alert error">
            Your vendor profile is not complete yet. Please complete your profile before companies can select you.
        </div>
    <?php } ?>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <h3>Vendor Profile</h3>
            <p>Complete or update your service details.</p>
            <a href="profile.php" class="btn btn-primary">
                <?= $profile ? 'Edit Profile' : 'Complete Profile' ?>
            </a>
        </div>

        <div class="dashboard-card">
            <h3>Vendor Calendar</h3>
            <p>Close dates when you are not available.</p>
            <a href="calendar/index.php" class="btn btn-primary">
                Open Calendar
            </a>
        </div>

       <div class="dashboard-card">
            <div>
                <h3>Assigned Bookings</h3>
                <p>View bookings where customers selected your service.</p>
            </div>

            <a href="jobs/index.php" class="btn btn-primary">
                View Jobs
            </a>
        </div>

    </div>

</div>

</body>
</html>