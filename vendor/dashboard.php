<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Vendor Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f6fa;
    margin: 0;
}

/* NAVBAR */
.navbar {
    display: flex;
    justify-content: space-between;
    padding: 15px 30px;
    background: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.nav-left a {
    margin-right: 20px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

.nav-right a {
    color: red;
    text-decoration: none;
}

/* CONTAINER */
.container {
    max-width: 1000px;
    margin: 40px auto;
}

/* HEADER */
.header h2 {
    margin-bottom: 5px;
}

.header p {
    color: #666;
    margin-bottom: 25px;
}

/* CARD GRID */
.grid {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.card {
    flex: 1;
    min-width: 280px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

/* BUTTON */
.btn {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 16px;
    background: #1677ff;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.2s;
}

.btn:hover {
    background: #125edb;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="nav-left">
        <a href="#">Dashboard</a>
    </div>
    <div class="nav-right">
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>

<div class="container">

    <div class="header">
        <h2>Vendor Dashboard</h2>
        <p>Manage bookings and services for your company.</p>
    </div>

    <div class="grid">

        <!-- ACCOUNT INFO -->
        <div class="card">
            <h3>Account Info</h3>
            <p><b>User ID:</b> <?= $user_id ?></p>
            <p><b>Company ID:</b> <?= $company_id ?></p>
        </div>

        <!-- MANAGE BOOKINGS -->
        <div class="card">
            <h3>Booking Management</h3>
            <p>Review, approve or reject customer bookings.</p>

            <a href="manage_bookings.php" class="btn">
                Manage Bookings
            </a>
        </div>

        <!-- OPTIONAL: SERVICES -->
        <div class="card">
            <h3>Vendor Services</h3>
            <p>Manage your services (Photography, Decoration, etc).</p>

            <a href="services.php" class="btn">
                Manage Services
            </a>
        </div>

    </div>

</div>

</body>
</html>