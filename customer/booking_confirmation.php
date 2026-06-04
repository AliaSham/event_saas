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
$booking_date_raw = $booking['booking_date'] ?? $booking['created_at'] ?? $booking['event_date'] ?? null;
$booking_date = $booking_date_raw ? date('d M Y', strtotime($booking_date_raw)) : 'N/A';
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
        .page-confirm {
            --p-blue: #2563eb;
            --p-blue-dark: #1e40af;
            --p-green: #16a34a;
            --p-amber: #d97706;
            --p-red: #dc2626;
            --p-dark: #0f172a;
            --p-muted: #64748b;
            --p-border: #e2e8f0;
            --p-bg: #f8fafc;
            
            background-color: var(--p-bg);
            font-family: 'Poppins', sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .page-confirm * {
            box-sizing: border-box;
        }

        .page-confirm .confirm-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* BANNER ATAS */
        .page-confirm .confirm-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, var(--p-blue) 100%);
            border-radius: 24px;
            padding: 40px 20px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.15);
        }

        .page-confirm .confirm-hero-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            font-weight: bold;
        }

        .page-confirm .confirm-hero h1 {
            margin: 0 0 10px 0 !important;
            font-size: 32px !important;
            font-weight: 700 !important;
            color: #ffffff !important;
        }

        .page-confirm .confirm-hero p {
            margin: 0 0 25px 0 !important;
            opacity: 0.9;
            font-size: 15px;
            color: #ffffff !important;
        }

        .page-confirm .confirm-meta-box {
            display: inline-flex;
            gap: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
        }

        .page-confirm .meta-item {
            text-align: center;
        }

        .page-confirm .meta-item span {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.7;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .page-confirm .meta-item strong {
            font-size: 18px;
            font-weight: 600;
        }

        /* STRUKTUR GRID KIRI & KANAN */
        .page-confirm .confirm-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 25px;
            align-items: start;
        }

        /* REKAAN KAD BARU */
        .page-confirm .confirm-card {
            background: #ffffff !important;
            border: 1px solid var(--p-border) !important;
            border-radius: 20px !important;
            padding: 24px !important;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04) !important;
            width: 100% !important;
            display: block !important;
            float: none !important;
        }

        .page-confirm .confirm-card h2 {
            margin: 0 0 20px 0 !important;
            font-size: 17px !important;
            font-weight: 700 !important;
            color: var(--p-dark) !important;
            border-bottom: 2px solid #f1f5f9 !important;
            padding-bottom: 12px !important;
            text-align: left !important;
        }

        /* SUSUNAN DATA BARIS */
        .page-confirm .confirm-row {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 12px 0 !important;
            border-bottom: 1px solid #f8fafc !important;
            width: 100% !important;
            gap: 20px;
        }

        .page-confirm .confirm-row:last-child {
            border-bottom: none !important;
        }

        .page-confirm .confirm-label {
            color: var(--p-muted) !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            text-align: left !important;
            flex-shrink: 0;
        }

        .page-confirm .confirm-value {
            color: var(--p-dark) !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            text-align: right !important;
            word-break: break-word;
        }

        /* BADGES */
        .page-confirm .p-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .page-confirm .p-badge-success {
            background-color: #dcfce7 !important;
            color: #15803d !important;
        }

        .page-confirm .p-badge-warning {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }

        /* BLOK JUMLAH BESAR */
        .page-confirm .confirm-total-box {
            background: linear-gradient(135deg, var(--p-green) 0%, #15803d 100%) !important;
            border-radius: 20px !important;
            padding: 24px !important;
            color: #ffffff !important;
            text-align: center !important;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(22, 163, 74, 0.15) !important;
        }

        .page-confirm .confirm-total-box .total-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 4px;
        }

        .page-confirm .confirm-total-box .total-value {
            font-size: 32px;
            font-weight: 800;
        }

        /* VENDOR LIST FIXED */
        .page-confirm .p-vendor-item {
            background: #f8fafc !important;
            border-left: 4px solid var(--p-blue) !important;
            padding: 14px !important;
            border-radius: 12px !important;
            margin-bottom: 10px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            min-height: auto !important; /* Reset tinggi */
            height: auto !important;
        }

        .page-confirm .p-vendor-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--p-dark);
        }

        .page-confirm .p-vendor-service {
            font-size: 12px;
            color: var(--p-muted);
        }

        .page-confirm .p-vendor-price {
            font-weight: 700;
            color: var(--p-green);
            font-size: 15px;
        }

        /* BUTANG AKSI */
        .page-confirm .confirm-btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .page-confirm .p-btn {
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
        }

        .page-confirm .p-btn-primary {
            background: var(--p-blue);
            color: #ffffff !important;
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }

        .page-confirm .p-btn-primary:hover {
            background: var(--p-blue-dark);
        }

        .page-confirm .p-btn-secondary {
            background: #e2e8f0;
            color: var(--p-dark) !important;
        }

        .page-confirm .p-btn-secondary:hover {
            background: #cbd5e1;
        }

        /* NOTA FOOTER */
        .page-confirm .confirm-note {
            background: #eff6ff;
            border-left: 4px solid var(--p-blue);
            padding: 20px;
            border-radius: 16px;
            margin-top: 10px;
        }

        .page-confirm .confirm-note h4 {
            margin: 0 0 6px 0 !important;
            font-size: 14px !important;
            color: #1e3a8a !important;
            font-weight: 700;
        }

        .page-confirm .confirm-note p {
            margin: 0 !important;
            font-size: 13px !important;
            color: #1e40af !important;
            line-height: 1.5 !important;
        }

        /* RESPONSIVE SMARTPHONE */
        @media (max-width: 800px) {
            .page-confirm .confirm-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-confirm .confirm-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 4px;
            }
            .page-confirm .confirm-value {
                text-align: left !important;
            }
            .page-confirm .confirm-btn-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="page-confirm">
        <div class="confirm-container">
            
            <!-- BANNER PENGESAHAN -->
            <div class="confirm-hero">
                <div class="confirm-hero-icon">✓</div>
                <h1>Booking Confirmed!</h1>
                <p>Your event booking has been successfully confirmed. Thank you for your order!</p>
                
                <div class="confirm-meta-box">
                    <div class="meta-item">
                        <span>Booking ID</span>
                        <strong>#<?= $booking_id ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Status</span>
                        <strong><span class="p-badge p-badge-success">Confirmed</span></strong>
                    </div>
                </div>
            </div>

            <!-- GRID KANDUNGAN UTAMA -->
            <div class="confirm-grid">
                
                <!-- SEBELAH KIRI -->
                <div>
                    <!-- MAKLUMAT BOOKING -->
                    <div class="confirm-card">
                        <h2>📋 Booking Specifications</h2>
                        <div class="confirm-row">
                            <span class="confirm-label">Booking Date</span>
                            <span class="confirm-value"><?= $booking_date ?></span>
                        </div>
                        <div class="confirm-row">
                            <span class="confirm-label">Event Date & Time</span>
                            <span class="confirm-value"><?= $event_date ?></span>
                        </div>
                    </div>

                    <!-- MAKLUMAT PAKEJ -->
                    <div class="confirm-card">
                        <h2>🎁 Selected Package Info</h2>
                        <div class="confirm-row">
                            <span class="confirm-label">Package Name</span>
                            <span class="confirm-value"><?= htmlspecialchars($booking['package_name']) ?></span>
                        </div>
                        <div class="confirm-row">
                            <span class="confirm-label">Maximum Capacity</span>
                            <span class="confirm-value"><?= $booking['pax'] ?> guests</span>
                        </div>
                        <div class="confirm-row" style="flex-direction: column; align-items: flex-start !important; gap: 6px;">
                            <span class="confirm-label">Package Description</span>
                            <span class="confirm-value" style="text-align: left !important; font-weight: 400; color: var(--p-muted); line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($booking['description'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- MAKLUMAT SYARIKAT -->
                    <div class="confirm-card">
                        <h2>🏢 Service Provider</h2>
                        <div class="confirm-row">
                            <span class="confirm-label">Company Name</span>
                            <span class="confirm-value"><?= htmlspecialchars($booking['company_name']) ?></span>
                        </div>
                        <div class="confirm-row">
                            <span class="confirm-label">Email Support</span>
                            <span class="confirm-value"><a href="mailto:<?= $booking['company_email'] ?>" style="color: var(--p-blue); text-decoration: none;"><?= $booking['company_email'] ?></a></span>
                        </div>
                        <div class="confirm-row">
                            <span class="confirm-label">Contact Line</span>
                            <span class="confirm-value"><a href="tel:<?= $booking['company_phone'] ?>" style="color: var(--p-blue); text-decoration: none; font-weight: 700;"><?= $booking['company_phone'] ?></a></span>
                        </div>
                    </div>
                </div>

                <!-- SEBELAH KANAN -->
                <div>
                    <!-- KOTAK JUMLAH BESAR -->
                    <div class="confirm-total-box">
                        <div class="total-label">Total Amount</div>
                        <div class="total-value">RM <?= number_format($booking['total_price'], 2) ?></div>
                    </div>

                    <!-- RINGKASAN INVOIS / BAYARAN -->
                    <div class="confirm-card">
                        <h2>💳 Invoice Ledger Summary</h2>
                        <?php if ($payment): ?>
                            <div class="confirm-row">
                                <span class="confirm-label">Subtotal</span>
                                <span class="confirm-value">RM <?= number_format($booking['total_price'], 2) ?></span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-label">Deposit Processed (10%)</span>
                                <span class="confirm-value" style="color: var(--p-green);">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-label">Outstanding Balance (90%)</span>
                                <span class="confirm-value" style="color: var(--p-red); font-weight: 700;">RM <?= number_format($payment['remaining_amount'], 2) ?></span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-label">Payment Method</span>
                                <span class="confirm-value"><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-label">Transaction Status</span>
                                <span class="confirm-value">
                                    <?php if ($payment['payment_status'] == 'Completed'): ?>
                                        <span class="p-badge p-badge-success">Completed</span>
                                    <?php else: ?>
                                        <span class="p-badge p-badge-warning">Pending</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="confirm-row">
                                <span class="confirm-label">Total Price</span>
                                <span class="confirm-value">RM <?= number_format($booking['total_price'], 2) ?></span>
                            </div>
                            <p style="color: var(--p-muted); font-size: 13px; text-align: center; margin: 10px 0 0;">No active payment records tracked yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- SENARAI VENDORS TAMBAHAN (Dah dibetulkan supaya tak memanjang) -->
                    <?php if (!empty($vendors)): ?>
                    <div class="confirm-card">
                        <h2>👥 Hired Third-Party Addons</h2>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($vendors as $vendor): ?>
                            <div class="p-vendor-item">
                                <div>
                                    <div class="p-vendor-name"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
                                    <div class="p-vendor-service"><?= htmlspecialchars($vendor['service_type']) ?></div>
                                </div>
                                <div class="p-vendor-price">RM <?= number_format($vendor['price'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- DATA PROFILE USER -->
                    <div class="confirm-card">
                        <h2>👤 Customer Profile Identity</h2>
                        <div class="confirm-row">
                            <span class="confirm-label">Account Holder</span>
                            <span class="confirm-value"><?= htmlspecialchars($booking['customer_name']) ?></span>
                        </div>
                        <div class="confirm-row">
                            <span class="confirm-label">Contact Handle</span>
                            <span class="confirm-value"><?= htmlspecialchars($booking['customer_email']) ?></span>
                        </div>
                    </div>

                    <!-- BUTANG TINDAKAN -->
                    <div class="confirm-btn-group">
                        <a href="my_bookings.php" class="p-btn p-btn-primary">View All Bookings</a>
                        <a href="dashboard.php" class="p-btn p-btn-secondary">Back to Dashboard</a>
                    </div>

                </div>
            </div> <!-- End Grid -->

            <!-- NOTA KAKI -->
            <div class="confirm-note">
                <h4>📌 What's Next?</h4>
                <p>
                    Your booking is now confirmed! A confirmation email has been sent to <strong><?= htmlspecialchars($booking['customer_email']) ?></strong>. 
                    The service provider will contact you shortly to confirm all details. If you have any questions, please contact the service provider directly.
                </p>
            </div>

        </div>
    </div>
</body>
</html>