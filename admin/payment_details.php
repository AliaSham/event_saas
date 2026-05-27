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

// Get detailed payment information
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

// Get vendors for this booking
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .payment-details-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .detail-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 12px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-row .label {
            font-weight: 500;
            color: #666;
            font-size: 13px;
        }

        .detail-row .value {
            font-weight: 600;
            color: #333;
            text-align: right;
            word-break: break-word;
            margin-left: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-proof {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .payment-proof h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
        }

        .proof-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            text-align: center;
        }

        .proof-preview a {
            display: inline-block;
            padding: 10px 20px;
            background: #2196f3;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin: 5px;
            font-size: 13px;
        }

        .proof-preview a:hover {
            background: #1976d2;
        }

        .vendors-list {
            margin-top: 15px;
        }

        .vendor-item {
            background: #f8f9fa;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .actions a,
        .actions button {
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .actions .back {
            background: white;
            color: #333;
        }

        .actions .back:hover {
            background: #f0f0f0;
        }

        .actions .print {
            background: #6c757d;
            color: white;
        }

        .actions .print:hover {
            background: #5a6268;
        }

        @media print {
            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-details-container">

    <div style="margin-bottom: 30px;">
        <a href="payment_management.php" class="back-link">← Back to Payment Management</a>
        <h2 style="margin-top: 15px; margin-bottom: 5px;">Payment Details</h2>
        <p class="muted">Payment ID: #<?= $payment_id ?> • Booking ID: #<?= $payment['booking_id'] ?></p>
    </div>

    <div class="detail-grid">

        <!-- Left: Payment Information -->
        <div>

            <div class="detail-section">
                <h3>💳 Payment Information</h3>

                <div class="detail-row">
                    <span class="label">Payment Status:</span>
                    <span class="status-badge <?= strtolower($payment['payment_status']) ?>">
                        <?= $payment['payment_status'] ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Payment Method:</span>
                    <span class="value"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value">RM <?= number_format($payment['amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Deposit (10%):</span>
                    <span class="value" style="color: #2196f3; font-weight: 700;">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Remaining (90%):</span>
                    <span class="value">RM <?= number_format($payment['remaining_amount'], 2) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Transaction ID:</span>
                    <span class="value"><?= $payment['transaction_id'] ?? '-' ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Bank Reference:</span>
                    <span class="value"><?= $payment['bank_ref_number'] ?? '-' ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Date Created:</span>
                    <span class="value"><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Last Updated:</span>
                    <span class="value"><?= date('d M Y H:i', strtotime($payment['updated_at'])) ?></span>
                </div>

                <?php if (!empty($payment['notes'])) { ?>
                    <div class="payment-proof">
                        <h4>📝 Notes</h4>
                        <p style="margin: 0; padding: 12px; background: #f8f9fa; border-radius: 4px; font-size: 13px;">
                            <?= nl2br(htmlspecialchars($payment['notes'])) ?>
                        </p>
                    </div>
                <?php } ?>

                <?php if (!empty($payment['payment_proof_file'])) { ?>
                    <div class="payment-proof">
                        <h4>📸 Payment Proof</h4>
                        <div class="proof-preview">
                            <p style="margin: 0 0 10px 0; font-size: 13px;">
                                File: <strong><?= $payment['payment_proof_file'] ?></strong>
                            </p>
                            <a href="../uploads/payments/<?= $payment['payment_proof_file'] ?>" target="_blank">
                                📄 View/Download Proof
                            </a>
                        </div>
                    </div>
                <?php } ?>

            </div>

        </div>

        <!-- Right: Booking & Customer Information -->
        <div>

            <div class="detail-section">
                <h3>👤 Customer Information</h3>

                <div class="detail-row">
                    <span class="label">Customer Name:</span>
                    <span class="value"><?= $payment['customer_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value"><?= $payment['customer_email'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?= $payment['customer_phone'] ?></span>
                </div>
            </div>

            <div class="detail-section" style="margin-top: 20px;">
                <h3>📅 Booking Information</h3>

                <div class="detail-row">
                    <span class="label">Booking ID:</span>
                    <span class="value">#<?= $payment['booking_id'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Company:</span>
                    <span class="value"><?= $payment['company_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value"><?= $payment['subscription_status'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Package:</span>
                    <span class="value"><?= $payment['package_name'] ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Pax:</span>
                    <span class="value"><?= $payment['pax'] ?> pax</span>
                </div>

                <div class="detail-row">
                    <span class="label">Event Date:</span>
                    <span class="value"><?= date('d F Y', strtotime($payment['event_date'])) ?></span>
                </div>

                <?php if (mysqli_num_rows($vendorQ) > 0) { ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                        <p style="margin: 0 0 10px 0; font-weight: 600; font-size: 13px;">Add-ons / Vendors:</p>
                        <div class="vendors-list">
                            <?php while ($vendor = mysqli_fetch_assoc($vendorQ)) { ?>
                                <div class="vendor-item">
                                    <div>
                                        <strong><?= $vendor['vendor_name'] ?></strong><br>
                                        <small><?= $vendor['service_type'] ?></small>
                                    </div>
                                    <div>RM <?= number_format($vendor['price'], 2) ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

            </div>

        </div>

    </div>

    <!-- Actions -->
    <div class="actions">
        <a href="payment_management.php" class="back">← Back to Management</a>
        <button onclick="window.print()" class="print">🖨️ Print Details</button>
    </div>

</div>

<?php include('footer.php'); ?>

