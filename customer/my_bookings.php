<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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
$totalBookings = mysqli_num_rows($bookings);
?>

<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 35%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.14), transparent 35%),
        #f4f7fb;
    color: #0f172a;
}

.container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 38px 24px 60px;
}

.booking-hero {
    background: linear-gradient(135deg, #0f172a, #1d4ed8, #38bdf8);
    color: white;
    border-radius: 30px;
    padding: 42px;
    margin-bottom: 28px;
    box-shadow: 0 25px 55px rgba(37,99,235,.25);
}

.booking-hero span {
    display: inline-block;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.24);
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 13px;
    margin-bottom: 16px;
}

.booking-hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
}

.booking-hero p {
    margin: 0;
    opacity: .9;
}

.summary-card {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.summary-box {
    background: white;
    border-radius: 22px;
    padding: 20px;
    box-shadow: 0 16px 38px rgba(15,23,42,.08);
    border: 1px solid #e2e8f0;
}

.summary-box small {
    color: #64748b;
}

.summary-box h3 {
    margin: 8px 0 0;
    font-size: 28px;
}

.alert-message {
    padding: 15px 18px;
    margin-bottom: 24px;
    border-radius: 16px;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    color: #166534;
    font-weight: 600;
}

.empty-state {
    min-height: 420px;
    background: white;
    border-radius: 30px;
    box-shadow: 0 20px 45px rgba(15,23,42,.08);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 45px;
}

.empty-icon {
    width: 86px;
    height: 86px;
    margin: 0 auto 18px;
    border-radius: 28px;
    background: linear-gradient(135deg, #dbeafe, #e0f2fe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
}

.empty-state h3 {
    margin: 0 0 8px;
    font-size: 26px;
}

.empty-state p {
    margin: 0 0 20px;
    color: #64748b;
}

.btn-primary {
    display: inline-block;
    padding: 13px 22px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    text-decoration: none;
    font-weight: 700;
    box-shadow: 0 12px 25px rgba(37,99,235,.25);
}

.booking-list {
    display: grid;
    gap: 26px;
}

.booking-card {
    background: rgba(255,255,255,.96);
    border: 1px solid #e2e8f0;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(15,23,42,.09);
}

.booking-card-header {
    padding: 24px 28px;
    background: linear-gradient(135deg, #f8fafc, #eff6ff);
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
}

.booking-card-header h3 {
    margin: 0;
    font-size: 23px;
}

.muted {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 13px;
}

.status-badge {
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
}

.status-pending,
.status-payment-pending {
    background: #fff7ed;
    color: #c2410c;
}

.status-approved,
.status-confirmed {
    background: #ecfdf5;
    color: #15803d;
}

.status-cancelled {
    background: #fef2f2;
    color: #b91c1c;
}

.status-completed {
    background: #eff6ff;
    color: #1d4ed8;
}

.booking-content {
    padding: 26px 28px 30px;
}

.booking-info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
}

.info-box small {
    color: #64748b;
    display: block;
    margin-bottom: 6px;
}

.info-box strong {
    font-size: 15px;
}

.booking-section {
    margin-top: 20px;
    padding: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
}

.booking-section h4 {
    margin: 0 0 9px;
    font-size: 16px;
}

.booking-section p {
    margin: 0;
    color: #475569;
    line-height: 1.7;
    font-size: 14px;
}

.payment-status-box {
    margin-top: 22px;
    padding: 20px;
    border-radius: 22px;
    border: 1px solid;
}

.payment-status-box.pending {
    background: #fffbeb;
    border-color: #fde68a;
}

.payment-status-box.completed {
    background: #ecfdf5;
    border-color: #bbf7d0;
}

.payment-title {
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 10px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    margin-top: 8px;
    font-size: 14px;
}

.payment-action-buttons {
    margin-top: 16px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-pay-now,
.btn-view-payment {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 14px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.btn-pay-now {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: white;
}

.btn-view-payment {
    background: #0f172a;
    color: white;
}

                .tab-header {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 22px;
                    flex-wrap: wrap;
                }

                .tab-button {
                    border: 1px solid #cbd5e1;
                    background: white;
                    color: #0f172a;
                    padding: 12px 18px;
                    border-radius: 14px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: background .2s, border-color .2s, color .2s;
                }

                .tab-button.active {
                    background: #2563eb;
                    border-color: #1d4ed8;
                    color: white;
                }

                .tab-panel {
                    display: none;
                    animation: fadeIn 180ms ease-in-out;
                }

                .tab-panel.active {
                    display: block;
                }

                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

.addon-item p {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 13px;
}

.addon-price {
    font-weight: 800;
    color: #2563eb;
}

@media(max-width: 900px) {
    .summary-card,
    .booking-info-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 600px) {
    .booking-hero {
        padding: 30px 24px;
    }

    .booking-hero h1 {
        font-size: 30px;
    }

    .summary-card,
    .booking-info-grid {
        grid-template-columns: 1fr;
    }

    .booking-card-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <div class="booking-hero">
        <span>📋 Customer Booking Centre</span>
        <h1>My Bookings</h1>
        <p>View your event booking status, package details, selected vendors and payment progress in one place.</p>
    </div>

    <div class="summary-card">
        <div class="summary-box">
            <small>Total Bookings</small>
            <h3><?= $totalBookings ?></h3>
        </div>
        <div class="summary-box">
            <small>Payment Tracking</small>
            <h3>Ready</h3>
        </div>
        <div class="summary-box">
            <small>Booking Status</small>
            <h3>Live</h3>
        </div>
    </div>

    <?php if ($message == 'already_paid') { ?>
        <div class="alert-message">
            ✓ This booking has already been paid. No additional payment is required.
        </div>
    <?php } ?>

    <?php if ($totalBookings == 0) { ?>
        <div class="empty-state">
            <div>
                <div class="empty-icon">🗓️</div>
                <h3>No bookings yet</h3>
                <p>You have not made any booking yet. Start by browsing available event companies.</p>
                <a href="dashboard.php" class="btn-primary">Browse Companies</a>
            </div>
        </div>
    <?php } ?>

    <div class="booking-list">

        <?php 
        mysqli_data_seek($bookings, 0);
        while($booking = mysqli_fetch_assoc($bookings)) { 
            $booking_id = $booking['id'];

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

            $balancePaymentQ = mysqli_query($conn, "
                SELECT * FROM payment_history
                WHERE booking_id = '$booking_id'
                AND payment_type = 'Balance'
                ORDER BY id DESC
                LIMIT 1
            ");

            $balancePayment = null;
            if (mysqli_num_rows($balancePaymentQ) > 0) {
                $balancePayment = mysqli_fetch_assoc($balancePaymentQ);
            }
        ?>

        <div class="booking-card">

            <div class="booking-card-header">
                <div>
                    <h3><?= htmlspecialchars($booking['company_name']) ?></h3>
                    <p class="muted">Booking ID: #<?= $booking['id'] ?></p>
                </div>

                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $booking['status'])) ?>">
                    <?= htmlspecialchars($booking['status']) ?>
                </span>
            </div>

            <div class="booking-content">

                <div class="tab-header">
                    <button type="button" class="tab-button active" data-tab="details-<?= $booking_id ?>">Booking Details</button>
                    <button type="button" class="tab-button" data-tab="balance-<?= $booking_id ?>">Balance / Deposit</button>
                </div>

                <div class="tab-panel active" id="details-<?= $booking_id ?>">
                    <div class="booking-info-grid">
                        <div class="info-box">
                            <small>Event Date</small>
                            <strong><?= htmlspecialchars($booking['event_date']) ?></strong>
                        </div>

                        <div class="info-box">
                            <small>Package</small>
                            <strong><?= htmlspecialchars($booking['package_name']) ?></strong>
                        </div>

                        <div class="info-box">
                            <small>Pax</small>
                            <strong><?= htmlspecialchars($booking['pax']) ?> pax</strong>
                        </div>

                        <div class="info-box">
                            <small>Total Price</small>
                            <strong>RM <?= number_format($booking['total_price'], 2) ?></strong>
                        </div>
                    </div>

                    <div class="booking-section">
                        <h4>Package Description</h4>
                        <p><?= htmlspecialchars($booking['package_description']) ?></p>
                    </div>

                    <div class="booking-section">
                        <h4>Menu</h4>
                        <p><?= htmlspecialchars($booking['menu']) ?></p>
                    </div>
                </div>

                <div class="tab-panel" id="balance-<?= $booking_id ?>">
                    <div class="booking-section">
                        <h4>Balance Deposit Payment</h4>
                        <p>Track deposit amount, remaining balance and payment confirmation details for this booking.</p>
                    </div>

                    <div class="booking-info-grid">
                        <div class="info-box">
                            <small>Deposit Amount</small>
                            <strong>RM <?= number_format($deposit_amount, 2) ?></strong>
                        </div>
                        <div class="info-box">
                            <small>Remaining Balance</small>
                            <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                        </div>
                        <div class="info-box">
                            <small>Payment Status</small>
                            <strong><?= htmlspecialchars($payment ? $payment['payment_status'] : 'Not Started') ?></strong>
                        </div>
                        <div class="info-box">
                            <small>Payment Method</small>
                            <strong><?= htmlspecialchars($payment ? ucfirst(str_replace('_', ' ', $payment['payment_method'])) : 'N/A') ?></strong>
                        </div>
                    </div>

                    <?php if ($balancePayment && $balancePayment['payment_status'] == 'Completed') { ?>
                        <div class="payment-status-box completed">
                            <div class="payment-title">✅ Balance Accepted</div>
                            <div class="payment-row">
                                <span>Balance Paid</span>
                                <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                            </div>
                            <div class="payment-row">
                                <span>Confirmation Status</span>
                                <strong>Accepted</strong>
                            </div>
                            <div class="payment-row">
                                <span>Paid On</span>
                                <strong><?= date('d M Y H:i', strtotime($balancePayment['created_at'])) ?></strong>
                            </div>
                            <div class="payment-action-buttons">
                                <a href="payment_confirmation.php?history_id=<?= $balancePayment['id'] ?>" class="btn-view-payment">📋 View Balance Details</a>
                            </div>
                        </div>
                    <?php } elseif ($balancePayment && $balancePayment['payment_status'] == 'Pending') { ?>
                        <div class="payment-status-box pending">
                            <div class="payment-title">⏳ Balance Payment Pending Confirmation</div>
                            <div class="payment-row">
                                <span>Balance Due</span>
                                <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                            </div>
                            <div class="payment-row">
                                <span>Submitted On</span>
                                <strong><?= date('d M Y H:i', strtotime($balancePayment['created_at'])) ?></strong>
                            </div>
                            <p style="margin-top:10px;color:#92400e;font-size:13px;">
                                Your balance payment has been submitted and is waiting for acceptance.
                            </p>
                            <div class="payment-action-buttons">
                                <a href="payment_confirmation.php?history_id=<?= $balancePayment['id'] ?>" class="btn-view-payment">📋 View Submission</a>
                            </div>
                        </div>
                    <?php } elseif ($payment && $booking['payment_status'] == 'Partial') { ?>
                        <div class="payment-status-box pending">
                            <div class="payment-title">💰 Pay Remaining Balance</div>
                            <div class="payment-row">
                                <span>Remaining Balance</span>
                                <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                            </div>
                            <p style="margin-top:10px;color:#92400e;font-size:13px;">
                                Your deposit is confirmed. Complete the remaining balance to settle the booking.
                            </p>
                            <div class="payment-action-buttons">
                                <a href="payment.php?booking_id=<?= $booking_id ?>&pay_type=balance" class="btn-pay-now">💳 Pay Balance</a>
                            </div>
                        </div>
                    <?php } elseif ($booking['payment_status'] == 'Paid') { ?>
                        <div class="payment-status-box completed">
                            <div class="payment-title">✅ Fully Paid</div>
                            <div class="payment-row">
                                <span>Final Balance</span>
                                <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                            </div>
                            <p style="margin-top:10px;color:#166534;font-size:13px;">
                                Your balance payment has already been accepted.
                            </p>
                        </div>
                    <?php } else { ?>
                        <div class="payment-status-box pending">
                            <div class="payment-title">🧾 Deposit Required</div>
                            <div class="payment-row">
                                <span>Deposit Required</span>
                                <strong>RM <?= number_format($deposit_amount, 2) ?></strong>
                            </div>
                            <div class="payment-row">
                                <span>Remaining Balance</span>
                                <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                            </div>
                            <p style="margin-top:10px;color:#92400e;font-size:13px;">
                                Complete the 10% deposit payment to start your confirmation process.
                            </p>
                            <div class="payment-action-buttons">
                                <a href="payment.php?booking_id=<?= $booking_id ?>" class="btn-pay-now">💳 Pay Deposit</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="booking-section">
                    <h4>Selected Add-On / Vendor</h4>

                    <?php if (mysqli_num_rows($vendorQ) == 0) { ?>
                        <p>No add-on selected.</p>
                    <?php } else { ?>
                        <div class="addon-list">
                            <?php while($vendor = mysqli_fetch_assoc($vendorQ)) { ?>
                                <div class="addon-item">
                                    <div>
                                        <strong><?= htmlspecialchars($vendor['vendor_name']) ?></strong>
                                        <p><?= htmlspecialchars($vendor['service_type']) ?></p>
                                    </div>
                                    <span class="addon-price">
                                        RM <?= number_format($vendor['price'], 2) ?>
                                    </span>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                </div>

            </div>
        </div>

        <?php } ?>

    </div>

</div>

<script>
    document.querySelectorAll('.tab-header').forEach(function(header) {
        header.addEventListener('click', function(event) {
            var button = event.target.closest('.tab-button');
            if (!button) return;
            var panelId = button.getAttribute('data-tab');
            var parent = button.closest('.booking-content');

            parent.querySelectorAll('.tab-button').forEach(function(btn) {
                btn.classList.toggle('active', btn === button);
            });

            parent.querySelectorAll('.tab-panel').forEach(function(panel) {
                panel.classList.toggle('active', panel.id === panelId);
            });
        });
    });
</script>
</body>
</html>