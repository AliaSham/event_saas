<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* GET CUSTOMER BOOKINGS */
$bookings = mysqli_query($conn, "
    SELECT 
        b.*,
        c.company_name,
        p.package_name,
        p.pax,
        p.menu,
        p.description AS package_description
    FROM bookings b
    JOIN companies c ON b.company_id = c.id
    JOIN packages p ON b.package_id = p.id
    WHERE b.user_id = '$user_id'
    ORDER BY b.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="customer-navbar">
    <div class="brand">
        Event Booking
    </div>

    <div>
        <a href="dashboard.php">Browse</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="account.php">My Account</a>
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">My Bookings</h2>
            <p class="muted">View your event booking status and selected add-ons</p>
        </div>
    </div>

    <?php if (mysqli_num_rows($bookings) == 0) { ?>
        <div class="empty-state">
            <h3>No bookings yet</h3>
            <p>You have not made any booking yet.</p>
            <a href="dashboard.php" class="btn btn-primary">Browse Companies</a>
        </div>
    <?php } ?>

    <div class="booking-list">

        <?php while($booking = mysqli_fetch_assoc($bookings)) { ?>

            <?php
            $booking_id = $booking['id'];

            /* GET ADD-ON / VENDOR FOR THIS BOOKING */
            $vendorQ = mysqli_query($conn, "
                SELECT 
                    bv.price,
                    u.name AS vendor_name,
                    vp.service_type
                FROM booking_vendors bv
                JOIN users u ON bv.vendor_id = u.id
                JOIN vendor_profiles vp ON u.id = vp.user_id
                WHERE bv.booking_id = '$booking_id'
            ");
            ?>

            <div class="booking-card">

                <div class="booking-card-header">

                    <div>
                        <h3><?= $booking['company_name'] ?></h3>
                        <p class="muted">
                            Booking ID: #<?= $booking['id'] ?>
                        </p>
                    </div>

                    <span class="status-badge status-<?= strtolower($booking['status']) ?>">
                        <?= $booking['status'] ?>
                    </span>

                </div>

                <div class="booking-info-grid">

                    <div>
                        <p><strong>Event Date:</strong></p>
                        <p><?= $booking['event_date'] ?></p>
                    </div>

                    <div>
                        <p><strong>Package:</strong></p>
                        <p><?= $booking['package_name'] ?></p>
                    </div>

                    <div>
                        <p><strong>Pax:</strong></p>
                        <p><?= $booking['pax'] ?> pax</p>
                    </div>

                    <div>
                        <p><strong>Total Price:</strong></p>
                        <p>RM <?= number_format($booking['total_price'], 2) ?></p>
                    </div>

                </div>

                <div class="booking-section">
                    <p><strong>Package Description:</strong></p>
                    <p><?= $booking['package_description'] ?></p>
                </div>

                <div class="booking-section">
                    <p><strong>Menu:</strong></p>
                    <p><?= $booking['menu'] ?></p>
                </div>

                <div class="booking-section">
                    <p><strong>Selected Add-On / Vendor:</strong></p>

                    <?php if (mysqli_num_rows($vendorQ) == 0) { ?>

                        <p>No add-on selected.</p>

                    <?php } else { ?>

                        <div class="addon-list">

                            <?php while($vendor = mysqli_fetch_assoc($vendorQ)) { ?>

                                <div class="addon-item">
                                    <div>
                                        <strong><?= $vendor['vendor_name'] ?></strong>
                                        <p><?= $vendor['service_type'] ?></p>
                                    </div>

                                    <span>
                                        RM <?= number_format($vendor['price'], 2) ?>
                                    </span>
                                </div>

                            <?php } ?>

                        </div>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>