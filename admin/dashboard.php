<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
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

<style>
body {
    font-family:'Poppins', sans-serif;
    background:#f5f6fa;
    margin:0;
}

/* NAVBAR */
.navbar {
    display:flex;
    justify-content:space-between;
    padding:15px 30px;
    background:white;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

.navbar a {
    text-decoration:none;
    color:#333;
    margin-left:15px;
}

.logout {
    color:red;
}

/* CONTAINER */
.container {
    padding:30px;
}

/* GRID */
.grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(250px,1fr));
    gap:20px;
}

/* CARD */
.card {
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
    transition:0.2s;
}

.card:hover {
    transform:translateY(-5px);
}

.card h3 {
    margin-bottom:10px;
}

.card p {
    color:#666;
    font-size:14px;
}

/* BUTTON */
.btn {
    display:inline-block;
    margin-top:10px;
    padding:10px 14px;
    background:#1677ff;
    color:white;
    border-radius:8px;
    text-decoration:none;
}
</style>

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
<div class="container">

<h2>Welcome, Admin</h2>
<p>Company ID: <?= $company_id ?></p>

<div class="grid">

    <!-- CATEGORY -->
    <div class="card">
        <h3>Manage Categories</h3>
        <p>Create and organize event types.</p>
        <a href="category/index.php" class="btn">Open</a>
    </div>

    <!-- PACKAGE -->
    <div class="card">
        <h3>Manage Packages</h3>
        <p>Create packages with SaaS limits.</p>
        <a href="package/index.php" class="btn">Open</a>
    </div>

    <!-- BOOKINGS -->
    <div class="card">
        <h3>View Bookings</h3>
        <p>Check all customer bookings.</p>
        <a href="../customer/history.php" class="btn">View</a>
    </div>

</div>

</div>

</body>
</html>