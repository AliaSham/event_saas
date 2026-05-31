<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['payment_id'])) {
    header("Location: my_bookings.php");
    exit;
}

$payment_id = intval($_GET['payment_id']);

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

if (mysqli_num_rows($paymentQ) == 0) {
    header("Location: my_bookings.php");
    exit;
}

$payment = mysqli_fetch_assoc($paymentQ);

$error = "";
$success = "";

if (isset($_POST['confirm_payment'])) {
    $bank_ref_number = trim($_POST['bank_ref'] ?? '');
    $has_file = isset($_FILES['payment_proof']) && $_FILES['payment_proof']['size'] > 0;

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

                $new_filename = 'proof_' . $payment_id . '_' . time() . '.' . $file_ext;

                if (!move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $error = "Error uploading file. Please try again.";
                }
            }
        }

        if ($error === '') {
            if ($new_filename !== null) {
                $stmt = $conn->prepare("UPDATE payments SET payment_proof_file = ?, bank_ref_number = ?, payment_status = 'Completed', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $new_filename, $bank_ref_number, $payment_id);
            } else {
                $stmt = $conn->prepare("UPDATE payments SET bank_ref_number = ?, payment_status = 'Completed', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $bank_ref_number, $payment_id);
            }

            if ($stmt->execute()) {
                mysqli_query($conn, "UPDATE bookings SET payment_status = 'Partial', status = 'Confirmed' WHERE id = '{$payment['booking_id']}'");
                $success = "Payment confirmed! Your booking is now confirmed.";

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

                $payment = mysqli_fetch_assoc($paymentQ);
            } else {
                $error = "Error updating payment. Please try again.";
            }
        }
    }
}

