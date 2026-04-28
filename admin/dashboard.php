<?php
session_start();

// PROTECT PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// INCLUDE HEADER (navigation + CSS)
include('header.php');
?>

<h1>Admin Dashboard</h1>

<p>Company ID: <?php echo $_SESSION['company_id']; ?></p>

<hr>

<h3>Admin Menu</h3>
<ul>
    <li><a href="category/index.php">Manage Category</a></li>
</ul>