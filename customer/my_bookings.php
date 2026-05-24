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

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .payment-status-box {
            background: #f0f7ff;
            border-left: 4px solid #2196f3;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 13px;
        }

        .payment-status-box.pending {
            background: #fff3cd;
            border-color: #ffc107;
        }

        .payment-status-box.completed {
            background: #e8f5e9;
            border-color: #28a745;
        }

        .payment-action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .payment-action-buttons a,
        .payment-action-buttons button {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-pay-now {
            background: #ff6b6b;
            color: white;
        }

        .btn-pay-now:hover {
            background: #ee5a52;
        }

        .btn-view-payment {
            background: #6c757d;
            color: white;
        }

        .btn-view-payment:hover {
            background: #5a6268;
        }

        .alert-message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #e8f5e9;
            border-left: 4px solid #28a745;
            color: #2e7d32;
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">My Bookings</h2>
            <p class="muted">View your event booking status and selected add-ons</p>
        </div>
    </div>

    <?php if ($message == 'already_paid') { ?>
        <div class="alert-message">
            ✓ This booking has already been paid. No additional payment is required.
        </div>
    <?php } ?>

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

            /* GET PAYMENT STATUS */
            $paymentQ = mysqli_query($conn, "
                SELECT * FROM payments
                WHERE booking_id = '$booking_id'
            ");

            $payment = null;
            $deposit_amount = $booking['total_price'] * 0.10;
            $remaining_amount = $booking['total_price'] * 0.90;

            if (mysqli_num_rows($paymentQ) > 0) {
                $payment = mysqli_fetch_assoc($paymentQ);
            }
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

                <!-- Payment Status Section -->
                <?php if ($booking['status'] == 'Payment Pending') { ?>
                    <div class="payment-status-box pending">
                        <strong>⏳ Payment Pending</strong>
                        <p style="margin: 5px 0 0 0;">Deposit (10%): <strong>RM <?= number_format($deposit_amount, 2) ?></strong></p>
                        <p style="margin: 3px 0 0 0; font-size: 12px; color: #666;">Please complete the payment to confirm your booking.</p>
                        <div class="payment-action-buttons">
                            <a href="payment.php?booking_id=<?= $booking_id ?>" class="btn-pay-now">
                                💳 Pay Now
                            </a>
                        </div>
                    </div>
                <?php } elseif ($booking['payment_status'] == 'Partial' || $booking['payment_status'] == 'Paid') { ?>
                    <div class="payment-status-box completed">
                        <?php if ($booking['payment_status'] == 'Paid') { ?>
                            <strong>✅ Fully Paid</strong>
                            <p style="margin: 5px 0 0 0;">Total Amount Paid: <strong>RM <?= number_format($booking['total_price'], 2) ?></strong></p>
                        <?php } else { ?>
                            <strong>⏳ Deposit Paid - Balance Pending</strong>
                            <p style="margin: 5px 0 0 0;">
                                Deposit Paid: <strong style="color: #4caf50;">RM <?= number_format($deposit_amount, 2) ?></strong>
                            </p>
                            <p style="margin: 5px 0 0 0;">
                                Remaining Balance: <strong style="color: #ff6b6b;">RM <?= number_format($remaining_amount, 2) ?></strong>
                            </p>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Due before: <?= date('d M Y', strtotime($booking['event_date'])) ?></p>
                        <?php } ?>
                        <?php if ($payment) { ?>
                            <div class="payment-action-buttons">
                                <a href="payment_confirmation.php?payment_id=<?= $payment['id'] ?>" class="btn-view-payment">
                                    📋 View Payment Details
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

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