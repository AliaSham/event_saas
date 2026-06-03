<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$history_id = null;
$payment_id = null;
$isHistory = false;

if (isset($_GET['payment_id'])) {
    $payment_id = intval($_GET['payment_id']);
} elseif (isset($_GET['history_id'])) {
    $history_id = intval($_GET['history_id']);
    $isHistory = true;
} else {
    header("Location: my_bookings.php");
    exit;
}

if ($isHistory) {
    $paymentQ = mysqli_query($conn, "
        SELECT 
            h.*,
            b.id as booking_id,
            c.company_name,
            u.email,
            u.phone
        FROM payment_history h
        JOIN bookings b ON h.booking_id = b.id
        JOIN companies c ON b.company_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE h.id = '$history_id'
        AND b.user_id = '$user_id'
    ");
} else {
    $paymentQ = mysqli_query($conn, "
        SELECT 
            p.*,
            b.id as booking_id,
            c.company_name,
            u.email,
            u.phone
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN companies c ON b.company_id = c.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = '$payment_id'
        AND p.user_id = '$user_id'
    ");
}

if (mysqli_num_rows($paymentQ) == 0) {
    header("Location: my_bookings.php");
    exit;
}

$payment = mysqli_fetch_assoc($paymentQ);

$error = "";
$success = "";

$confirmAmount = $isHistory ? $payment['amount'] : $payment['deposit_amount'];
if ($isHistory) {
    $depositPercent = 100;
    $remainingPercent = 0;
} else {
    $depositPercent = ($payment['amount'] > 0) ? round(($payment['deposit_amount'] / $payment['amount']) * 100) : 0;
    $remainingPercent = max(0, 100 - $depositPercent);
}

if (isset($_POST['confirm_payment'])) {
    $bank_ref_number = trim($_POST['bank_ref'] ?? '');
    $has_file = isset($_FILES['payment_proof']) && $_FILES['payment_proof']['size'] > 0;
    $recordId = $isHistory ? $history_id : $payment_id;

    if (!$has_file && $bank_ref_number === '') {
        $error = "Please upload payment proof or enter a reference/transaction number.";
    } else {
        $new_filename = null;

        if ($has_file) {
            $file_name = $_FILES['payment_proof']['name'];
            $file_tmp = $_FILES['payment_proof']['tmp_name'];
            $file_size = $_FILES['payment_proof']['size'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];

            if (!in_array(strtolower($file_ext), $allowed_exts)) {
                $error = "Invalid file type. Allowed: PDF, JPG, PNG, GIF.";
            } elseif ($file_size > 5000000) {
                $error = "File size too large. Max 5MB allowed.";
            } else {
                $upload_dir = '../uploads/payments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_filename = 'proof_' . $recordId . '_' . time() . '.' . $file_ext;

                if (!move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $error = "Error uploading file. Please try again.";
                }
            }
        }

        if ($error === '') {
            if ($new_filename !== null) {
                if ($isHistory) {
                    $stmt = $conn->prepare("UPDATE payment_history SET payment_proof_file = ?, transaction_id = ?, payment_status = 'Completed' WHERE id = ?");
                } else {
                    $stmt = $conn->prepare("UPDATE payments SET payment_proof_file = ?, bank_ref_number = ?, payment_status = 'Completed', updated_at = NOW() WHERE id = ?");
                }
                $stmt->bind_param("ssi", $new_filename, $bank_ref_number, $recordId);
            } else {
                if ($isHistory) {
                    $stmt = $conn->prepare("UPDATE payment_history SET transaction_id = ?, payment_status = 'Completed' WHERE id = ?");
                } else {
                    $stmt = $conn->prepare("UPDATE payments SET bank_ref_number = ?, payment_status = 'Completed', updated_at = NOW() WHERE id = ?");
                }
                $stmt->bind_param("si", $bank_ref_number, $recordId);
            }

            if ($stmt->execute()) {
                if ($isHistory) {
                    mysqli_query($conn, "UPDATE bookings SET payment_status = 'Paid', status = 'Completed' WHERE id = '{$payment['booking_id']}'");
                    $success = "Balance payment confirmed! Your booking is now fully paid.";
                    $reloadQuery = mysqli_query($conn, "
                        SELECT 
                            h.*,
                            b.id as booking_id,
                            c.company_name,
                            u.email,
                            u.phone
                        FROM payment_history h
                        JOIN bookings b ON h.booking_id = b.id
                        JOIN companies c ON b.company_id = c.id
                        JOIN users u ON b.user_id = u.id
                        WHERE h.id = '$history_id'
                        AND b.user_id = '$user_id'
                    ");
                } else {
                    mysqli_query($conn, "UPDATE bookings SET payment_status = 'Partial', status = 'Confirmed' WHERE id = '{$payment['booking_id']}'");
                    $success = "Payment confirmed! Your booking is now confirmed.";
                    $reloadQuery = mysqli_query($conn, "
                        SELECT 
                            p.*,
                            b.id as booking_id,
                            c.company_name,
                            u.email,
                            u.phone
                        FROM payments p
                        JOIN bookings b ON p.booking_id = b.id
                        JOIN companies c ON b.company_id = c.id
                        JOIN users u ON p.user_id = u.id
                        WHERE p.id = '$payment_id'
                        AND p.user_id = '$user_id'
                    ");
                }

                $payment = mysqli_fetch_assoc($reloadQuery);
                header("Location: booking_confirmation.php?booking_id=" . $payment['booking_id']);
                exit;
            } else {
                $error = "Error updating payment. Please try again.";
            }
        }
    }
}

$statusClass = strtolower($payment['payment_status']);
$isCompleted = $payment['payment_status'] == 'Completed';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
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
                radial-gradient(circle at top left, rgba(37, 99, 235, .18), transparent 32%),
                radial-gradient(circle at top right, rgba(245, 158, 11, .16), transparent 30%),
                linear-gradient(135deg, #f8fafc 0%, #eef4ff 50%, #f8fafc 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        .payment-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 38px 20px 70px;
        }

        /* HEADER HERO */
        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: 34px;
            color: white;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #f59e0b 130%);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: -80px -80px auto auto;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
        }

        .hero-content {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.24);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(28px, 5vw, 46px);
            line-height: 1.05;
            letter-spacing: -1px;
        }

        .hero p {
            margin: 12px 0 0;
            color: rgba(255,255,255,.82);
            font-size: 15px;
        }

        .hero-amount {
            min-width: 245px;
            padding: 22px;
            border-radius: 24px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.24);
            backdrop-filter: blur(12px);
        }

        .hero-amount span {
            display: block;
            font-size: 13px;
            color: rgba(255,255,255,.75);
        }

        .hero-amount strong {
            display: block;
            font-size: 32px;
            margin-top: 5px;
        }

        /* STRUKTUR GRID UTAMA */
        .main-management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .column-side {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .full-width-bottom {
            grid-column: span 2;
            margin-top: 4px;
        }

        .card {
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(226,232,240,.9);
            border-radius: var(--radius);
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
            padding: 24px;
            width: 100%;
        }

        /* STATUS DISPLAY DESIGN */
        .status-card {
            display: flex;
            align-items: center;
            gap: 20px;
            border: none;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            position: relative;
            overflow: hidden;
            padding: 24px;
        }

        .status-card::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 6px;
            background: <?= $isCompleted ? 'linear-gradient(90deg, #16a34a, #86efac)' : 'linear-gradient(90deg, #f59e0b, #fde68a)' ?>;
        }

        .status-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-icon {
            width: 64px;
            height: 64px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            font-size: 32px;
            background: <?= $isCompleted ? '#dcfce7' : '#fffbeb' ?>;
            border: 1px solid <?= $isCompleted ? '#86efac' : '#fde68a' ?>;
            flex-shrink: 0;
        }

        .status-text-side h2 {
            margin: 0;
            font-size: 24px;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            margin-top: 6px;
            font-weight: 700;
            font-size: 12px;
            color: <?= $isCompleted ? '#166534' : '#92400e' ?>;
            background: <?= $isCompleted ? '#dcfce7' : '#fef3c7' ?>;
        }

        .status-card .status-hint {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
            max-width: 250px;
            margin-left: auto;
            text-align: right;
        }

        .section-title-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-title {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
        }

        /* PAYMENT DETAILS FLOW STYLE */
        .payment-details-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .details-grid-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .info-card-box {
            padding: 16px;
            border-radius: 18px;
            background: var(--soft);
            border: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-card-box .label-text {
            font-size: 12px;
            color: var(--muted);
        }

        .info-card-box .value-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
        }

        /* CAPSULE PROGRESS INDICATOR */
        .orange-progress-card {
            padding: 18px;
            border-radius: 20px;
            background: #fffdf9;
            border: 1px solid #fbd59e;
            position: relative;
        }

        .progress-data-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .progress-data-row .data-item {
            font-size: 14px;
            font-weight: 700;
        }

        .custom-slider-bar {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .custom-slider-fill {
            height: 100%;
            width: <?= $depositPercent ?>%;
            background: linear-gradient(90deg, #2563eb 0%, #f59e0b 100%);
            border-radius: inherit;
        }

        .progress-footer-hint {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* INSTRUCTIONS & INFO SECTION */
        .instruction-flex-layout {
            display: flex;
            gap: 16px;
            align-items: stretch;
        }

        .instruction-side-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .notice {
            padding: 16px;
            border-radius: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.7;
        }

        .bank-box {
            padding: 18px;
            border-radius: 20px;
            background: #0f172a;
            color: white;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.1);
            min-width: 260px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .bank-box p {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px dashed rgba(255,255,255,.18);
            padding-bottom: 8px;
            font-size: 13px;
        }

        .bank-box p:last-child { border-bottom: none; padding-bottom: 0; }
        .bank-box span { color: #93c5fd; }
        .bank-box strong { text-align: right; }

        .method-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 220px;
        }

        .method-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid var(--line);
            flex: 1;
        }

        .method-item b { display: block; margin-bottom: 2px; }
        .method-item small { color: var(--muted); line-height: 1.5; font-size: 11px; }

        /* INPUT FORM COMPONENT */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #1e293b;
        }

        .hint {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 10px;
        }

        input[type="file"],
        input[type="text"] {
            width: 100%;
            padding: 14px 15px;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            background: white;
            font-family: Poppins, sans-serif;
            font-size: 14px;
            outline: none;
            transition: .2s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        /* BUTTON DESIGN SYSTEM */
        .btn-confirm {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 15px 18px;
            font-family: Poppins, sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            box-shadow: 0 12px 24px rgba(34,197,94,.25);
        }

        .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(34,197,94,.35); }

        .btn-secondary {
            display: inline-block;
            border: none;
            border-radius: 16px;
            padding: 12px 24px;
            font-family: Poppins, sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
            text-align: center;
            text-decoration: none;
            color: white;
            background: #2563eb;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .2);
        }
        .btn-secondary:hover { background: #1d4ed8; transform: translateY(-1px); }

        /* PREMIUM COMPLETED SUCCESS BOX DESIGN */
        .complete-card-layout {
            background: #ffffff;
            border: 1px solid #bbf7d0;
            padding: 32px;
            border-radius: var(--radius);
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }

        .complete-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #dcfce7;
            border: 1px solid #86efac;
            display: grid;
            place-items: center;
            font-size: 26px;
            flex-shrink: 0;
        }

        .complete-details-side h3 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 800;
            color: #14532d;
        }

        .complete-details-side p {
            margin: 0 0 16px;
            font-size: 14px;
            color: #334155;
            line-height: 1.6;
        }

        .complete-card-layout ul {
            margin: 0 0 24px;
            padding-left: 20px;
            line-height: 1.8;
            color: #475569;
            font-size: 13.5px;
        }

        /* PHONE RESPONSIVE MEDIA QUERIES */
        @media (max-width: 992px) {
            .main-management-grid { grid-template-columns: 1fr; }
            .full-width-bottom { grid-column: span 1; }
            .instruction-flex-layout { flex-direction: column; }
            .status-card { flex-direction: column; text-align: center; }
            .status-card .status-hint { text-align: center; margin: 10px auto 0; }
            .complete-card-layout { flex-direction: column; align-items: center; text-align: center; padding: 24px; }
            .complete-card-layout ul { text-align: left; }
        }
    </style>
</head>

<body>
<?php include('header.php'); ?>

<main class="payment-page">
    <section class="hero">
        <div class="hero-content">
            <div>
                <div class="badge">👑 Secure Payment Confirmation</div>
                <h1>Confirm Your Event Payment</h1>
                <p>Booking #<?= $payment['booking_id'] ?> • <?= htmlspecialchars($payment['company_name']) ?></p>
            </div>

            <div class="hero-amount">
                <span><?= $isHistory ? 'Balance Payment' : 'Deposit Payment' ?></span>
                <strong>RM <?= number_format($confirmAmount, 2) ?></strong>
                <span>
                    <?= $isHistory ? 'Final balance payment summary' : $depositPercent . '% from total booking amount' ?>
                </span>
            </div>
        </div>
    </section>

    <?php if ($error != "") { ?>
        <div class="alert error" style="padding: 16px 18px; border-radius: 18px; margin-bottom: 20px; font-weight: 600; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success" style="padding: 16px 18px; border-radius: 18px; margin-bottom: 20px; font-weight: 600; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">✅ <?= htmlspecialchars($success) ?></div>
    <?php } ?>

    <div class="main-management-grid">
        
        <div class="column-side">
            <div class="card status-card">
                <div class="status-left">
                    <div class="status-icon"><?= $isCompleted ? '✅' : '⏳' ?></div>
                    <div class="status-text-side">
                        <h2><?= htmlspecialchars($payment['payment_status']) ?></h2>
                        <div class="status-pill"><?= $isCompleted ? 'Payment Verified' : 'Waiting for Confirmation' ?></div>
                    </div>
                </div>
                <p class="status-hint">Your payment data status computation log report profile update view.</p>
            </div>

            <div class="card">
                <div class="section-title-container">
                    <span>📄</span>
                    <h3 class="section-title">Payment Details</h3>
                </div>

                <div class="payment-details-wrapper">
                    <div class="details-grid-layout">
                        <?php if (!$isHistory) { ?>
                            <div class="info-card-box">
                                <span class="label-text">Deposit Amount</span>
                                <span class="value-text">RM <?= number_format($payment['deposit_amount'], 2) ?></span>
                            </div>
                            <div class="info-card-box">
                                <span class="label-text">Payment Method</span>
                                <span class="value-text"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
                            </div>
                            <div class="info-card-box">
                                <span class="label-text">Total Amount</span>
                                <span class="value-text">RM <?= number_format($payment['amount'], 2) ?></span>
                            </div>
                            <div class="info-card-box">
                                <span class="label-text">Remaining Balance</span>
                                <span class="value-text">RM <?= number_format($payment['remaining_amount'], 2) ?></span>
                            </div>
                        <?php } else { ?>
                            <div class="info-card-box">
                                <span class="label-text">Balance Amount</span>
                                <span class="value-text">RM <?= number_format($payment['amount'], 2) ?></span>
                            </div>
                            <div class="info-card-box">
                                <span class="label-text">Payment Method</span>
                                <span class="value-text"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
                            </div>
                            <div class="info-card-box">
                                <span class="label-text">Transaction Reference</span>
                                <span class="value-text"><?= htmlspecialchars($payment['transaction_id'] ?: 'Not provided') ?></span>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="orange-progress-card">
                        <?php if (!$isHistory) { ?>
                            <div class="progress-data-row">
                                <div class="data-item" style="color: #c2410c;">Paid: <?= $depositPercent ?>%</div>
                                <div class="data-item" style="color: #1e293b;">Balance: <?= $remainingPercent ?>%</div>
                            </div>
                            <div class="custom-slider-bar">
                                <div class="custom-slider-fill"></div>
                            </div>
                            <p class="progress-footer-hint">Remaining balance is due before your event date.</p>
                        <?php } else { ?>
                            <div class="progress-data-row">
                                <div class="data-item" style="color: #16a34a;">Final balance payment submitted</div>
                            </div>
                            <div class="custom-slider-bar">
                                <div class="custom-slider-fill" style="width: 100%;"></div>
                            </div>
                            <p class="progress-footer-hint">Once accepted, your booking will be marked fully paid.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="column-side">
            <?php if ($payment['payment_status'] == 'Pending') { ?>
                
                <div class="card" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div class="section-title-container">
                            <span>🏛️</span>
                            <h3 class="section-title">Payment Instructions</h3>
                        </div>
                        
                        <div class="instruction-flex-layout">
                            <div class="instruction-side-text">
                                <div class="notice">
                                    Please make the payment to the account below. Use your booking ID as reference so the admin can verify faster.
                                </div>
                                <div class="method-list" style="margin-top: 14px;">
                                    <div class="method-item">
                                        <div>📱</div>
                                        <div><b>Online Banking / FPX</b><small>FPX Code: 01087000 • Ref: Booking #<?= $payment['booking_id'] ?></small></div>
                                    </div>
                                    <div class="method-item">
                                        <div>💼</div>
                                        <div><b>E-Wallet / Card</b><small>Touch n Go, Boost, Visa or Mastercard if available.</small></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bank-box">
                                <p><span>Bank Name</span><strong>MAYBANK BERHAD</strong></p>
                                <p><span>Account Holder</span><strong>EVENT SAAS MGMT</strong></p>
                                <p><span>Account Number</span><strong>170098765432</strong></p>
                                <p><span>Reference</span><strong>BOOKING #<?= $payment['booking_id'] ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php } else { ?>
                
                <div class="complete-card-layout" style="height: 100%;">
                    <div class="complete-icon-wrap">✔</div>
                    <div class="complete-details-side">
                        <h3>Payment Confirmed!</h3>
                        <?php if ($isHistory) { ?>
                            <p>Your balance payment has been successfully received and accepted. Your booking is now fully paid.</p>
                            <ul>
                                <li>A formal confirmation voucher will be sent to <strong><?= htmlspecialchars($payment['email']) ?></strong></li>
                                <li>Your final balance payment of <strong>RM <?= number_format($payment['amount'], 2) ?></strong> has been accepted.</li>
                                <li>Thank you for completing the payment. Your event will proceed as scheduled.</li>
                            </ul>
                        <?php } else { ?>
                            <p>Your deposit payment has been successfully received and logged. Your booking record is now securely activated.</p>
                            <ul>
                                <li>A formal confirmation voucher will be transmitted to <strong><?= htmlspecialchars($payment['email']) ?></strong></li>
                                <li>Kindly capture or keep this generation transaction summary reference safe.</li>
                                <li>The outstanding balance of <strong>RM <?= number_format($payment['remaining_amount'], 2) ?></strong> is required prior to fulfillment execution.</li>
                            </ul>
                        <?php } ?>
                        <a href="my_bookings.php" class="btn-secondary">← Back to My Bookings</a>
                    </div>
                </div>

            <?php } ?>
        </div>

        <?php if ($payment['payment_status'] == 'Pending') { ?>
            <div class="full-width-bottom">
                <div class="card">
                    <div class="section-title-container">
                        <span>📤</span>
                        <h3 class="section-title">Upload Payment Proof</h3>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="payment_proof">Receipt / Bank Slip</label>
                                <p class="hint">Upload PDF, JPG, PNG or GIF. Maximum file size is 5MB.</p>
                                <input type="file" id="payment_proof" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,.gif">
                            </div>

                            <div class="form-group">
                                <label for="reference">Reference / Transaction Number</label>
                                <p class="hint">Fill up validation text transaction row below.</p>
                                <input type="text" id="reference" name="bank_ref" placeholder="Example: FPX123456789 / MBB Transfer ID">
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <button type="submit" name="confirm_payment" class="btn-confirm">✓ Submit Payment Confirmation</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php } ?>

    </div>
</main>

</body>
</html>