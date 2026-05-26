<?php
// 1. Semakan keselamatan asas
if (!isset($_SESSION['user_id'])) {
    header("Location: /event_saas/auth/login.php");
    exit;
}

// Ambil data plan secara selamat sekiranya fail dalaman lupa pas pada header
$current_plan = isset($plan) ? strtolower($plan) : 'standard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Event SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        :root {
            --sidebar-bg: #0b1a13;
            --sidebar-text: #96a59e;
            --sidebar-active-bg: #152d22;
            --sidebar-active-text: #4ade80;
            --body-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --primary-color: #22c55e;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR STYLE */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ffffff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            padding: 25px 20px;
            box-sizing: border-box;
            z-index: 100;
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand span { color: var(--primary-color); }

        .menu-section-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4b5563;
            margin: 20px 0 10px 5px;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .sidebar-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .sidebar-menu li.active-page a {
            background-color: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 500;
        }

        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid #112a1e;
            padding-top: 15px;
            font-size: 13px;
            color: var(--sidebar-text);
        }

        /* MAIN WORKSPACE STYLE */
        .main-workspace {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .top-navbar {
            background-color: #ffffff;
            height: 70px;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }

        .search-wrapper input {
            background-color: #f3f4f6;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            width: 250px;
            outline: none;
        }

        .top-navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-role-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-logout-link {
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .content-body {
            padding: 40px;
            max-width: 1400px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Kemasan tambahan global untuk jadual (tables) & borang (forms) halaman dalaman */
        .page-inner-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        }

        @media (max-width: 992px) {
            .sidebar { width: 70px; padding: 25px 10px; }
            .sidebar-brand span, .menu-section-label, .sidebar-menu a span, .sidebar-footer { display: none; }
            .main-workspace { margin-left: 70px; }
            .top-navbar { padding: 0 20px; }
            .content-body { padding: 20px; }
        }
    </style>
</head>
<body>

<?php 
$current_file = basename($_SERVER['PHP_SELF']); 
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar">
    <div class="sidebar-brand">⚡ <span>SaaS</span>Admin</div>
    <div class="menu-section-label">Menu</div>
    <ul class="sidebar-menu">
        <li class="<?= ($current_file == 'dashboard.php' || $current_file == 'index.php' && $current_dir == 'admin') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/dashboard.php">📊 <span>Dashboard</span></a>
        </li>
        <li class="<?= ($current_file == 'company_profile.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/company_profile.php">🏢 <span>Company Profile</span></a>
        </li>
        <li class="<?= ($current_dir == 'category') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/category/index.php">📂 <span>Category</span></a>
        </li>
        <li class="<?= ($current_dir == 'package') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/package/index.php">📖 <span>Package</span></a>
        </li>
        <li class="<?= ($current_dir == 'calendar') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/calendar/index.php">📅 <span>Calendar</span></a>
        </li>
        <li class="<?= ($current_dir == 'vendors') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/vendors/index.php">🤝 <span>Vendors</span></a>
        </li>
        <li class="<?= ($current_dir == 'bookings') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/bookings/index.php">📋 <span>Bookings</span></a>
        </li>
    </ul>
    <div class="menu-section-label">General</div>
    <ul class="sidebar-menu">
        <li class="<?= ($current_file == 'payment_management.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/payment_management.php">💳 <span>Payments</span></a>
            <a href="/event_saas/admin/admin_notifications.php">🔔 <span>Notifications</span></a>
        </li>
        <?php if ($current_plan == 'premium' || $current_file == 'premium_dashboard.php'): ?>
        <li class="<?= ($current_file == 'premium_dashboard.php') ? 'active-page' : '' ?>">
            <a href="/event_saas/admin/premium_dashboard.php">📈 <span>Premium Intel</span></a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer"><span>Active Session</span></div>
</aside>

<main class="main-workspace">
    <header class="top-navbar">
        <div class="search-wrapper"><input type="text" placeholder="Search..."></div>
        <div class="top-navbar-right">
            <span class="admin-role-badge">Sales Admin</span>
            <a href="/event_saas/auth/logout.php" class="btn-logout-link">Logout 🚪</a>
        </div>
    </header>
    <div class="content-body">