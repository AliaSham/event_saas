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
$plan = strtolower($companyData['plan'] ?? 'basic'); 
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
    DATA RETRIEVAL QUERIES (HANYA UNTUK PREMIUM)
======================================================== */
if ($plan === 'premium') {
    // 1. Kira pending booking
    $query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND LOWER(status) = 'pending'");
    $data_pending = mysqli_fetch_assoc($query_pending);
    $total_pending = $data_pending['total'] ?? 0;

    // 2. Kira active booking
    $query_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id = '$company_id' AND (LOWER(status) = 'approved' OR LOWER(status) = 'confirmed')");
    $data_active = mysqli_fetch_assoc($query_active);
    $total_active = $data_active['total'] ?? 0;

    // 3. Kira Total Revenue
    $query_revenue = mysqli_query($conn, "
        SELECT SUM(total_price) as total_revenue 
        FROM bookings 
        WHERE company_id = '$company_id' 
        AND (LOWER(payment_status) = 'paid' OR LOWER(payment_status) = 'partial')
    ");
    $data_revenue = mysqli_fetch_assoc($query_revenue);
    $total_revenue = $data_revenue['total_revenue'] ?? 0;

    // 4. Jumlah Pakej Terdaftar
    $query_packages_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM packages WHERE company_id = '$company_id'");
    $data_packages_count = mysqli_fetch_assoc($query_packages_count);
    $total_packages = $data_packages_count['total'] ?? 0;

    // 5. Tarik 5 booking terkini
    $recent_bookings = mysqli_query($conn, "
        SELECT b.*, p.package_name, u.name as customer_name 
        FROM bookings b
        LEFT JOIN packages p ON b.package_id = p.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.company_id = '$company_id' 
        ORDER BY b.id DESC 
        LIMIT 5
    ");

    // 6. Tarik 3 Pakej Paling Laris
    $top_packages = mysqli_query($conn, "
        SELECT package_id, COUNT(id) as total_sales
        FROM bookings
        WHERE company_id = '$company_id'
        GROUP BY package_id
        ORDER BY total_sales DESC
        LIMIT 3
    ");
}
?>

<?php 
// Panggil header utama di paling atas supaya sidebar sentiasa kekal ada
include('header.php'); 
?>

<?php if ($plan === 'basic' || $plan === 'standard'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> 

    <style>
        .basic-dashboard-wrapper {
            padding: 30px 24px;
            max-width: 1140px;
            margin: 0 auto;
            font-family: 'Poppins', sans-serif;
        }
        .expiry-banner {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-size: 14px;
        }
        .company-id-tag {
            font-size: 14px;
            color: #718096;
            margin: 5px 0;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .admin-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 24px !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02) !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>

    <div class="basic-dashboard-wrapper">
        <h2 style="font-weight: 600; color: #1a202c; margin-bottom: 15px;">Welcome, Admin</h2>

        <?php if (!empty($expiry_alert_message)): ?>
            <div class="expiry-banner"><?= $expiry_alert_message ?></div>
        <?php endif; ?>

        <p class="company-id-tag">Company ID: <?= $company_id ?></p>
        <p class="company-id-tag">Current Plan: <b><?= strtoupper($plan) ?></b></p>

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
                    <h3>Manage Bookings</h3>
                    <p>Review customer bookings and approve or reject requests.</p>
                </div>
                <a href="bookings/index.php" class="btn btn-primary">Open</a>
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

            <div class="admin-card">
                <div>
                    <div class="card-icon">💳</div>
                    <h3>Manage Payments</h3>
                    <p>View deposits, payment status, and proof of payments.</p>
                </div>
                <a href="payment_management.php" class="btn btn-primary">Open</a>
            </div>

            <div class="admin-card">
                <div>
                    <div class="card-icon">🔔</div>
                    <h3>Manage Notifications</h3>
                    <p>View all payment and booking notifications.</p>
                </div>
                <a href="admin_notifications.php" class="btn btn-primary">Open</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-container { max-width: 1180px; margin: 0 auto; padding: 35px 24px 60px; font-family: 'Poppins', sans-serif; }
        .dashboard-welcome {
            padding: 28px 28px 24px; border-radius: 20px; background: #ffffff !important;
            border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); margin-bottom: 35px;
        }
        .dashboard-welcome h2 { color: #1a202c !important; font-weight: 700; margin: 0; font-size: 32px; }
        .dashboard-welcome p { color: #718096 !important; margin: 10px 0 0 0; font-size: 15px; line-height: 1.7; }
        .glowing-banner {
            background: #fffaf0; border: 1px solid #feebc8; color: #dd6b20;
            border-radius: 16px; padding: 18px 22px; margin-bottom: 35px;
            font-size: 14px; display: flex; align-items: center; gap: 14px;
        }
        .stats-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card-glass {
            background: #e6f4ea !important; border: 1px solid #cce8d5;
            border-radius: 20px; padding: 22px; display: flex;
            justify-content: space-between; align-items: center; transition: transform 0.22s ease;
        }
        .stat-card-glass:hover { transform: translateY(-4px); }
        .stat-label { font-size: 11px; color: #526355; text-transform: uppercase; font-weight: 600; letter-spacing: 0.8px; }
        .dashboard-split-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start; }
        .panel-glass {
            background: #ffffff !important; border: 1px solid #e2e8f0;
            border-radius: 20px; padding: 28px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
        }
        .panel-title { font-size: 16px; font-weight: 700; color: #1a202c; margin: 0; display: flex; align-items: center; gap: 12px; }
        .badge-status { padding: 6px 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }
        .badge-pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .action-btn-link {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 18px;
            border-radius: 14px; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .action-btn-link:hover { background: #f1f5f9; color: #1e293b; transform: translateX(4px); }
        @media (max-width: 1024px) {
            .stats-container { grid-template-columns: repeat(2, 1fr); }
            .dashboard-split-layout { grid-template-columns: 1fr; }
        }
    </style>

    <div class="dashboard-container">
        <div class="dashboard-welcome">
            <h2>Welcome, Premium Admin</h2>
            <p>An easy way to manage sales and event operations with care and precision.</p>
        </div>

        <?php if (!empty($expiry_alert_message)): ?>
            <div class="glowing-banner">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= $expiry_alert_message ?></div>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card-glass" style="border-left: 4px solid #d97706;">
                <div>
                    <span class="stat-label">Pending Action</span>
                    <div style="font-size: 28px; font-weight: 700; color: #1a202c; margin-top: 5px; line-height: 1;">
                        <span style="color: #b45309;"><?= $total_pending ?></span>
                        <span style="font-size: 12px; font-weight: 500; color: #718096; margin-left: 3px;">Bookings</span>
                    </div>
                </div>
                <div style="font-size: 16px; color: #b45309; background: rgba(217, 119, 6, 0.1); width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>

            <div class="stat-card-glass" style="border-left: 4px solid #059669;">
                <div>
                    <span class="stat-label">Active Events</span>
                    <div style="font-size: 28px; font-weight: 700; color: #1a202c; margin-top: 5px; line-height: 1;">
                        <span style="color: #047857;"><?= $total_active ?></span>
                        <span style="font-size: 12px; font-weight: 500; color: #718096; margin-left: 3px;">Confirmed</span>
                    </div>
                </div>
                <div style="font-size: 16px; color: #047857; background: rgba(5, 150, 105, 0.1); width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>

            <div class="stat-card-glass" style="border-left: 4px solid #10b981; background: #eafaf1 !important;">
                <div>
                    <span class="stat-label">Total Earnings</span>
                    <div style="font-size: 24px; font-weight: 700; color: #111827; margin-top: 5px; line-height: 1;">
                        <span style="color: #059669;">RM <?= number_format($total_revenue, 2) ?></span>
                    </div>
                </div>
                <div style="font-size: 16px; color: #059669; background: rgba(16, 185, 129, 0.1); width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="stat-card-glass" style="border-left: 4px solid #2563eb;">
                <div>
                    <span class="stat-label">Subscription</span>
                    <div style="font-size: 20px; font-weight: 700; color: #1d4ed8; margin-top: 8px; line-height: 1;">
                        <?= strtoupper($plan) ?>
                    </div>
                </div>
                <div style="font-size: 16px; color: #1d4ed8; background: rgba(37, 99, 235, 0.1); width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-crown"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-split-layout">
            <div class="main-analytics-area panel-glass">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 class="panel-title"><i class="fa-solid fa-list-check" style="color: #059669;"></i> Live Booking Activity Pipeline</h3>
                    <a href="bookings/index.php" style="color: #2563eb; text-decoration: none; font-size: 12px; font-weight: 600;">View All <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i></a>
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
                                    <span class="badge-status <?= (strtolower($row['status'] ?? 'pending') == 'pending') ? 'badge-pending' : 'badge-approved' ?>">
                                        <?= ucfirst($row['status'] ?? 'Pending') ?>
                                    </span>
                                    <a href="bookings/index.php" style="color: #cbd5e1;"><i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i></a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 20px;"><h4 style="margin: 0; font-size: 14px; color: #718096;">No Recent Bookings Placed</h4></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="side-shortcut-area" style="display: flex; flex-direction: column; gap: 25px;">
                <div class="panel-glass">
                    <h3 class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-bolt" style="color: #d97706;"></i> System Management</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <a href="bookings/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#059669'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <span><i class="fa-solid fa-plus-circle" style="margin-right: 6px; color: #059669;"></i> Review Applications</span>
                            <i class="fa-solid fa-arrow-right-long" style="color: #059669;"></i>
                        </a>
                        <a href="calendar/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#ec4899'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <span><i class="fa-solid fa-calendar-minus" style="margin-right: 6px; color: #ec4899;"></i> Config Locked Dates</span>
                            <i class="fa-solid fa-arrow-right-long" style="color: #ec4899;"></i>
                        </a>
                        <a href="package/index.php" class="action-btn-link" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <span><i class="fa-solid fa-box-open" style="margin-right: 6px; color: #2563eb;"></i> Inventory Packages (<?= $total_packages ?>)</span>
                            <i class="fa-solid fa-arrow-right-long" style="color: #2563eb;"></i>
                        </a>
                    </div>
                </div>

                <div class="panel-glass">
                    <h3 class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Top Demand Packages</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if(mysqli_num_rows($top_packages) > 0): ?>
                            <?php $rank = 1; while($pkg = mysqli_fetch_assoc($top_packages)): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-weight: 700; color: #64748b; font-size: 13px;">#<?= $rank++ ?></span>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 600; color: #1a202c;">Package ID: #<?= $pkg['package_id'] ?></div>
                                            <div style="font-size: 11px; color: #94a3b8;">SaaS Managed Item</div>
                                        </div>
                                    </div>
                                    <span style="background: #fee2e2; color: #ef4444; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 8px;"><?= $pkg['total_sales'] ?> Orders</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px 0;">No purchase records found yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel-glass" style="border: 1px solid #a7f3d0; background: linear-gradient(135deg, #f0fdf4, #ffffff) !important; padding: 22px;">
                    <h3 class="panel-title" style="color: #047857; margin-bottom: 6px;"><i class="fa-solid fa-chart-line"></i> Premium Insights</h3>
                    <p style="color: #4a5568; font-size: 12px; margin: 0 0 15px 0; line-height: 1.5;">SaaS Business Intelligence mode is active.</p>
                    <a href="premium_dashboard.php" style="background: linear-gradient(135deg, #059669, #10b981); color: #ffffff; padding: 10px 15px; border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; width: 100%; text-align: center; box-sizing: border-box;">View Charts</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php 
// Panggil footer di bahagian paling bawah sekali
include('footer.php'); 
?>