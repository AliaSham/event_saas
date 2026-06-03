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

$bookingQ = mysqli_query($conn, "
    SELECT 
        b.*,
        c.company_name,
        p.package_name,
        p.pax,
        u.email,
        u.phone
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

$total_amount = $booking['total_price'];
$deposit_amount = $total_amount * 0.10;
$remaining_amount = $total_amount * 0.90;

$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] === 'balance' ? 'balance' : 'deposit';

$paymentQ = mysqli_query($conn, "
    SELECT * FROM payments
    WHERE booking_id = '$booking_id'
");

$existing_payment = null;
if (mysqli_num_rows($paymentQ) > 0) {
    $existing_payment = mysqli_fetch_assoc($paymentQ);
}

if ($pay_type === 'deposit') {
    if ($existing_payment && $existing_payment['payment_status'] == 'Completed') {
        header("Location: my_bookings.php?msg=already_paid");
        exit;
    }
} else {
    if (!$existing_payment || $existing_payment['payment_status'] != 'Completed' || $booking['payment_status'] != 'Partial') {
        header("Location: payment.php?booking_id=$booking_id");
        exit;
    }
}

$payment_label = $pay_type === 'balance' ? 'Remaining Balance Payment' : 'Deposit Payment';
$payment_heading = $pay_type === 'balance' ? 'Complete Your Balance Payment' : 'Complete Your Deposit Payment';
$payment_step = $pay_type === 'balance' ? 'Balance Payment' : 'Deposit Payment';
$payment_amount = $pay_type === 'balance' ? $remaining_amount : $deposit_amount;

$methodsQ = mysqli_query($conn, "
    SELECT * FROM payment_methods
    WHERE is_active = TRUE
");

$error = "";
$success = "";

if (isset($_POST['submit_payment'])) {

    $payment_method = $_POST['payment_method'] ?? '';
    $bank_ref_number = $_POST['bank_ref_number'] ?? '';

    if ($payment_method == "") {
        $error = "Please select a payment method.";
    } else {
        if ($pay_type === 'balance') {
            mysqli_query($conn, "
                CREATE TABLE IF NOT EXISTS payment_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    booking_id INT NOT NULL,
                    payment_type VARCHAR(50) NOT NULL,
                    amount DECIMAL(10, 2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    transaction_id VARCHAR(100),
                    payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
                    payment_proof_file VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
                )
            ");

            $stmt = $conn->prepare("
                INSERT INTO payment_history
                (booking_id, payment_type, amount, payment_method, transaction_id, payment_status)
                VALUES (?, 'Balance', ?, ?, ?, 'Pending')
            ");

            $stmt->bind_param(
                "idss",
                $booking_id,
                $remaining_amount,
                $payment_method,
                $bank_ref_number
            );
        } else {
            $notes = "Deposit payment for booking #" . $booking_id;

            $existingQ = mysqli_query($conn, "SELECT id FROM payments WHERE booking_id = '$booking_id'");

            if (mysqli_num_rows($existingQ) > 0) {
                $existingRow = mysqli_fetch_assoc($existingQ);
                $payment_id = $existingRow['id'];

                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET amount = ?, deposit_amount = ?, remaining_amount = ?, payment_method = ?, 
                        bank_ref_number = ?, payment_status = 'Pending', notes = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "dddsssi",
                    $total_amount,
                    $deposit_amount,
                    $remaining_amount,
                    $payment_method,
                    $bank_ref_number,
                    $notes,
                    $payment_id
                );
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO payments 
                    (booking_id, user_id, amount, deposit_amount, remaining_amount, payment_method, 
                     bank_ref_number, payment_status, notes)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
                ");

                $stmt->bind_param(
                    "iidddsss",
                    $booking_id,
                    $user_id,
                    $total_amount,
                    $deposit_amount,
                    $remaining_amount,
                    $payment_method,
                    $bank_ref_number,
                    $notes
                );
            }
        }

        if ($stmt->execute()) {
            if ($pay_type === 'balance') {
                $history_id = $stmt->insert_id;
                header("Location: payment_confirmation.php?history_id=$history_id");
                exit;
            }

            if (mysqli_num_rows($existingQ) == 0) {
                $payment_id = $stmt->insert_id;
            }

            mysqli_query($conn, "
                UPDATE bookings
                SET payment_status = 'Partial', status = 'Payment Pending'
                WHERE id = '$booking_id'
            ");

            $company_id = $booking['company_id'];
            $notification_message = "New deposit payment received! Customer: " . $booking['email'] . " | Amount: RM " . number_format($deposit_amount, 2) . " | Booking: #" . $booking_id;

            $notificationTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'admin_notifications'");
            if ($notificationTableCheck && mysqli_num_rows($notificationTableCheck) > 0) {
                mysqli_query($conn, "
                    INSERT INTO admin_notifications (type, message, company_id, booking_id, payment_id, is_read, created_at)
                    VALUES ('payment_deposit', '$notification_message', '$company_id', '$booking_id', '$payment_id', 0, NOW())
                ");
            }

            header("Location: payment_confirmation.php?payment_id=$payment_id");
            exit;
        } else {
            $error = "Error processing payment. Please try again.";
        }
    }
}

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

$vendors = [];
while ($v = mysqli_fetch_assoc($vendorQ)) {
    $vendors[] = $v;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment - Booking #<?= $booking_id ?></title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 34%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.14), transparent 36%),
        #f4f7fb;
    color: #0f172a;
}

.payment-container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 38px 24px 70px;
}

.back-link {
    display: inline-block;
    margin-bottom: 22px;
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.payment-hero {
    background: linear-gradient(135deg, #0f172a, #1d4ed8, #38bdf8);
    color: white;
    padding: 42px;
    border-radius: 30px;
    margin-bottom: 30px;
    box-shadow: 0 25px 55px rgba(37,99,235,.25);
}

.hero-badge {
    display: inline-block;
    padding: 8px 14px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.24);
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 16px;
}

.payment-hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
    letter-spacing: -.5px;
}

.payment-hero p {
    margin: 0;
    opacity: .9;
}

.payment-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}

.step-box {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 14px 32px rgba(15,23,42,.06);
}

.step-box small {
    color: #64748b;
}

.step-box strong {
    display: block;
    margin-top: 5px;
    color: #0f172a;
}

.step-box.active {
    border-color: #2563eb;
    background: #eff6ff;
}

.payment-grid {
    display: grid;
    grid-template-columns: 1.05fr .95fr;
    gap: 28px;
    align-items: start;
}

.payment-summary,
.payment-form {
    background: rgba(255,255,255,.96);
    border: 1px solid #e2e8f0;
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 22px 50px rgba(15,23,42,.09);
}

.payment-summary h3,
.payment-form h3 {
    margin: 0 0 20px;
    font-size: 21px;
}

.booking-details {
    display: grid;
    gap: 12px;
    margin-bottom: 26px;
}

.detail-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 15px;
}

.detail-box small {
    display: block;
    color: #64748b;
    margin-bottom: 5px;
    font-size: 12px;
}

.detail-box strong {
    font-size: 14px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 15px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
}

.summary-item span:first-child {
    color: #475569;
}

.summary-item.total {
    border-bottom: none;
    margin-top: 8px;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

.addon-block {
    margin: 18px 0;
    padding: 18px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.addon-block h4 {
    margin: 0 0 12px;
}

.deposit-highlight {
    margin-top: 24px;
    padding: 24px;
    border-radius: 22px;
    background: linear-gradient(135deg, #dbeafe, #eff6ff);
    border: 1px solid #bfdbfe;
}

.deposit-highlight small {
    display: block;
    color: #64748b;
    margin-bottom: 6px;
}

.deposit-highlight h2 {
    margin: 0 0 10px;
    color: #2563eb;
    font-size: 31px;
}

.balance-note {
    padding: 14px;
    background: white;
    border-radius: 15px;
    color: #334155;
    font-size: 13px;
}

.payment-methods {
    display: grid;
    gap: 14px;
    margin-bottom: 22px;
}

.method-option {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    padding: 18px;
    cursor: pointer;
    transition: .25s ease;
    background: white;
}

.method-option:hover {
    border-color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(37,99,235,.09);
}

.method-option input[type="radio"] {
    margin-top: 5px;
    accent-color: #2563eb;
}

.method-info h4 {
    margin: 0 0 5px;
    font-size: 15px;
}

.method-info p {
    margin: 0;
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}

.method-option:has(input:checked) {
    background: #eff6ff;
    border-color: #2563eb;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 9px;
    font-weight: 700;
    color: #334155;
    font-size: 13px;
}

.form-group input[type="text"] {
    width: 100%;
    padding: 14px 15px;
    border: 1px solid #dbe4f0;
    border-radius: 15px;
    font-family: Poppins, sans-serif;
    outline: none;
}

.form-group input[type="text"]:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,.12);
}

.terms-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 22px;
}

.terms-box label {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    font-size: 13px;
    color: #334155;
    line-height: 1.6;
}

.terms-box input {
    margin-top: 4px;
    accent-color: #2563eb;
}

.btn-pay {
    width: 100%;
    padding: 17px 20px;
    border: none;
    border-radius: 18px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    font-family: Poppins, sans-serif;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 30px rgba(37,99,235,.25);
    transition: .25s ease;
}

.btn-pay:hover {
    transform: translateY(-2px);
    filter: brightness(.97);
}

.secure-note {
    margin-top: 18px;
    padding: 15px;
    border-radius: 16px;
    background: #ecfdf5;
    color: #166534;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
}

.alert {
    padding: 15px 18px;
    border-radius: 16px;
    margin-bottom: 22px;
    font-weight: 600;
}

.alert.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert.success {
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
}

@media(max-width: 900px) {
    .payment-grid,
    .payment-steps {
        grid-template-columns: 1fr;
    }

    .payment-hero h1 {
        font-size: 30px;
    }
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-container">

    <a href="my_bookings.php" class="back-link">← Back to My Bookings</a>

    <section class="payment-hero">
        <span class="hero-badge">💳 Secure <?= htmlspecialchars($payment_label) ?></span>
        <h1><?= htmlspecialchars($payment_heading) ?></h1>
        <p>Booking #<?= $booking_id ?> • <?= htmlspecialchars($booking['company_name']) ?></p>
    </section>

    <div class="payment-steps">
        <div class="step-box">
            <small>Step 1</small>
            <strong>Booking Created</strong>
        </div>
        <div class="step-box active">
            <small>Step 2</small>
            <strong><?= htmlspecialchars($payment_step) ?></strong>
        </div>
        <div class="step-box">
            <small>Step 3</small>
            <strong>Confirmation</strong>
        </div>
    </div>

    <?php if ($error != "") { ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php } ?>

    <div class="payment-grid">

        <div class="payment-summary">
            <h3>📋 Booking Summary</h3>

            <div class="booking-details">
                <div class="detail-box">
                    <small>Company</small>
                    <strong><?= htmlspecialchars($booking['company_name']) ?></strong>
                </div>

                <div class="detail-box">
                    <small>Package</small>
                    <strong><?= htmlspecialchars($booking['package_name']) ?></strong>
                </div>

                <div class="detail-box">
                    <small>Event Date</small>
                    <strong><?= date('d F Y', strtotime($booking['event_date'])) ?></strong>
                </div>

                <div class="detail-box">
                    <small>Pax</small>
                    <strong><?= htmlspecialchars($booking['pax']) ?> pax</strong>
                </div>
            </div>

            <h3>💰 Payment Breakdown</h3>

            <div class="summary-item">
                <span>Total Package & Services</span>
                <strong>RM <?= number_format($booking['total_price'], 2) ?></strong>
            </div>

            <?php if (count($vendors) > 0) { ?>
                <div class="addon-block">
                    <h4>Add-ons / Vendors</h4>

                    <?php foreach ($vendors as $vendor) { ?>
                        <div class="summary-item" style="padding:8px 0;border-bottom:none;">
                            <span><?= htmlspecialchars($vendor['vendor_name']) ?> <br><small><?= htmlspecialchars($vendor['service_type']) ?></small></span>
                            <strong>RM <?= number_format($vendor['price'], 2) ?></strong>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="summary-item total">
                <span>Total Amount</span>
                <strong>RM <?= number_format($total_amount, 2) ?></strong>
            </div>

            <div class="deposit-highlight">
                <small><?= $pay_type === 'balance' ? 'Remaining Balance Due' : 'Deposit Required 10%' ?></small>
                <h2>RM <?= number_format($payment_amount, 2) ?></h2>

                <div class="balance-note">
                    Remaining balance:
                    <strong>RM <?= number_format($remaining_amount, 2) ?></strong>
                    <br>
                    Due before your event date.
                </div>
            </div>
        </div>

        <form method="POST" class="payment-form">

            <h3>💳 Choose Payment Method</h3>

            <div class="payment-methods">
                <?php while ($method = mysqli_fetch_assoc($methodsQ)) { ?>
                    <label class="method-option">
                        <input type="radio" 
                               name="payment_method" 
                               value="<?= htmlspecialchars($method['method_code']) ?>">

                        <div class="method-info">
                            <h4>
                                <?php
                                $code = strtolower($method['method_code']);
                                if (strpos($code, 'bank') !== false || strpos($code, 'fpx') !== false) {
                                    echo "🏦 ";
                                } elseif (strpos($code, 'card') !== false || strpos($code, 'credit') !== false) {
                                    echo "💳 ";
                                } elseif (strpos($code, 'wallet') !== false) {
                                    echo "📱 ";
                                } else {
                                    echo "🌐 ";
                                }
                                ?>
                                <?= htmlspecialchars($method['method_name']) ?>
                            </h4>
                            <p><?= htmlspecialchars($method['description']) ?></p>
                        </div>
                    </label>
                <?php } ?>
            </div>

            <div class="form-group">
                <label for="bank_ref">Reference / Transaction Number</label>
                <input type="text" 
                       id="bank_ref" 
                       name="bank_ref_number" 
                       placeholder="e.g., Bank transfer reference or transaction ID">
            </div>

            <div class="terms-box">
                <label>
                    <input type="checkbox" name="agree_terms" required>
                    <span>
                        I agree that I am making <?php if ($pay_type === 'balance') { ?>the remaining balance payment of<?php } else { ?>a 10% deposit payment of<?php } ?>
                        <strong>RM <?= number_format($payment_amount, 2) ?></strong>.
                        <?php if ($pay_type === 'balance') { ?>This will complete the final settlement for the booking.<?php } else { ?>The remaining balance of <strong>RM <?= number_format($remaining_amount, 2) ?></strong> is due before the event date.<?php } ?>
                    </span>
                </label>
            </div>

            <button type="submit" name="submit_payment" class="btn-pay">
                💳 Proceed to Payment — RM <?= number_format($payment_amount, 2) ?>
            </button>

            <div class="secure-note">
                🔒 Your payment record will be saved securely in the system.
            </div>

        </form>

    </div>

</div>

</body>
</html>