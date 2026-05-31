<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../../auth/login.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['booking_vendor_id'])) {

    $booking_vendor_id = $_GET['booking_vendor_id'];
    $action = $_GET['action'];

    $checkQ = mysqli_query($conn, "
        SELECT bv.*, b.status AS booking_status
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.id='$booking_vendor_id'
        AND bv.vendor_id='$vendor_id'
    ");

    if (mysqli_num_rows($checkQ) > 0) {

        $job = mysqli_fetch_assoc($checkQ);

        if ($job['booking_status'] == 'Confirmed') {

            if ($action == 'start') {
                mysqli_query($conn, "
                    UPDATE booking_vendors
                    SET vendor_status='In Progress'
                    WHERE id='$booking_vendor_id'
                    AND vendor_id='$vendor_id'
                ");
            }

            if ($action == 'complete') {
                mysqli_query($conn, "
                    UPDATE booking_vendors
                    SET vendor_status='Completed'
                    WHERE id='$booking_vendor_id'
                    AND vendor_id='$vendor_id'
                ");
            }
        }
    }

    header("Location: index.php");
    exit;
}

$jobs = mysqli_query($conn, "
    SELECT 
        bv.id AS booking_vendor_id,
        bv.price AS vendor_price,
        bv.vendor_status,

        b.id AS booking_id,
        b.event_date,
        b.status,

        c.company_name,

        u.name AS customer_name,
        u.email AS customer_email,

        vp.service_type AS vendor_service_type
    FROM booking_vendors bv
    JOIN bookings b ON bv.booking_id = b.id
    JOIN companies c ON b.company_id = c.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN vendor_profiles vp ON bv.vendor_id = vp.user_id
    WHERE bv.vendor_id = '$vendor_id'
    ORDER BY b.event_date DESC
");

$totalJobs = mysqli_num_rows($jobs);
$pendingCount = 0;
$progressCount = 0;
$completedCount = 0;
$totalEarnings = 0;

$jobRows = [];

while ($row = mysqli_fetch_assoc($jobs)) {
    $jobRows[] = $row;

    if ($row['vendor_status'] == 'Confirmed') {
        $pendingCount++;
    }

    if ($row['vendor_status'] == 'In Progress') {
        $progressCount++;
    }

    if ($row['vendor_status'] == 'Completed') {
        $completedCount++;
        $totalEarnings += $row['vendor_price'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Jobs</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #0f172a;
        }

        .jobs-wrapper {
            padding: 38px 55px;
        }

        .jobs-hero {
            background: linear-gradient(135deg, #0f766e, #14b8a6, #2563eb);
            color: white;
            padding: 35px 40px;
            border-radius: 28px;
            box-shadow: 0 18px 45px rgba(15,118,110,0.25);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .jobs-hero::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
        }

        .jobs-hero h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }

        .jobs-hero p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: .95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 14px 35px rgba(15,23,42,0.07);
            border: 1px solid #e5e7eb;
        }

        .stat-card span {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-card h2 {
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 800;
        }

        .section-title {
            margin: 10px 0 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title h2 {
            margin: 0;
            font-size: 24px;
        }

        .section-title p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .job-list {
            display: grid;
            gap: 22px;
        }

        .job-card {
            background: white;
            border-radius: 26px;
            padding: 28px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 14px 35px rgba(15,23,42,0.08);
            transition: .25s;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 45px rgba(15,23,42,0.12);
        }

        .job-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .job-title h3 {
            margin: 0;
            font-size: 21px;
        }

        .job-title p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .badge-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .badge {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-pending {
            background: #fff7ed;
            color: #9a3412;
        }

        .status-confirmed {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-rejected {
            background: #fef2f2;
            color: #991b1b;
        }

        .vendor-confirmed {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .vendor-in-progress {
            background: #fef9c3;
            color: #854d0e;
        }

        .vendor-completed {
            background: #dcfce7;
            color: #166534;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px;
        }

        .info-box span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .info-box strong {
            font-size: 14px;
            color: #0f172a;
        }

        .action-area {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-action {
            border: none;
            text-decoration: none;
            padding: 13px 20px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 800;
            transition: .25s;
            color: white;
        }

        .btn-start {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
        }

        .btn-complete {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(37,99,235,0.25);
        }

        .notice {
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .notice.warning {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        .notice.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .notice.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .empty-state {
            background: white;
            border-radius: 28px;
            padding: 70px 30px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 14px 35px rgba(15,23,42,0.07);
        }

        .empty-icon {
            width: 85px;
            height: 85px;
            margin: 0 auto 20px;
            border-radius: 25px;
            background: #ecfeff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
        }

        .empty-state h3 {
            margin: 0;
            font-size: 24px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 14px;
            margin-top: 10px;
        }

        @media(max-width: 1000px) {
            .jobs-wrapper {
                padding: 25px;
            }

            .stats-grid,
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .job-top {
                flex-direction: column;
            }

            .badge-wrap {
                justify-content: flex-start;
            }
        }

        @media(max-width: 650px) {
            .stats-grid,
            .info-grid {
                grid-template-columns: 1fr;
            }

            .jobs-hero h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<?php include('../header.php'); ?>

<div class="jobs-wrapper">

    <div class="jobs-hero">
        <h1>My Jobs</h1>
        <p>Track your event bookings, update job progress and manage customer service requests.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span>Total Jobs</span>
            <h2><?= $totalJobs ?></h2>
        </div>

        <div class="stat-card">
            <span>Pending / Confirmed</span>
            <h2><?= $pendingCount ?></h2>
        </div>

        <div class="stat-card">
            <span>In Progress</span>
            <h2><?= $progressCount ?></h2>
        </div>

        <div class="stat-card">
            <span>Completed Earnings</span>
            <h2>RM <?= number_format($totalEarnings, 2) ?></h2>
        </div>
    </div>

    <div class="section-title">
        <div>
            <h2>Job Bookings</h2>
            <p>Your customer bookings will appear below.</p>
        </div>
    </div>

    <?php if ($totalJobs == 0) { ?>

        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No jobs yet</h3>
            <p>Your bookings will appear here once customers select your service.</p>
        </div>

    <?php } else { ?>

        <div class="job-list">

            <?php foreach($jobRows as $job) { ?>

                <div class="job-card">

                    <div class="job-top">
                        <div class="job-title">
                            <h3>Booking #<?= $job['booking_id'] ?></h3>
                            <p><?= htmlspecialchars($job['company_name']) ?></p>
                        </div>

                        <div class="badge-wrap">
                            <span class="badge status-<?= strtolower($job['status']) ?>">
                                Booking: <?= htmlspecialchars($job['status']) ?>
                            </span>

                            <span class="badge vendor-<?= strtolower(str_replace(' ', '-', $job['vendor_status'])) ?>">
                                Job: <?= htmlspecialchars($job['vendor_status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-grid">

                        <div class="info-box">
                            <span>Event Date</span>
                            <strong><?= htmlspecialchars($job['event_date']) ?></strong>
                        </div>

                        <div class="info-box">
                            <span>Customer</span>
                            <strong><?= htmlspecialchars($job['customer_name']) ?></strong>
                        </div>

                        <div class="info-box">
                            <span>Email</span>
                            <strong><?= htmlspecialchars($job['customer_email']) ?></strong>
                        </div>

                        <div class="info-box">
                            <span>Service</span>
                            <strong><?= htmlspecialchars($job['vendor_service_type'] ?? 'Service') ?></strong>
                        </div>

                        <div class="info-box">
                            <span>Price</span>
                            <strong>RM <?= number_format($job['vendor_price'], 2) ?></strong>
                        </div>

                    </div>

                    <div class="action-area">

                        <?php if ($job['status'] == 'Pending') { ?>

                            <div class="notice warning">
                                This booking is pending approval. Actions are not available yet.
                            </div>

                        <?php } elseif ($job['status'] == 'Rejected') { ?>

                            <div class="notice error">
                                This booking was rejected. No action required.
                            </div>

                        <?php } elseif ($job['status'] == 'Confirmed') { ?>

                            <?php if ($job['vendor_status'] == 'Confirmed') { ?>

                                <a href="index.php?action=start&booking_vendor_id=<?= $job['booking_vendor_id'] ?>"
                                   class="btn-action btn-start"
                                   onclick="return confirm('Start this job?');">
                                    Start Job
                                </a>

                            <?php } elseif ($job['vendor_status'] == 'In Progress') { ?>

                                <a href="index.php?action=complete&booking_vendor_id=<?= $job['booking_vendor_id'] ?>"
                                   class="btn-action btn-complete"
                                   onclick="return confirm('Mark this job as completed?');">
                                    Mark Completed
                                </a>

                            <?php } elseif ($job['vendor_status'] == 'Completed') { ?>

                                <div class="notice success">
                                    This job has been completed.
                                </div>

                            <?php } ?>

                        <?php } ?>

                    </div>

                </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

</body>
</html>