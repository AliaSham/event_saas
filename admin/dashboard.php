<?php
session_start();

// PROTECT PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// INCLUDE HEADER (nav + CSS)
include('header.php');
?>

<div class="container">

    <h1>Admin Dashboard</h1>
    <p class="muted">Welcome back. Manage your system here.</p>

    <div class="dashboard-grid">

        <!-- COMPANY INFO -->
        <div class="card">
            <h3>Company Info</h3>
            <p>Company ID: <?php echo $_SESSION['company_id']; ?></p>
        </div>

        <!-- CATEGORY MODULE -->
        <div class="card">
            <h3>Category</h3>
            <p class="muted">Manage event categories</p>

            <a href="/event_saas/admin/category/index.php">
                Manage Category
            </a>
        </div>

    </div>

</div>