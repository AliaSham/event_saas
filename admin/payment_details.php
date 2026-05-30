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
        }

        .hero-mini-card strong {
            font-size: 22px;
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

        .status-badge.pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge.failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .highlight-amount {
            color: #16a34a;
            font-size: 15px;
        }

        .note-box,
        .proof-box {
            margin-top: 18px;
            padding: 16px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .note-box h4,
        .proof-box h4 {
            margin: 0 0 10px;
            font-size: 14px;
        }

        .note-box p {
            margin: 0;
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
        }

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

        .vendor-item strong {
            font-size: 13px;
        }

        .vendor-item small {
            color: #64748b;
            font-size: 11px;
        }

        .vendor-price {
            font-weight: 800;
            color: #0b4d32;
            white-space: nowrap;
        }

        .actions {
            margin-top: 10px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .actions a,
        .actions button {
            padding: 13px 20px;
            border: none;
            border-radius: 15px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: .25s ease;
        }

        .actions .back {
            background: white;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }

        .actions .print {
            background: #06281b;
            color: white;
        }

        .actions a:hover,
        .actions button:hover,
        .proof-box a:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(15,23,42,.14);
        }

        @media (max-width: 900px) {
            .summary-grid,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .hero-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-mini-card {
                width: 100%;
            }
        }

        @media print {
            body {
                background: white;
            }

            .hero-detail,
            .actions,
            header,
            .sidebar {
                display: none !important;
            }

            .payment-details-container {
                padding: 0;
                max-width: 100%;
            }

            .detail-section,
            .summary-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-details-container">

    <div class="hero-detail">
        <div class="hero-content">
            <div>
                <a href="payment_management.php" class="back-link">← Back to Payment Management</a>
                <h1>Payment Details</h1>
                <p>Payment ID: #<?= $payment_id ?> • Booking ID: #<?= $payment['booking_id'] ?></p>
            </div>

            <div class="hero-mini-card">
                <span>Deposit Received</span>
                <strong>RM <?= number_format($payment['deposit_amount'], 2) ?></strong>
            </div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <span>Total Amount</span>
            <strong>RM <?= number_format($payment['amount'], 2) ?></strong>
        </div>

        <div class="summary-card">
            <span>Remaining Balance</span>
            <strong>RM <?= number_format($payment['remaining_amount'], 2) ?></strong>
        </div>

        <div class="summary-card">
            <span>Payment Status</span>
            <span class="status-badge <?= strtolower($payment['payment_status']) ?>">
                <?= $payment['payment_status'] ?>
            </span>
        </div>
    </div>

    <div class="detail-grid">

        <div>
            <div class="detail-section">
                <h3>💳 Payment Information</h3>

                <div class="detail-row">
                    <span class="label">Payment Method</span>
                    <span class="value"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Total Amount</span>
                    <span class="value">RM <?= number_format($payment['amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Deposit 10%</span>
                    <span class="value highlight-amount">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Remaining 90%</span>
                    <span class="value">RM <?= number_format($payment['remaining_amount'], 2) ?></span>
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
                        <h4>📸 Payment Proof</h4>
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

        <div>
            <div class="detail-section">
                <h3>👤 Customer Information</h3>

                <div class="detail-row">
                    <span class="label">Customer Name</span>
                    <span class="value"><?= $payment['customer_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value"><?= $payment['customer_email'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Phone</span>
                    <span class="value"><?= $payment['customer_phone'] ?></span>
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
                    <span class="value"><?= $payment['company_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Status</span>
                    <span class="value"><?= $payment['subscription_status'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Package</span>
                    <span class="value"><?= $payment['package_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Pax</span>
                    <span class="value"><?= $payment['pax'] ?> pax</span>
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
                                    <strong><?= $vendor['vendor_name'] ?></strong><br>
                                    <small><?= $vendor['service_type'] ?></small>
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

    <div class="actions">
        <a href="payment_management.php" class="back">← Back to Management</a>
        <button onclick="window.print()" class="print">🖨️ Print Details</button>
    </div>

</div>

<?php include('footer.php'); ?>

</body>
</html>