$statusClass = strtolower($payment['payment_status']);
$isCompleted = $payment['payment_status'] == 'Completed';
$depositPercent = ($payment['amount'] > 0) ? round(($payment['deposit_amount'] / $payment['amount']) * 100) : 0;
$remainingPercent = max(0, 100 - $depositPercent);
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

        .grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(226,232,240,.9);
            border-radius: var(--radius);
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
            padding: 24px;
            margin-bottom: 22px;
        }

        .status-card {
            text-align: center;
            border: none;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            position: relative;
            overflow: hidden;
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

        .status-icon {
            width: 88px;
            height: 88px;
            display: grid;
            place-items: center;
            border-radius: 26px;
            margin: 10px auto 16px;
            font-size: 44px;
            background: <?= $isCompleted ? '#dcfce7' : '#fffbeb' ?>;
            border: 1px solid <?= $isCompleted ? '#86efac' : '#fde68a' ?>;
        }

        .status-card h2 {
            margin: 0;
            font-size: 26px;
        }

        .status-pill {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            margin-top: 12px;
            font-weight: 700;
            font-size: 13px;
            color: <?= $isCompleted ? '#166534' : '#92400e' ?>;
            background: <?= $isCompleted ? '#dcfce7' : '#fef3c7' ?>;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 18px;
            font-size: 19px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-box {
            padding: 16px;
            border-radius: 18px;
            background: var(--soft);
            border: 1px solid var(--line);
        }

        .info-box span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .info-box strong {
            font-size: 16px;
        }

        .progress-wrap {
            margin-top: 20px;
            padding: 18px;
            border-radius: 20px;
            background: linear-gradient(135deg, #fff7ed, #fff);
            border: 1px solid #fed7aa;
        }

        .progress-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 13px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .progress-fill {
            height: 100%;
            width: <?= $depositPercent ?>%;
            background: linear-gradient(90deg, #2563eb, #f59e0b);
            border-radius: inherit;
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
            margin: 14px 0 18px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.1);
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
            display: grid;
            gap: 12px;
        }

        .method-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid var(--line);
        }

        .method-item b { display: block; margin-bottom: 2px; }
        .method-item small { color: var(--muted); line-height: 1.5; }

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

        .btn-confirm,
        .btn-secondary {
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
            text-decoration: none;
            display: inline-block;
        }

        .btn-confirm {
            color: white;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            box-shadow: 0 12px 24px rgba(34,197,94,.25);
        }

        .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(34,197,94,.35); }

        .btn-secondary {
            color: white;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            margin-top: 12px;
        }

        .alert {
            padding: 16px 18px;
            border-radius: 18px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .complete-card {
            background: linear-gradient(135deg, #f0fdf4, #ffffff);
            border: 1px solid #bbf7d0;
        }

        .complete-card ul {
            margin: 12px 0 0;
            padding-left: 20px;
            line-height: 1.9;
            color: #334155;
        }

        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .hero { padding: 26px; }
        }

        @media (max-width: 560px) {
            .payment-page { padding: 22px 14px 50px; }
            .info-grid { grid-template-columns: 1fr; }
            .bank-box p { display: block; }
            .bank-box strong { display: block; text-align: left; margin-top: 4px; }
        }
    </style>
</head>

<body>
<?php include('header.php'); ?>

<main class="payment-page">
    <section class="hero">
        <div class="hero-content">
            <div>
                <div class="badge">💳 Secure Payment Confirmation</div>
                <h1>Confirm Your Event Payment</h1>
                <p>Booking #<?= $payment['booking_id'] ?> • <?= htmlspecialchars($payment['company_name']) ?></p>
            </div>

            <div class="hero-amount">
                <span>Deposit Payment</span>
                <strong>RM <?= number_format($payment['deposit_amount'], 2) ?></strong>
                <span><?= $depositPercent ?>% from total booking amount</span>
            </div>
        </div>
    </section>

    <?php if ($error != "") { ?>
        <div class="alert error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success">✅ <?= htmlspecialchars($success) ?></div>
    <?php } ?>

    <section class="grid">
        <div>
            <div class="card status-card">
                <div class="status-icon"><?= $isCompleted ? '✅' : '⏳' ?></div>
                <h2><?= htmlspecialchars($payment['payment_status']) ?></h2>
                <div class="status-pill"><?= $isCompleted ? 'Payment Verified' : 'Waiting for Confirmation' ?></div>
                <p class="hint" style="margin-top:14px;">Your payment status will be updated after proof/reference is submitted.</p>
            </div>

            <div class="card">
                <h3 class="section-title">🧾 Payment Details</h3>
                <div class="info-grid">
                    <div class="info-box">
                        <span>Deposit Amount</span>
                        <strong>RM <?= number_format($payment['deposit_amount'], 2) ?></strong>
                    </div>
                    <div class="info-box">
                        <span>Payment Method</span>
                        <strong><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></strong>
                    </div>
                    <div class="info-box">
                        <span>Total Amount</span>
                        <strong>RM <?= number_format($payment['amount'], 2) ?></strong>
                    </div>
                    <div class="info-box">
                        <span>Remaining Balance</span>
                        <strong>RM <?= number_format($payment['remaining_amount'], 2) ?></strong>
                    </div>
                </div>

                <div class="progress-wrap">
                    <div class="progress-row">
                        <span>Paid: <?= $depositPercent ?>%</span>
                        <span>Balance: <?= $remainingPercent ?>%</span>
                    </div>
                    <div class="progress-bar"><div class="progress-fill"></div></div>
                    <p class="hint" style="margin:12px 0 0;">Remaining balance is due before your event date.</p>
                </div>
            </div>
        </div>

        <div>
            <?php if ($payment['payment_status'] == 'Pending') { ?>
                <div class="card">
                    <h3 class="section-title">🏦 Payment Instructions</h3>
                    <div class="notice">
                        Please make the payment to the account below. Use your booking ID as reference so the admin can verify faster.
                    </div>

                    <div class="bank-box">
                        <p><span>Bank Name</span><strong>MAYBANK BERHAD</strong></p>
                        <p><span>Account Holder</span><strong>EVENT SAAS MANAGEMENT</strong></p>
                        <p><span>Account Number</span><strong>170098765432</strong></p>
                        <p><span>Reference</span><strong>BOOKING #<?= $payment['booking_id'] ?></strong></p>
                    </div>

                    <div class="method-list">
                        <div class="method-item">
                            <div>📱</div>
                            <div><b>Online Banking / FPX</b><small>FPX Code: 01087000 • Reference: Booking #<?= $payment['booking_id'] ?></small></div>
                        </div>
                        <div class="method-item">
                            <div>💼</div>
                            <div><b>E-Wallet / Card</b><small>Touch n Go, Boost, Visa or Mastercard if available.</small></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title">📤 Upload Payment Proof</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="payment_proof">Receipt / Bank Slip</label>
                            <p class="hint">Upload PDF, JPG, PNG or GIF. Maximum file size is 5MB.</p>
                            <input type="file" id="payment_proof" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,.gif">
                        </div>

                        <div class="form-group">
                            <label for="reference">Reference / Transaction Number</label>
                            <input type="text" id="reference" name="bank_ref" placeholder="Example: FPX123456789 / MBB Transfer ID">
                        </div>

                        <button type="submit" name="confirm_payment" class="btn-confirm">✓ Submit Payment Confirmation</button>
                    </form>
                </div>
            <?php } else { ?>
                <div class="card complete-card">
                    <h3 class="section-title">✅ Payment Confirmed!</h3>
                    <p>Your deposit payment has been received and your booking is confirmed.</p>
                    <ul>
                        <li>Confirmation email will be sent to <strong><?= htmlspecialchars($payment['email']) ?></strong></li>
                        <li>Save your booking details for your record.</li>
                        <li>Remaining balance RM <?= number_format($payment['remaining_amount'], 2) ?> is due before the event date.</li>
                    </ul>
                    <a href="my_bookings.php" class="btn-secondary">← Back to My Bookings</a>
                </div>
            <?php } ?>
        </div>
    </section>
</main>

</body>
</html>
