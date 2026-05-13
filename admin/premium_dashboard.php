<?php
session_start();
include('../config/db.php');

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* =========================
   DATE FILTER
========================= */

$filter = $_GET['filter'] ?? 'month';

$dateCondition = "";

if($filter == '7days'){

    $dateCondition = "AND event_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

}
elseif($filter == '30days'){

    $dateCondition = "AND event_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

}
elseif($filter == 'month'){

    $dateCondition = "AND MONTH(event_date) = MONTH(NOW())
                      AND YEAR(event_date) = YEAR(NOW())";

}
elseif($filter == 'year'){

    $dateCondition = "AND YEAR(event_date) = YEAR(NOW())";

}

/* =========================
   PLAN SECURITY
========================= */

$companyQuery = mysqli_query($conn, "
    SELECT plan
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);

if($companyData['plan'] != 'premium'){
    die("Access Denied");
}

/* =========================
   MONTHLY REVENUE DATA
========================= */

$months = [];
$revenues = [];

$chartQuery = mysqli_query($conn, "
    SELECT 
        MONTH(event_date) as month_num,
        MONTHNAME(event_date) as month_name,
        SUM(total_price) as total_revenue

    FROM bookings

    WHERE company_id = '$company_id'
    $dateCondition

    GROUP BY MONTH(event_date)

    ORDER BY MONTH(event_date)
");

while($row = mysqli_fetch_assoc($chartQuery)) {

    $months[] = $row['month_name'];
    $revenues[] = $row['total_revenue'];
}

/* =========================
   TOTAL BOOKINGS
========================= */

$bookingQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total

    FROM bookings

    WHERE company_id = '$company_id'
    $dateCondition
");

$bookingData = mysqli_fetch_assoc($bookingQuery);

/* =========================
   TOTAL REVENUE
========================= */

$revenueQuery = mysqli_query($conn, "
    SELECT SUM(total_price) as revenue

    FROM bookings

    WHERE company_id = '$company_id'
    $dateCondition

    AND status = 'Approved'
");

$revenueData = mysqli_fetch_assoc($revenueQuery);

/* =========================
   TOTAL VENDORS
========================= */

$vendorQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total

    FROM company_vendors

    WHERE company_id = '$company_id'
");

$vendorData = mysqli_fetch_assoc($vendorQuery);

/* =========================
   PENDING BOOKINGS
========================= */

$pendingQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total

    FROM bookings

    WHERE company_id = '$company_id'
    $dateCondition

    AND status = 'Pending'
");

$pendingData = mysqli_fetch_assoc($pendingQuery);

/* =========================
   TOP SELLING PACKAGES
========================= */

$topPackagesQuery = mysqli_query($conn, "
    SELECT
        packages.package_name,
        COUNT(bookings.id) as total_bookings

    FROM bookings

    JOIN packages
    ON packages.id = bookings.package_id

    WHERE bookings.company_id = '$company_id'
    $dateCondition

    GROUP BY bookings.package_id

    ORDER BY total_bookings DESC

    LIMIT 5
");

/* =========================
   RECENT BOOKINGS
========================= */

$recentBookingsQuery = mysqli_query($conn, "
    SELECT 
        bookings.event_date,
        bookings.status,
        bookings.total_price,
        users.name as customer_name,
        packages.package_name as package_name

    FROM bookings

    JOIN users
    ON users.id = bookings.user_id

    JOIN packages
    ON packages.id = bookings.package_id

    WHERE bookings.company_id = '$company_id'
    $dateCondition

    ORDER BY bookings.id DESC

    LIMIT 5
");

/* =========================
   BOOKING STATUS ANALYTICS
========================= */

$statusLabels = [];
$statusTotals = [];

$statusQuery = mysqli_query($conn, "
    SELECT 
        status,
        COUNT(*) as total

    FROM bookings

    WHERE company_id = '$company_id'
    $dateCondition

    GROUP BY status
");

while($row = mysqli_fetch_assoc($statusQuery)){

    $statusLabels[] = $row['status'];
    $statusTotals[] = $row['total'];
}

/* =========================
   COMPLETED VENDOR JOBS
========================= */

$completedVendorQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM booking_vendors
    WHERE vendor_status = 'Completed'
");

$completedVendorData = mysqli_fetch_assoc($completedVendorQuery);

/* =========================
   IN PROGRESS JOBS
========================= */

$inProgressQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM booking_vendors
    WHERE vendor_status = 'In Progress'
");

$inProgressData = mysqli_fetch_assoc($inProgressQuery);

/* =========================
   TOP VENDORS
========================= */

$topVendorsQuery = mysqli_query($conn, "
    SELECT 
        vendor_profiles.service_type,
        COUNT(booking_vendors.id) as total_jobs
    FROM booking_vendors

    JOIN vendor_profiles
    ON vendor_profiles.user_id = booking_vendors.vendor_id

    GROUP BY booking_vendors.vendor_id

    ORDER BY total_jobs DESC

    LIMIT 5
");

?>

<!DOCTYPE html>
<html>

<head>

    <title>Premium Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body{
            background: #f4f7fb;
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
        }

        .dashboard-header h2{
            font-size:36px;
            margin-bottom:5px;
            color:#1e293b;
        }

        .dashboard-header p{
            color:#64748b;
        }

        .filter-box select{
            padding:12px 18px;
            border-radius:12px;
            border:none;
            background:white;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
            font-size:14px;
        }

        .admin-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
            gap:25px;
        }

        .admin-card{
            background:white;
            border-radius:22px;
            padding:30px;
            box-shadow:0 8px 30px rgba(0,0,0,0.06);
            transition:0.3s;
        }

        .admin-card:hover{
            transform:translateY(-5px);
        }

        .card-icon{
            font-size:38px;
            margin-bottom:15px;
        }

        .admin-card h3{
            margin-bottom:15px;
            font-size:24px;
            color:#0f172a;
        }

        .metric-number{
            font-size:34px;
            font-weight:700;
            color:#2563eb;
        }

        .metric-label{
            margin-top:8px;
            color:#64748b;
            font-size:14px;
        }

        .chart-card{
            margin-top:35px;
        }

        .chart-container{
            position:relative;
            width:100%;
            height:350px;
            margin-top:20px;
        }

        .analytics-table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        .analytics-table th{
            background:#2563eb;
            color:white;
            padding:14px;
            text-align:left;
        }

        .analytics-table td{
            padding:14px;
            border-bottom:1px solid #e2e8f0;
        }

        .analytics-table tr:hover{
            background:#f8fafc;
        }

        .btn-primary{
            background:#2563eb;
            color:white;
            padding:12px 20px;
            border-radius:12px;
            text-decoration:none;
            font-weight:600;
        }

    </style>

</head>

<body>

<div class="navbar">

    <div>
        <b>Premium Dashboard</b>
    </div>

    <div>
        <a href="dashboard.php" class="btn-primary">
            Back
        </a>
    </div>

</div>

<div class="container">

    <div class="dashboard-header">

        <div>
            <h2>Business Analytics</h2>
            <p>Monitor revenue, bookings and business performance.</p>
        </div>

        <div class="filter-box">

            <form method="GET">

                <select name="filter" onchange="this.form.submit()">

                    <option value="7days" <?= $filter == '7days' ? 'selected' : '' ?>>
                        Last 7 Days
                    </option>

                    <option value="30days" <?= $filter == '30days' ? 'selected' : '' ?>>
                        Last 30 Days
                    </option>

                    <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>
                        This Month
                    </option>

                    <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>
                        This Year
                    </option>

                </select>

            </form>

        </div>

    </div>

    <div class="admin-grid">

        <!-- BOOKINGS -->
        <div class="admin-card">

            <div class="card-icon">📋</div>

            <h3>Total Bookings</h3>

            <div class="metric-number">
                <?= $bookingData['total'] ?>
            </div>

            <div class="metric-label">
                Total bookings recorded
            </div>

        </div>

        <!-- REVENUE -->
        <div class="admin-card">

            <div class="card-icon">💰</div>

            <h3>Total Revenue</h3>

            <div class="metric-number">
                RM <?= number_format($revenueData['revenue'] ?? 0, 2) ?>
            </div>

            <div class="metric-label">
                Approved booking revenue
            </div>

        </div>

        <!-- VENDORS -->
        <div class="admin-card">

            <div class="card-icon">🤝</div>

            <h3>Total Vendors</h3>

            <div class="metric-number">
                <?= $vendorData['total'] ?>
            </div>

            <div class="metric-label">
                Registered vendors
            </div>

        </div>

        <!-- PENDING -->
        <div class="admin-card">

            <div class="card-icon">⏳</div>

            <h3>Pending Bookings</h3>

            <div class="metric-number">
                <?= $pendingData['total'] ?>
            </div>

            <div class="metric-label">
                Awaiting approval
            </div>

        </div>


    <!-- COMPLETED VENDOR JOBS -->
<div class="admin-card">

    <div class="card-icon">✅</div>

    <h3>Completed Vendor Jobs</h3>

    <div class="metric-number">
        <?= $completedVendorData['total'] ?>
    </div>

    <div class="metric-label">
        Vendor tasks completed
    </div>

</div>

<!-- IN PROGRESS JOBS -->
<div class="admin-card">

    <div class="card-icon">🚧</div>

    <h3>Jobs In Progress</h3>

    <div class="metric-number">
        <?= $inProgressData['total'] ?>
    </div>

    <div class="metric-label">
        Active vendor jobs
    </div>

</div>

    <!-- REVENUE CHART -->
    <div class="admin-card chart-card">

        <div class="card-icon">📈</div>

        <h3>Monthly Revenue Analytics</h3>

        <div class="chart-container">
            <canvas id="revenueChart"></canvas>
        </div>

    </div>

    <!-- STATUS CHART -->
    <div class="admin-card chart-card">

        <div class="card-icon">🥧</div>

        <h3>Booking Status Analytics</h3>

        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>

    </div>

    <!-- TOP PACKAGES -->
    <div class="admin-card chart-card">

        <div class="card-icon">🔥</div>

        <h3>Top Selling Packages</h3>

        <table class="analytics-table">

            <tr>
                <th>Package</th>
                <th>Total Bookings</th>
            </tr>

            <?php while($package = mysqli_fetch_assoc($topPackagesQuery)) { ?>

            <tr>
                <td><?= $package['package_name'] ?></td>
                <td><?= $package['total_bookings'] ?></td>
            </tr>

            <?php } ?>

        </table>

    </div>

    <!-- RECENT BOOKINGS -->
    <div class="admin-card chart-card">

        <div class="card-icon">📋</div>

        <h3>Recent Bookings</h3>

        <table class="analytics-table">

            <tr>
                <th>Customer</th>
                <th>Package</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
            </tr>

            <?php while($booking = mysqli_fetch_assoc($recentBookingsQuery)) { ?>

            <tr>

                <td><?= $booking['customer_name'] ?></td>

                <td><?= $booking['package_name'] ?></td>

                <td><?= $booking['event_date'] ?></td>

                <td><?= $booking['status'] ?></td>

                <td>
                    RM <?= number_format($booking['total_price'], 2) ?>
                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

<!-- TOP VENDORS -->
<div class="admin-card chart-card">

    <div class="card-icon">🏆</div>

    <h3>Top Vendor Performance</h3>

    <table class="analytics-table">

        <tr>
            <th>Vendor Service</th>
            <th>Total Jobs</th>
        </tr>

        <?php while($vendor = mysqli_fetch_assoc($topVendorsQuery)) { ?>

        <tr>
            <td><?= $vendor['service_type'] ?></td>
            <td><?= $vendor['total_jobs'] ?></td>
        </tr>

        <?php } ?>

    </table>

</div>

<script>

const revenueCtx = document.getElementById('revenueChart');

new Chart(revenueCtx, {

    type: 'bar',

    data: {

        labels: <?= json_encode($months) ?>,

        datasets: [{

            label: 'Monthly Revenue (RM)',

            data: <?= json_encode($revenues) ?>,

            borderRadius: 10,
            borderWidth: 0

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false

    }

});

const statusCtx = document.getElementById('statusChart');

new Chart(statusCtx, {

    type: 'doughnut',

    data: {

        labels: <?= json_encode($statusLabels) ?>,

        datasets: [{

            data: <?= json_encode($statusTotals) ?>

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false

    }

});

</script>
</div>

</body>
</html>