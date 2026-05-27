<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

/* CHECK VENDOR APPROVAL */
$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../../auth/login.php");
    exit;
}

/* HANDLE VENDOR STATUS UPDATE */
if (isset($_GET['action']) && isset($_GET['booking_vendor_id'])) {

    $booking_vendor_id = $_GET['booking_vendor_id'];
    $action = $_GET['action'];

    /* Make sure this job belongs to this vendor and booking is confirmed */
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

/* GET VENDOR JOBS (limited fields for vendor view) */
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Jobs</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<?php include('../header.php'); ?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">My Jobs</h2>
            <p class="muted">View and update your job progress</p>
        </div>
    </div>

    <?php if (mysqli_num_rows($jobs) == 0) { ?>
        <div class="empty-state">
            <h3>No jobs yet</h3>
            <p>Your bookings will appear here once customers select your service.</p>
        </div>
    <?php } ?>

    <div class="booking-list">

        <?php while($job = mysqli_fetch_assoc($jobs)) { ?>

            <div class="booking-card">

                <div class="booking-card-header">
                    <div>
                        <h3>Booking #<?= $job['booking_id'] ?></h3>
                        <p class="muted"><?= htmlspecialchars($job['company_name']) ?></p>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <div style="text-align:right;">
                            <div style="font-size:13px; color:#718096;">Event Date</div>
                            <div style="font-weight:700;"><?= htmlspecialchars($job['event_date']) ?></div>
                        </div>

                        <div>
                            <span class="status-badge status-<?= strtolower($job['status']) ?>">Booking: <?= $job['status'] ?></span>
                            <br>
                            <span class="status-badge vendor-status-<?= strtolower(str_replace(' ', '-', $job['vendor_status'])) ?>">Job: <?= $job['vendor_status'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="booking-info-grid">
                    <div>
                        <p><strong>Customer</strong></p>
                        <p><?= htmlspecialchars($job['customer_name']) ?></p>
                    </div>

                    <div>
                        <p><strong>Email</strong></p>
                        <p><?= htmlspecialchars($job['customer_email']) ?></p>
                    </div>

                    <div>
                        <p><strong>Service</strong></p>
                        <p><?= htmlspecialchars($job['vendor_service_type'] ?? 'Service') ?></p>
                    </div>

                    <div>
                        <p><strong>Price</strong></p>
                        <p>RM <?= number_format($job['vendor_price'], 2) ?></p>
                    </div>

                    <div style="grid-column: 1 / -1; display:flex; gap:12px; align-items:center;">
                        <?php if ($job['status'] == 'Pending') { ?>
                            <div class="alert warning">This booking is pending approval. Actions unavailable.</div>
                        <?php } elseif ($job['status'] == 'Rejected') { ?>
                            <div class="alert error">This booking was rejected. No action required.</div>
                        <?php } elseif ($job['status'] == 'Confirmed') { ?>
                            <?php if ($job['vendor_status'] == 'Confirmed') { ?>
                                <a href="index.php?action=start&booking_vendor_id=<?= $job['booking_vendor_id'] ?>" class="btn btn-primary" onclick="return confirm('Start this job?');">Start Job</a>
                            <?php } elseif ($job['vendor_status'] == 'In Progress') { ?>
                                <a href="index.php?action=complete&booking_vendor_id=<?= $job['booking_vendor_id'] ?>" class="btn btn-success" onclick="return confirm('Mark this job as completed?');">Mark Completed</a>
                            <?php } elseif ($job['vendor_status'] == 'Completed') { ?>
                                <div class="alert success">This job has been completed.</div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>