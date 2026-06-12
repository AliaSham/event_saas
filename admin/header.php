<?php
// 1. Semakan keselamatan asas & pengaktifan sesi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memastikan fail sambungan database dimuatkan dengan betul
// Jika fail ini diletakkan di dalam subfolder yang dalam, pastikan path ini tepat
if (file_exists('../config/db.php')) {
    include('../config/db.php');
} elseif (file_exists('../../config/db.php')) {
    include('../../config/db.php');
} else {
    // Alternatif jika diletakkan secara mutlak dari root folder XAMPP
    include($_SERVER['DOCUMENT_ROOT'] . '/event_saas/config/db.php');
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /event_saas/auth/login.php");
    exit;
}

// Ambil company_id dari session secara selamat
$company_id = $_SESSION['company_id'] ?? null;
$current_plan = 'standard'; // Tetapkan nilai sandaran asal

// Buat semakan pelan secara langsung daripada database supaya kalis ralat di semua page
if ($company_id && isset($conn)) {
    $planQuery = mysqli_query($conn, "SELECT plan FROM companies WHERE id = '$company_id'");
    if ($planQuery && mysqli_num_rows($planQuery) > 0) {
        $planData = mysqli_fetch_assoc($planQuery);
        $current_plan = strtolower($planData['plan'] ?? 'standard');
    }
}
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
            --body-bg: linear-gradient(135deg,#1f5ca8,#2f80ed,#4f9df1);
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
            background: var(--body-bg);
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

    gap: 0px;
}

.sidebar-logo {
    width: 90px;
    height: 90px;
    object-fit: contain;
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
    min-height: 100vh;

    background: #f8fafc;

    background-attachment: fixed;
}

        /* TOP NAVBAR */
        .top-navbar {
            background: #ffffff !important;
            height: 64px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e5f0ea !important;
            box-shadow: 0 1px 12px rgba(34, 197, 94, 0.07), 0 2px 6px rgba(0, 0, 0, 0.04) !important;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .top-navbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1a2e23 !important;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
        }

        .top-navbar .brand .logo {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f0faf4 !important;
            border: 1px solid #d1f0de !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22c55e !important;
            font-weight: 800;
            font-size: 18px;
        }

        .top-navbar-left { display: flex; align-items: center; gap: 16px; }

        .top-navbar-right { display: flex; align-items: center; gap: 12px; }

        .nav-icon {
            color: #3a5a46 !important;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f0faf4 !important;
            border: 1px solid #d1f0de !important;
            text-decoration: none;
            transition: background 0.15s ease, transform 0.12s ease;
        }
        .nav-icon:hover { background: #dcf5e7 !important; transform: translateY(-2px); }

        .admin-role-badge {
            background-color: #f0faf4 !important;
            color: #166534 !important;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #d1f0de !important;
        }

        .btn-logout-link { color: #dc2626; text-decoration: none; font-size: 13px; font-weight: 600; }

        .user-menu { position: relative; padding-bottom: 8px; }
        .user-menu::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            height: 10px;
        }
        .user-menu .user-name {
            color: #1a2e23 !important;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }
        .user-menu .dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            background: #ffffff;
            border: 1px solid #e5f0ea;
            border-radius: 8px;
            box-shadow: 0 8px 28px rgba(34, 197, 94, 0.08), 0 2px 8px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 160ms ease, transform 160ms ease, visibility 160ms;
            min-width: 180px;
            pointer-events: none;
            z-index: 60;
        }
        .user-menu .dropdown a {
            display: block;
            padding: 10px 12px;
            color: #1a2e23;
            text-decoration: none;
            font-size: 14px;
        }
        .user-menu .dropdown a:hover { background: #f0faf4; }
        .user-menu:hover .dropdown,
        .user-menu .dropdown:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .content-body {
    padding: 28px;
    max-width: 1400px;
    width: 100%;
    box-sizing: border-box;

    flex: 1;
}

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
    <div class="sidebar-brand">

    <img src="/event_saas/assets/images/logo2.png"
         alt="Event Pro Logo"
         class="sidebar-logo">

    <span>Event</span>Pro

</div>
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
        </li>
        <li class="<?= ($current_file == 'admin_notifications.php') ? 'active-page' : '' ?>">
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
        <div class="top-navbar-left">
            <a href="/event_saas/admin/dashboard.php" class="brand">
                <div class="logo">S</div>
                <div class="brand-text">SaaSAdmin</div>
            </a>
        </div>

        <div class="top-navbar-right">
            <a href="/event_saas/admin/admin_notifications.php" class="nav-icon" title="Notifications">🔔</a>

            <div class="user-menu">
                <div class="user-name"><?= htmlspecialchars(($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Admin')) ?></div>
                <div class="dropdown">
                    <a href="/event_saas/admin/company_profile.php">Company Profile</a>
                    <a href="/event_saas/admin/payment_management.php">Payments</a>
                    <a href="/event_saas/auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
    <div class="content-body">