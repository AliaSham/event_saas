<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

if (!isset($_GET['id'])) {
    header("Location: payment_management.php");
    exit;
}

$payment_id = intval($_GET['id']);

$paymentQ = mysqli_query($conn, "
    SELECT 
        p.*,
        b.id as booking_id,
        b.event_date,
        b.total_price,
        b.payment_status AS booking_payment_status,
        c.company_name,
        c.subscription_status,
        pkg.package_name,
        pkg.pax,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN companies c ON b.company_id = c.id
    JOIN packages pkg ON b.package_id = pkg.id
    JOIN users u ON p.user_id = u.id
    WHERE p.id = '$payment_id'
      AND b.company_id = '$company_id'
");

if (mysqli_num_rows($paymentQ) == 0) {
    header("Location: payment_management.php");
    exit;
}

$payment = mysqli_fetch_assoc($paymentQ);

$vendorQ = mysqli_query($conn, "
    SELECT 
        bv.price,
        u.name AS vendor_name,
        vp.service_type
    FROM booking_vendors bv
    JOIN users u ON bv.vendor_id = u.id
    JOIN vendor_profiles vp ON u.id = vp.user_id
    WHERE bv.booking_id = '{$payment['booking_id']}'
");

$is_fully_paid = ($payment['booking_payment_status'] == 'Paid');
$display_remaining = $is_fully_paid ? 0.00 : $payment['remaining_amount'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Details - #<?= $payment_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(18, 183, 106, 0.13), transparent 28%),
                linear-gradient(135deg, #f8fafc, #eef7f2);
            color: #0f172a;
        }

        .payment-details-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 38px 35px 60px;
        }

        /* Nyorokkan print header daripada skrin biasa */
        .print-only-header {
            display: none;
        }

        .hero-detail {
            background: linear-gradient(135deg, #06281b, #0b4d32, #12b76a);
            border-radius: 28px;
            padding: 32px;
            color: white;
            box-shadow: 0 25px 55px rgba(6, 40, 27, 0.25);
            margin-bottom: 26px;
            position: relative;
            overflow: hidden;
        }

        .hero-detail::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            top: -100px;
            right: -80px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
        }

        .hero-detail h1 {
            margin: 10px 0 6px;
            font-size: 32px;
            font-weight: 800;
        }

        .hero-detail p {
            margin: 0;
            opacity: .85;
            font-size: 14px;
        }

        .back-link {
            color: #d1fae5;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .hero-mini-card {
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 20px;
            padding: 16px 20px;
            backdrop-filter: blur(12px);
            min-width: 210px;
        }

        .hero-mini-card span {
            display: block;
            font-size: 12px;
            opacity: .8;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .hero-mini-card strong {
            font-size: 22px;
            font-weight: 800;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(226,232,240,.9);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 15px 35px rgba(15,23,42,.07);
        }

        .summary-card span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
        }

        .summary-card strong {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.08fr .92fr;
            gap: 24px;
            align-items: start;
        }

        .detail-section {
            background: rgba(255,255,255,0.95);
            border-radius: 24px;
            padding: 24px;
            border: 1px solid rgba(226,232,240,.9);
            box-shadow: 0 18px 45px rgba(15,23,42,.08);
            margin-bottom: 22px;
        }

        .detail-section h3 {
            margin: 0 0 18px;
            font-size: 18px;
            font-weight: 800;
            padding-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 13px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            text-align: right;
            word-break: break-word;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .status-badge.pending { background: #fff7ed; color: #c2410c; }
        .status-badge.partial { background: #e0f2fe; color: #0369a1; }
        .status-badge.paid { background: #dcfce7; color: #15803d; }
        .status-badge.failed { background: #fee2e2; color: #b91c1c; }

        .highlight-amount {
            color: #16a34a;
            font-size: 15px;
        }

        .note-box, .proof-box {
            margin-top: 18px;
            padding: 16px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .note-box h4, .proof-box h4 { margin: 0 0 10px; font-size: 14px; }
        .note-box p { margin: 0; font-size: 13px; color: #334155; line-height: 1.7; }
        .proof-box a {
            display: inline-block;
            margin-top: 12px;
            padding: 11px 16px;
            border-radius: 14px;
            background: #06281b;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .vendor-item {
            background: linear-gradient(135deg, #f8fafc, #eefdf5);
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 12px;
        }

        .vendor-item strong { font-size: 13px; }
        .vendor-item small { color: #64748b; font-size: 11px; }
        .vendor-price { font-weight: 800; color: #0b4d32; white-space: nowrap; }
        .actions { margin-top: 10px; display: flex; gap: 12px; flex-wrap: wrap; }
        .actions a, .actions button {
            padding: 13px 20px; border: none; border-radius: 15px;
            text-decoration: none; font-size: 13px; font-weight: 800;
            cursor: pointer; transition: .25s ease;
        }
        .actions .back { background: white; color: #0f172a; border: 1px solid #cbd5e1; }
        .actions .print { background: #06281b; color: white; }
        .actions a:hover, .actions button:hover, .proof-box a:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(15,23,42,.14);
        }

        @media (max-width: 900px) {
            .summary-grid, .detail-grid { grid-template-columns: 1fr; }
            .hero-content { flex-direction: column; align-items: flex-start; }
            .hero-mini-card { width: 100%; }
        }

        /* ==========================================================================
           MOD PRINTING BARU: DIROMBAK UNTUK JADI INVOIS/RESIT RASMI DAN BERSIH
           ========================================================================== */
        @media print {
            @page {
                size: A4;
                margin: 15mm 15mm 15mm 15mm;
            }
            
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 12px !important;
            }

            /* Sembunyikan hiasan web dashboard */
            .hero-detail, .actions, header, .sidebar, .back-link, .proof-box a {
                display: none !important;
            }

            /* Paparkan header resit formal */
            .print-only-header {
                display: block !important;
                border-bottom: 3px double #000000;
                padding-bottom: 15px;
                margin-bottom: 30px;
            }
            .print-only-header .receipt-title {
                font-size: 26px;
                font-weight: 800;
                text-transform: uppercase;
                color: #06281b;
                margin: 0;
            }
            .print-only-header .company-sub {
                font-size: 12px;
                color: #475569;
                margin-top: 4px;
            }
            .print-meta-table {
                width: 100%;
                margin-top: 15px;
            }
            .print-meta-table td {
                padding: 3px 0;
                font-size: 12px;
            }

            .payment-details-container {
                padding: 0 !important;
                max-width: 100% !important;
            }

            /* Tukar grid dashboard kepada susunan sebaris ke bawah */
            .summary-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 10px !important;
                margin-bottom: 30px !important;
            }

            .summary-card {
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 12px !important;
                padding: 12px !important;
                box-shadow: none !important;
                text-align: center;
            }
            .summary-card span { color: #475569 !important; font-size: 10px !important; }
            .summary-card strong { font-size: 16px !important; color: #000000 !important; }

            /* Menghilangkan status badge berbentuk emoji sewaktu diprint */
            .status-badge {
                padding: 0 !important;
                background: transparent !important;
                color: #000000 !important;
                font-size: 14px !important;
                font-weight: 700 !important;
            }

            .detail-grid {
                display: block !important;
            }

            .detail-section {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 14px !important;
                padding: 20px !important;
                box-shadow: none !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid; /* Mengelakkan maklumat terputus ke kertas sebelah */
            }

            .detail-section h3 {
                font-size: 14px !important;
                margin-bottom: 12px !important;
                padding-bottom: 8px !important;
                border-bottom: 2px solid #000000 !important;
                color: #000000 !important;
            }

            .detail-row {
                padding: 8px 0 !important;
                border-bottom: 1px dashed #e2e8f0 !important;
            }

            .label { color: #475569 !important; font-size: 12px !important; }
            .value { color: #000000 !important; font-size: 12px !important; font-weight: 700 !important; }

            .vendor-item {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                padding: 10px !important;
                border-radius: 10px !important;
                box-shadow: none !important;
            }
            .vendor-price { color: #000000 !important; }
            .note-box { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-details-container">

    <!-- HEADER FORMAL CETAKAN (Hanya muncul waktu print kertas) -->
    <div class="print-only-header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h2 class="receipt-title">SaaSAdmin System</h2>
                    <div class="company-sub">Official Payment Receipt / Invoice Statement</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div style="font-size: 11px; color: #64748b;">Printed On: <?= date('d M Y, H:i') ?></div>
                </td>
            </tr>
        </table>
        
        <table class="print-meta-table">
            <tr>
                <td style="width: 15%;"><strong>Payment ID:</strong></td>
                <td style="width: 35%;">#<?= $payment_id ?></td>
                <td style="width: 15%;"><strong>Booking ID:</strong></td>
                <td style="width: 35%;">#<?= $payment['booking_id'] ?></td>
            </tr>
            <tr>
                <td><strong>Client Name:</strong></td>
                <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                <td><strong>Event Date:</strong></td>
                <td><?= date('d F Y', strtotime($payment['event_date'])) ?></td>
            </tr>
        </table>
    </div>

    <!-- HERO DASHBOARD (Disembunyikan automatik waktu print) -->
    <div class="hero-detail">
        <div class="hero-content">
            <div>
                <a href="payment_management.php" class="back-link">← Back to Payment Management</a>
                <h1>Payment Details</h1>
                <p>Payment ID: #<?= $payment_id ?> • Booking ID: #<?= $payment['booking_id'] ?></p>
            </div>

            <div class="hero-mini-card">
                <?php if ($is_fully_paid) { ?>
                    <span>Total Paid</span>
                    <strong>RM <?= number_format($payment['total_price'], 2) ?></strong>
                <?php } else { ?>
                    <span>Deposit Received</span>
                    <strong>RM <?= number_format($payment['deposit_amount'], 2) ?></strong>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- KAD RINGKASAN AMAUN -->
    <div class="summary-grid">
        <div class="summary-card">
            <span>Total Amount</span>
            <strong>RM <?= number_format($payment['total_price'], 2) ?></strong>
        </div>

        <div class="summary-card">
            <span>Remaining Balance</span>
            <strong>RM <?= number_format($display_remaining, 2) ?></strong>
        </div>

        <div class="summary-card">
            <span>Payment Status</span>
            <?php 
            if ($payment['payment_status'] == 'Failed') {
                echo '<span class="status-badge failed">Failed</span>';
            } else if ($payment['payment_status'] == 'Pending') {
                echo '<span class="status-badge pending">Pending</span>';
            } else {
                if ($payment['booking_payment_status'] == 'Partial') {
                    echo '<span class="status-badge partial">Deposit Paid</span>';
                } else if ($payment['booking_payment_status'] == 'Paid') {
                    echo '<span class="status-badge paid">Fully Paid</span>';
                } else {
                    echo '<span class="status-badge pending">Unpaid</span>';
                }
            }
            ?>
        </div>
    </div>

    <div class="detail-grid">

        <!-- KOTAK KIRI: MAKLUMAT PEMBAYARAN -->
        <div>
            <div class="detail-section">
                <h3>💳 Payment Information</h3>

                <div class="detail-row">
                    <span class="label">Payment Method</span>
                    <span class="value"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Total Amount</span>
                    <span class="value">RM <?= number_format($payment['total_price'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Deposit 10%</span>
                    <span class="value highlight-amount">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Remaining 90%</span>
                    <span class="value">
                        <?= $is_fully_paid ? 'RM 0.00 (Paid)' : 'RM ' . number_format($payment['remaining_amount'], 2) ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Transaction ID</span>
                    <span class="value"><?= $payment['transaction_id'] ?? '-' ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Bank Reference</span>
                    <span class="value"><?= $payment['bank_ref_number'] ?? '-' ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Date Created</span>
                    <span class="value"><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Last Updated</span>
                    <span class="value"><?= date('d M Y H:i', strtotime($payment['updated_at'])) ?></span>
                </div>

                <?php if (!empty($payment['notes'])) { ?>
                    <div class="note-box">
                        <h4>📝 Notes</h4>
                        <p><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                    </div>
                <?php } ?>

                <?php if (!empty($payment['payment_proof_file'])) { ?>
                    <div class="proof-box">
                        <h4>📸 Payment Proof Document</h4>
                        <p style="margin:0;font-size:13px;color:#64748b;">
                            File: <strong><?= $payment['payment_proof_file'] ?></strong>
                        </p>
                        <a href="../uploads/payments/<?= $payment['payment_proof_file'] ?>" target="_blank">
                            View / Download Proof
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- KOTAK KANAN: PELANGGAN & TEMPAHAN -->
        <div>
            <div class="detail-section">
                <h3>👤 Customer Information</h3>

                <div class="detail-row">
                    <span class="label">Customer Name</span>
                    <span class="value"><?= htmlspecialchars($payment['customer_name']) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($payment['customer_email']) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Phone</span>
                    <span class="value"><?= htmlspecialchars($payment['customer_phone']) ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>📅 Booking Information</h3>

                <div class="detail-row">
                    <span class="label">Booking ID</span>
                    <span class="value">#<?= $payment['booking_id'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Company</span>
                    <span class="value"><?= htmlspecialchars($payment['company_name']) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Status</span>
                    <span class="value"><?= htmlspecialchars($payment['subscription_status']) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Package</span>
                    <span class="value"><?= htmlspecialchars($payment['package_name']) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Pax</span>
                    <span class="value"><?= htmlspecialchars($payment['pax']) ?> pax</span>
                </div>

                <div class="detail-row">
                    <span class="label">Event Date</span>
                    <span class="value"><?= date('d F Y', strtotime($payment['event_date'])) ?></span>
                </div>

                <?php if (mysqli_num_rows($vendorQ) > 0) { ?>
                    <div style="margin-top:18px;">
                        <h4 style="margin:0 0 12px;font-size:14px;">Add-ons / Vendors</h4>

                        <?php while ($vendor = mysqli_fetch_assoc($vendorQ)) { ?>
                            <div class="vendor-item">
                                <div>
                                    <strong><?= htmlspecialchars($vendor['vendor_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($vendor['service_type']) ?></small>
                                </div>
                                <div class="vendor-price">
                                    RM <?= number_format($vendor['price'], 2) ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

    </div>

    <!-- BUTANG AKSI -->
    <div class="actions">
        <a href="payment_management.php" class="back">← Back to Management</a>
        <button onclick="window.print()" class="print">🖨️ Print Details</button>
    </div>

</div>

<?php include('footer.php'); ?>

</body>
</html>