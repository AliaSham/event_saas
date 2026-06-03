<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking details
$bookingQ = mysqli_query($conn, "
    SELECT 
        b.*,
        c.company_name,
        c.phone as company_phone,
        c.email as company_email,
        p.package_name,
        p.pax,
        p.description,
        u.email as customer_email,
        u.name as customer_name,
        u.phone as customer_phone
    FROM bookings b
    JOIN companies c ON b.company_id = c.id
    JOIN packages p ON b.package_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = '$booking_id'
    AND b.user_id = '$user_id'
");

if (mysqli_num_rows($bookingQ) == 0) {
    header("Location: my_bookings.php");
    exit;
}

$booking = mysqli_fetch_assoc($bookingQ);

// Fetch payment details
$paymentQ = mysqli_query($conn, "
    SELECT * FROM payments
    WHERE booking_id = '$booking_id'
    ORDER BY id DESC
    LIMIT 1
");

$payment = (mysqli_num_rows($paymentQ) > 0) ? mysqli_fetch_assoc($paymentQ) : null;

// Fetch vendors
$vendorQ = mysqli_query($conn, "
    SELECT 
        bv.price,
        u.name AS vendor_name,
        u.email as vendor_email,
        vp.service_type
    FROM booking_vendors bv
    JOIN users u ON bv.vendor_id = u.id
    JOIN vendor_profiles vp ON u.id = vp.user_id
    WHERE bv.booking_id = '$booking_id'
");

$vendors = [];
while ($v = mysqli_fetch_assoc($vendorQ)) {
    $vendors[] = $v;
}

// Format dates
$booking_date = date('d M Y', strtotime($booking['booking_date']));
$event_date = date('d M Y, g:i A', strtotime($booking['event_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #dc2626;
            --dark: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
            --white: #ffffff;
            --shadow: 0 22px 60px rgba(15, 23, 42, .12);
            --radius: 24px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 34%),
                radial-gradient(circle at bottom right, rgba(14,165,233,.14), transparent 36%),
                #f4f7fb;
            color: var(--dark);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, #0284c7 100%);
            border-radius: var(--radius);
            padding: 40px;
            color: white;
            text-align: center;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
        }

        .hero-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }

        .hero h1 {
            margin: 0;
            font-size: 36px;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .hero p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .hero-details {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            text-align: left;
        }

        .detail-item span {
            display: block;
            font-size: 13px;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .detail-item strong {
            display: block;
            font-size: 18px;
        }

        /* GRID LAYOUT */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .card {
            background: white;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
        }

        .card h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: var(--dark);
            border-bottom: 2px solid var(--line);
            padding-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--soft);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--muted);
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(22, 163, 74, .1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, .1);
            color: var(--warning);
        }

        .side-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .amount-box {
            background: linear-gradient(135deg, var(--success) 0%, #15803d 100%);
            border-radius: var(--radius);
            padding: 24px;
            color: white;
            text-align: center;
        }

        .amount-box .label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .amount-box .value {
            font-size: 32px;
            font-weight: 700;
        }

        .vendor-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .vendor-item {
            padding: 12px;
            background: var(--soft);
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }

        .vendor-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .vendor-service {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .vendor-price {
            font-weight: 600;
            color: var(--success);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, .3);
        }

        .btn-secondary {
            background: var(--line);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .note-box {
            background: var(--soft);
            border-left: 4px solid var(--primary);
            padding: 16px;
            border-radius: 8px;
            margin-top: 24px;
        }

        .note-box h4 {
            margin: 0 0 8px;
            font-size: 14px;
        }

        .note-box p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .hero-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="container">
        <!-- HERO SECTION -->
        <div class="hero">
            <div class="hero-icon">✓</div>
            <h1>Booking Confirmed!</h1>
            <p>Your event booking has been successfully confirmed. Thank you for your order!</p>
            <div class="hero-details">
                <div class="detail-item">
                    <span>Booking ID</span>
                    <strong>#<?= $booking_id ?></strong>
                </div>
                <div class="detail-item">
                    <span>Status</span>
                    <strong><span class="badge badge-success">Confirmed</span></strong>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT GRID -->
        <div class="main-grid">
            <!-- LEFT COLUMN -->
            <div>
                <!-- BOOKING DETAILS -->
                <div class="card">
                    <h2>📋 Booking Details</h2>
                    <div class="info-row">
                        <span class="info-label">Booking Date</span>
                        <span class="info-value"><?= $booking_date ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Event Date & Time</span>
                        <span class="info-value"><?= $event_date ?></span>
                    </div>
                </div>

                <!-- PACKAGE DETAILS -->
                <div class="card">
                    <h2>🎁 Package Details</h2>
                    <div class="info-row">
                        <span class="info-label">Package Name</span>
                        <span class="info-value"><?= htmlspecialchars($booking['package_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Maximum Pax</span>
                        <span class="info-value"><?= $booking['pax'] ?> guests</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Package Description</span>
                        <span class="info-value"><?= htmlspecialchars($booking['description']) ?></span>
                    </div>
                </div>

                <!-- COMPANY DETAILS -->
                <div class="card">
                    <h2>🏢 Service Provider</h2>
                    <div class="info-row">
                        <span class="info-label">Company Name</span>
                        <span class="info-value"><?= htmlspecialchars($booking['company_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><a href="mailto:<?= $booking['company_email'] ?>" style="color: var(--primary); text-decoration: none;"><?= $booking['company_email'] ?></a></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><a href="tel:<?= $booking['company_phone'] ?>" style="color: var(--primary); text-decoration: none;"><?= $booking['company_phone'] ?></a></span>
                    </div>
                </div>

                <!-- VENDORS SECTION -->
                <?php if (!empty($vendors)): ?>
                <div class="card">
                    <h2>👥 Additional Vendors</h2>
                    <div class="vendor-list">
                        <?php foreach ($vendors as $vendor): ?>
                        <div class="vendor-item">
                            <div class="vendor-name"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
                            <div class="vendor-service"><?= htmlspecialchars($vendor['service_type']) ?></div>
                            <div class="vendor-price">RM <?= number_format($vendor['price'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="side-section">
                <!-- PAYMENT SUMMARY -->
                <div class="card">
                    <h2>💳 Payment Summary</h2>
                    <?php if ($payment): ?>
                    <div class="info-row">
                        <span class="info-label">Subtotal</span>
                        <span class="info-value">RM <?= number_format($booking['total_price'], 2) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Deposit (10%)</span>
                        <span class="info-value">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Remaining (90%)</span>
                        <span class="info-value">RM <?= number_format($payment['remaining_amount'], 2) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method</span>
                        <span class="info-value"><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status</span>
                        <span class="info-value">
                            <?php if ($payment['payment_status'] == 'Completed'): ?>
                                <span class="badge badge-success">Completed</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="info-row">
                        <span class="info-label">Total Amount</span>
                        <span class="info-value">RM <?= number_format($booking['total_price'], 2) ?></span>
                    </div>
                    <p style="color: var(--muted); font-size: 13px;">Payment information not yet recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- TOTAL AMOUNT BOX -->
                <div class="amount-box">
                    <div class="label">Total Amount</div>
                    <div class="value">RM <?= number_format($booking['total_price'], 2) ?></div>
                </div>

                <!-- CUSTOMER INFO -->
                <div class="card">
                    <h2>👤 Your Information</h2>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= htmlspecialchars($booking['customer_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($booking['customer_email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($booking['customer_phone']) ?></span>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="action-buttons">
                    <a href="my_bookings.php" class="btn btn-primary">View All Bookings</a>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- NOTE SECTION -->
        <div class="note-box">
            <h4>📌 What's Next?</h4>
            <p>
                Your booking is now confirmed! A confirmation email has been sent to <strong><?= htmlspecialchars($booking['customer_email']) ?></strong>. 
                The service provider will contact you shortly to confirm all details. If you have any questions, please contact the service provider directly or visit your bookings page for more information.
            </p>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
