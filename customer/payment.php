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

// Get booking details
$bookingQ = mysqli_query($conn, "
    SELECT 
        b.*,
        c.company_name,
        p.package_name,
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

// Calculate 10% deposit
$total_amount = $booking['total_price'];
$deposit_amount = $total_amount * 0.10; // 10% deposit
$remaining_amount = $total_amount * 0.90; // 90% remaining

// Check if payment already exists
$paymentQ = mysqli_query($conn, "
    SELECT * FROM payments
    WHERE booking_id = '$booking_id'
");

if (mysqli_num_rows($paymentQ) > 0) {
    $existing_payment = mysqli_fetch_assoc($paymentQ);
    if ($existing_payment['payment_status'] == 'Completed') {
        header("Location: my_bookings.php?msg=already_paid");
        exit;
    }
}

// Get payment methods
$methodsQ = mysqli_query($conn, "
    SELECT * FROM payment_methods
    WHERE is_active = TRUE
");

$error = "";
$success = "";

// Process payment submission
if (isset($_POST['submit_payment'])) {
    
    $payment_method = $_POST['payment_method'] ?? '';
    $bank_ref_number = $_POST['bank_ref_number'] ?? '';
    
    if ($payment_method == "") {
        $error = "Please select a payment method.";
    }
    else {
        // Create payment record
        $stmt = $conn->prepare("
            INSERT INTO payments 
            (booking_id, user_id, amount, deposit_amount, remaining_amount, payment_method, 
             bank_ref_number, payment_status, notes)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
        ");
        
        $notes = "Deposit payment for booking #" . $booking_id;
        
        $stmt->bind_param(
            "iidddss",
            $booking_id,
            $user_id,
            $total_amount,
            $deposit_amount,
            $remaining_amount,
            $payment_method,
            $bank_ref_number,
            $notes
        );
        
        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id;
            
            // Update booking status to Payment Pending
            mysqli_query($conn, "
                UPDATE bookings
                SET payment_status = 'Partial', status = 'Payment Pending'
                WHERE id = '$booking_id'
            ");
            
            // Redirect to payment verification/confirmation
            header("Location: payment_confirmation.php?payment_id=$payment_id");
            exit;
        } else {
            $error = "Error processing payment. Please try again.";
        }
    }
}

// Get vendor details for booking
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .payment-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }
        }

        .payment-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e0e0e0;
        }

        .payment-summary h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item.total {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .deposit-highlight {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .deposit-highlight strong {
            color: #1976d2;
            font-size: 18px;
        }

        .payment-form {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e0e0e0;
        }

        .payment-form h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Poppins, sans-serif;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .payment-methods {
            margin-bottom: 20px;
        }

        .method-option {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            margin-bottom: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .method-option:hover {
            border-color: #2196f3;
            background: #f5f5f5;
        }

        .method-option input[type="radio"] {
            margin-top: 4px;
            margin-right: 12px;
            cursor: pointer;
        }

        .method-option.selected {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .method-info {
            flex: 1;
        }

        .method-info h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .method-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        .btn-pay {
            width: 100%;
            padding: 12px;
            background: #2196f3;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-pay:hover {
            background: #1976d2;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .booking-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .booking-details p {
            margin: 8px 0;
        }

        .booking-details strong {
            color: #333;
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-container">

    <div style="margin-bottom: 30px;">
        <a href="my_bookings.php" class="back-link">← Back to My Bookings</a>
        <h2 style="margin-top: 10px; margin-bottom: 5px;">Complete Your Deposit Payment</h2>
        <p class="muted">Booking #<?= $booking_id ?> • <?= $booking['company_name'] ?></p>
    </div>

    <?php if ($error != "") { ?>
        <div class="alert error"><?= $error ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success"><?= $success ?></div>
    <?php } ?>

    <div class="payment-grid">

        <!-- Left: Order Summary -->
        <div>
            <div class="payment-summary">
                <h3>📋 Booking Summary</h3>
                
                <div class="booking-details">
                    <p><strong>Company:</strong> <?= $booking['company_name'] ?></p>
                    <p><strong>Package:</strong> <?= $booking['package_name'] ?></p>
                    <p><strong>Event Date:</strong> <?= date('d F Y', strtotime($booking['event_date'])) ?></p>
                    <p><strong>Pax:</strong> <?= $booking['pax'] ?></p>
                </div>

                <h3 style="margin-top: 25px; margin-bottom: 15px;">💰 Payment Breakdown</h3>

                <div class="summary-item">
                    <span>Package Base Price:</span>
                    <strong>RM <?= number_format($booking['total_price'], 2) ?></strong>
                </div>

                <?php if (count($vendors) > 0) { ?>
                    <div style="margin: 15px 0; padding: 15px 0; border-bottom: 1px solid #ddd;">
                        <strong style="display: block; margin-bottom: 10px;">Add-ons:</strong>
                        <?php foreach ($vendors as $vendor) { ?>
                            <div class="summary-item" style="padding: 5px 0; margin-bottom: 5px; border: none;">
                                <span style="font-size: 13px;"><?= $vendor['vendor_name'] ?> (<?= $vendor['service_type'] ?>)</span>
                                <span style="font-size: 13px;">RM <?= number_format($vendor['price'], 2) ?></span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="summary-item total" style="margin-top: 20px;">
                    <span>Total Amount:</span>
                    <span>RM <?= number_format($total_amount, 2) ?></span>
                </div>

                <div class="deposit-highlight">
                    <strong>Deposit (10%):</strong><br>
                    <strong>RM <?= number_format($deposit_amount, 2) ?></strong><br>
                    <small style="color: #666;">Rest of 90% (RM <?= number_format($remaining_amount, 2) ?>) due before event date</small>
                </div>

            </div>
        </div>

        <!-- Right: Payment Form -->
        <div>
            <form method="POST" class="payment-form">

                <h3>💳 Choose Payment Method</h3>

                <div class="payment-methods">
                    <?php while ($method = mysqli_fetch_assoc($methodsQ)) { ?>
                        <label class="method-option">
                            <input type="radio" 
                                   name="payment_method" 
                                   value="<?= $method['method_code'] ?>"
                                   onchange="updateMethodInfo('<?= $method['method_code'] ?>')">
                            <div class="method-info">
                                <h4><?= $method['method_name'] ?></h4>
                                <p><?= $method['description'] ?></p>
                            </div>
                        </label>
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="bank_ref">Reference/Transaction Number (Optional)</label>
                    <input type="text" 
                           id="bank_ref" 
                           name="bank_ref_number" 
                           placeholder="e.g., Bank transfer reference or transaction ID"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: flex-start;">
                        <input type="checkbox" 
                               name="agree_terms" 
                               required 
                               style="margin-top: 4px; width: auto; margin-right: 10px;">
                        <span>I agree that I am making a 10% deposit payment of <strong>RM <?= number_format($deposit_amount, 2) ?></strong>. 
                               The remaining balance of <strong>RM <?= number_format($remaining_amount, 2) ?></strong> is due before the event date.</span>
                    </label>
                </div>

                <button type="submit" name="submit_payment" class="btn-pay">
                    💳 Proceed to Payment (RM <?= number_format($deposit_amount, 2) ?>)
                </button>

            </form>
        </div>

    </div>

</div>

<?php include('header.php'); /* Close HTML */ ?>

</body>
</html>
