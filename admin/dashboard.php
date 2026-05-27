<?php
session_start();
include('../config/db.php');

// 1. Basic Check: Ensure user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

/* ========================================================
    LIVE CHECK 1: CHECK ADMIN ACCOUNT SUBSCRIPTION STATUS
======================================================== */
$statusQuery = mysqli_query($conn, "
    SELECT subscription_status 
    FROM users 
    WHERE id = '$user_id'
");
$userData = mysqli_fetch_assoc($statusQuery);

if (!$userData || $userData['subscription_status'] !== 'Active') {
    session_destroy();
    echo "<script>
        alert('Your session has been terminated! Your company subscription account is inactive or suspended. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

/* ========================================================
    LIVE CHECK 2: GET COMPANY PLAN & RECORD DATE
======================================================== */
$companyQuery = mysqli_query($conn, "
    SELECT plan, subscription_status, created_at
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);
$plan = $companyData['plan'];
$created_at = $companyData['created_at']; 

if (!$companyData || $companyData['subscription_status'] !== 'Active') {
    session_destroy();
    echo "<script>
        alert('Your session has been terminated! This company subscription is inactive. Please contact admin.');
        window.location.href = '../auth/login.php';
    </script>";
    exit;
}

/* ========================================================
    REAL-TIME LOGIC: CALCULATE EXPIRY
======================================================== */
$expiry_alert_message = "";
$days_left = null;

if (!empty($created_at)) {
    $today = new DateTime();
    $registration_date = new DateTime($created_at);
    $expiry_date = clone $registration_date;
    $expiry_date->modify('+30 days');
    $formatted_expiry = $expiry_date->format('Y-m-d');

    if ($today >= $expiry_date) {
        session_destroy();
        echo "<script>
            alert('CRITICAL: Your subscription expired on $formatted_expiry! Access denied. Please contact admin.');
            window.location.href = '../auth/login.php';
        </script>";
        exit;
    } else {
        $interval = $today->diff($expiry_date);
        $days_left = $interval->days;
        if ($days_left <= 3) {
            $expiry_alert_message = "⚠️ WARNING: Your company's " . strtoupper($plan) . " plan subscription will expire in <b>$days_left days</b> ($formatted_expiry). Please renew immediately!";
        }
    }
}

/* ========================================================
    DATA RETRIEVAL QUERIES
======================================================== */
// Kira pending
$query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND LOWER(status) = 'pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$total_pending = $data_pending['total'] ?? 0;

// Kira active
$query_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND (LOWER(status) = 'approved' OR LOWER(status) = 'confirmed')");
$data_active = mysqli_fetch_assoc($query_active);
$total_active = $data_active['total'] ?? 0;

// Kira Total Revenue (Kutipan Bersih Syarikat terus dari jadual bookings)
$query_revenue = mysqli_query($conn, "
    SELECT SUM(total_price) as total_revenue 
    FROM bookings 
    WHERE company_id = '$company_id' 
    AND (LOWER(payment_status) = 'paid' OR LOWER(payment_status) = 'partial')
");
$data_revenue = mysqli_fetch_assoc($query_revenue);
$total_revenue = $data_revenue['total_revenue'] ?? 0;

// Jumlah Pakej Terdaftar
$query_packages_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM packages WHERE company_id = '$company_id'");
$data_packages_count = mysqli_fetch_assoc($query_packages_count);
$total_packages = $data_packages_count['total'] ?? 0;

// Jumlah Keseluruhan Bookings
$query_total_bookings = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id'");
$data_total_bookings = mysqli_fetch_assoc($query_total_bookings);
$total_bookings = $data_total_bookings['total'] ?? 0;

// Tarik 5 booking terkini
$recent_bookings = mysqli_query($conn, "
    SELECT b.*, p.package_name, u.name as customer_name 
    FROM bookings b
    LEFT JOIN packages p ON b.package_id = p.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.company_id = '$company_id' 
    ORDER BY b.id DESC 
    LIMIT 5
");

// Tarik 3 Pakej Paling Laris
$top_packages = mysqli_query($conn, "
    SELECT p.package_name, COUNT(b.id) as total_sales, p.base_price AS package_price
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    WHERE b.company_id = '$company_id'
    GROUP BY b.package_id, p.package_name, p.base_price
    ORDER BY total_sales DESC
    LIMIT 3
");
?>

<?php include('header.php'); ?>

<style>
    body {
        min-height: 100vh;
        margin: 0;
        background: linear-gradient(180deg, #f3fbf7 0%, #edf7ef 55%, #f9faf9 100%) !important;
        color: #1f2937;
        font-family: 'Poppins', sans-serif;
    }

    .dashboard-container {
        max-width: 1180px;
        margin: 0 auto;
        padding: 24px 20px 40px;
    }

    .dashboard-welcome {
        padding: 24px 28px;
        border-radius: 24px;
        background: #ffffff !important;
        border: 1px solid #d9e8dc;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
        margin-bottom: 24px;
    }
    .dashboard-welcome h2 { color: #0f172a !important; font-weight: 800; margin: 0; font-size: 32px; }
    .dashboard-welcome p { color: #475569 !important; margin: 10px 0 0 0; font-size: 15px; line-height: 1.75; }

    .dashboard-hero {
        display: grid;
        grid-template-columns: 1.8fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .hero-card {
        border-radius: 24px;
        background: #f8fcf7;
        border: 1px solid #dbe9dc;
        padding: 24px;
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.06);
    }
    .hero-card h3 {
        margin: 0 0 10px;
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }
    .hero-card p {
        margin: 0;
        color: #475569;
        line-height: 1.75;
        font-size: 14px;
    }
    .hero-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 24px;
    }
    .hero-stat {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e4ede6;
        padding: 16px;
    }
    .hero-stat span {
        display: block;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .hero-stat strong {
        color: #065f46;
        display: block;
        font-size: 24px;
        line-height: 1.1;
    }

    .glowing-banner {
        background: #eef8f0;
        border: 1px solid #c9e8d4;
        color: #166534;
        border-radius: 18px;
        padding: 16px 20px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-card-glass {
        background: #ffffff !important;
        border: 1px solid #e4ede7;
        border-radius: 22px;
        padding: 22px;
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.05);
        transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
    }
    .stat-card-glass:hover { transform: translateY(-3px); border-color: #c5e5d2; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
    .stat-label { font-size: 11px; color: #52635d; text-transform: uppercase; font-weight: 700; letter-spacing: 0.7px; margin-bottom: 14px; display: block; }
    .stat-value { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
    .stat-value strong { font-size: 34px; color: #0f172a; line-height: 1; }
    .stat-value span { color: #475569; font-size: 13px; }
    .stat-badge { background: #ecfdf3; color: #166534; padding: 6px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }

    .dashboard-split-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 22px; align-items: start; }
    
    @media (max-width: 1024px) {
        .dashboard-hero { grid-template-columns: 1fr; }
        .dashboard-summary { grid-template-columns: repeat(2, minmax(220px, 1fr)); }
        .dashboard-split-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
        .dashboard-summary { grid-template-columns: 1fr; }
    }

    .panel-glass {
        background: #ffffff !important;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.05);
    }
    .panel-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 12px; }
    .panel-note { color: #475569; font-size: 13px; margin-top: 10px; }

    .badge-status { padding: 6px 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-approved { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }

    .action-btn-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f7faf6;
        border: 1px solid #d8e6dc;
        padding: 14px 18px;
        border-radius: 16px;
        color: #1f2937;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .action-btn-link:hover { background: #eef7ee; color: #065f46; transform: translateX(4px); border-color: #cde6d5; }

    .sidebar-card { display: grid; gap: 16px; }
    .mini-card {
        border-radius: 18px;
        background: #f8fcf7;
        border: 1px solid #dbe8dd;
        padding: 16px;
    }
    .mini-card strong { display: block; font-size: 20px; color: #065f46; margin-bottom: 6px; }
    .mini-card span { display: block; font-size: 12px; color: #475569; }
</style>

<div class="dashboard-container">
    
    <div class="dashboard-welcome">
        <h2>Welcome back, Admin</h2>
        <p>Keep an eye on revenue, booking flow, and package demand with a clean, data-focused dashboard.</p>
    </div>

    <div class="dashboard-hero">
        <div class="hero-card">
            <h3>Performance overview</h3>
            <p>Your company insights are grouped here so you can act faster. Focus on pending approvals, confirmed bookings, and package demand in one view.</p>
            <div class="hero-meta">
                <div class="hero-stat">
                    <span>Total bookings</span>
                    <strong><?= $total_bookings ?></strong>
                </div>
                <div class="hero-stat">
                    <span>Total packages</span>
                    <strong><?= $total_packages ?></strong>
                </div>
            </div>
        </div>
        <div class="hero-card">
            <h3>Quick snapshot</h3>
            <p><strong style="color:#065f46;">RM <?= number_format($total_revenue, 2) ?></strong> total revenue collected.</p>
            <p><strong style="color:#065f46;"><?= $total_active ?> confirmed orders</strong> currently active.</p>
            <p><strong style="color:#065f46;"><?= $total_pending ?> pending requests</strong> waiting for review.</p>
        </div>
    </div>

    <?php if (!empty($expiry_alert_message)): ?>
        <div class="glowing-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?= $expiry_alert_message ?></div>
        </div>
    <?php endif; ?>

    <div class="dashboard-summary">
        <div class="stat-card-glass">
            <span class="stat-label">Pending approval</span>
            <div class="stat-value">
                <strong><?= $total_pending ?></strong>
                <span>Bookings need review</span>
            </div>
        </div>
        <div class="stat-card-glass">
            <span class="stat-label">Confirmed events</span>
            <div class="stat-value">
                <strong><?= $total_active ?></strong>
                <span>Active bookings</span>
            </div>
        </div>
        <div class="stat-card-glass">
            <span class="stat-label">Revenue collected</span>
            <div class="stat-value">
                <strong>RM <?= number_format($total_revenue, 2) ?></strong>
                <span>Paid or partial orders</span>
            </div>
        </div>
        <div class="stat-card-glass">
            <span class="stat-label">Company plan</span>
            <div class="stat-value">
                <strong><?= strtoupper($plan) ?></strong>
                <span>Subscription level</span>
            </div>
        </div>
    </div>

    <div class="dashboard-split-layout">

        <div class="main-analytics-area panel-glass">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 class="panel-title"><i class="fa-solid fa-list-check" style="color: #059669;"></i> Live Booking Activity Pipeline</h3>
                <a href="bookings/index.php" style="color: #065f46; text-decoration: none; font-size: 12px; font-weight: 600;">View All Bookings <i class="fa-solid fa-arrow-right" style="font-size: 10px; margin-left: 3px;"></i></a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php if (mysqli_num_rows($recent_bookings) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recent_bookings)): ?>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #fafafa; border: 1px solid #f0f0f0; padding: 14px 18px; border-radius: 14px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#fafafa';">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #718096; font-size: 13px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 13px; font-weight: 600; color: #1a202c;"><?= htmlspecialchars($row['customer_name'] ?? 'Walk-In / Guest') ?></h4>
                                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #718096;"><?= htmlspecialchars($row['package_name'] ?? 'Custom Package') ?> • <span style="color: #059669; font-weight: 500;"><?= htmlspecialchars($row['event_date']) ?></span></p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <?php if (strtolower($row['status'] ?? 'pending') == 'pending'): ?>
                                    <span class="badge-status badge-pending">Pending</span>
                                <?php else: ?>
                                    <span class="badge-status badge-approved">Approved</span>
                                <?php endif; ?>
                                <a href="bookings/index.php" style="color: #cbd5e1;"><i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i></a>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <h4 style="margin: 0; font-size: 14px; color: #718096;">No Recent Bookings Placed</h4>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-shortcut-area" style="display: flex; flex-direction: column; gap: 25px;">
            
            <div class="panel-glass">
                <h3 class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-bolt" style="color: #d97706;"></i> System Management</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="bookings/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#059669'" onmouseout="this.style.borderColor='#e2e8f0'">
                        <span><i class="fa-solid fa-plus-circle" style="margin-right: 6px; color: #059669;"></i> Review Applications</span>
                        <i class="fa-solid fa-arrow-right-long" style="opacity: 0.7; color: #059669;"></i>
                    </a>
                    <a href="calendar/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#ec4899'" onmouseout="this.style.borderColor='#e2e8f0'">
                        <span><i class="fa-solid fa-calendar-minus" style="margin-right: 6px; color: #ec4899;"></i> Config Locked Dates</span>
                        <i class="fa-solid fa-arrow-right-long" style="opacity: 0.7; color: #ec4899;"></i>
                    </a>
                    <a href="package/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#059669'" onmouseout="this.style.borderColor='#e2e8f0'">
                        <span><i class="fa-solid fa-box-open" style="margin-right: 6px; color: #059669;"></i> Inventory Packages (<?= $total_packages ?>)</span>
                        <i class="fa-solid fa-arrow-right-long" style="opacity: 0.7; color: #059669;"></i>
                    </a>
                </div>
            </div>

            <div class="panel-glass" style="background: #ffffff;">
                <h3 class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Top Demand Packages</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if(mysqli_num_rows($top_packages) > 0): ?>
                        <?php $rank = 1; while($pkg = mysqli_fetch_assoc($top_packages)): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: 700; color: #64748b; font-size: 13px;">#<?= $rank++ ?></span>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 600; color: #1a202c;"><?= htmlspecialchars($pkg['package_name']) ?></div>
                                        <div style="font-size: 11px; color: #94a3b8;">RM <?= number_format($pkg['package_price'], 2) ?></div>
                                    </div>
                                </div>
                                <span style="background: #fee2e2; color: #ef4444; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 8px;">
                                    <?= $pkg['total_sales'] ?> Orders
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px 0;">No purchase records found yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($plan == 'premium'): ?>
            <div class="panel-glass" style="border: 1px solid #a7f3d0; background: linear-gradient(135deg, #f0fdf4, #ffffff) !important; padding: 22px;">
                <h3 class="panel-title" style="color: #047857; margin-bottom: 6px;"><i class="fa-solid fa-chart-line"></i> Premium Insights</h3>
                <p style="color: #4a5568; font-size: 12px; margin: 0 0 15px 0; line-height: 1.5;">SaaS Business Intelligence mode is active.</p>
                <a href="premium_dashboard.php" style="background: linear-gradient(135deg, #059669, #10b981); color: #ffffff; padding: 10px 15px; border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; width: 100%; text-align: center; box-sizing: border-box;">View Charts</a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include('footer.php'); ?>