<?php
// PROTECT PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<!-- LINK CSS (GLOBAL STYLE) -->
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<hr>

<div style="display: flex; justify-content: space-between; align-items: center;">

    <!-- LEFT MENU -->
    <div>
        <a href="/event_saas/admin/dashboard.php" style="margin-right:15px;">
            🏠 Dashboard
        </a>

        <a href="/event_saas/admin/category/index.php">
            📂 Category
        </a>
    </div>

    <!-- RIGHT MENU -->
    <div>
        <a href="/event_saas/auth/logout.php">
            🚪 Logout
        </a>
    </div>

</div>

<hr>