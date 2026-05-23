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

// Get payment details
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

// Handle payment confirmation
if (isset($_POST['confirm_payment'])) {
    
    // Validate payment proof file upload
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['size'] > 0) {
        
        $file_name = $_FILES['payment_proof']['name'];
        $file_tmp = $_FILES['payment_proof']['tmp_name'];
        $file_size = $_FILES['payment_proof']['size'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Allowed file types
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array(strtolower($file_ext), $allowed_exts)) {
            $error = "Invalid file type. Allowed: PDF, JPG, PNG, GIF";
        } elseif ($file_size > 5000000) { // 5MB limit
            $error = "File size too large. Max 5MB allowed.";
        } else {
            // Create upload directory if not exists
            $upload_dir = '../uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $new_filename = 'proof_' . $payment_id . '_' . time() . '.' . $file_ext;
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                
                // Update payment with proof file and mark as completed
                $stmt = $conn->prepare("
                    UPDATE payments
                    SET payment_proof_file = ?, payment_status = 'Completed', updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("si", $new_filename, $payment_id);
                
                if ($stmt->execute()) {
                    
                    // Update booking status to Confirmed
                    mysqli_query($conn, "
                        UPDATE bookings
                        SET payment_status = 'Partial', status = 'Confirmed'
                        WHERE id = '{$payment['booking_id']}'
                    ");
                    
                    $success = "Payment confirmed! Your booking is now confirmed. You will receive a confirmation email shortly.";
                    
                    // Refresh payment data
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
            } else {
                $error = "Error uploading file. Please try again.";
            }
        }
    } else {
        // Allow confirmation without proof (admin can follow up)
        $stmt = $conn->prepare("
            UPDATE payments
            SET payment_status = 'Completed', updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $payment_id);
        
        if ($stmt->execute()) {
            mysqli_query($conn, "
                UPDATE bookings
                SET payment_status = 'Partial', status = 'Confirmed'
                WHERE id = '{$payment['booking_id']}'
            ");
            
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
        }
    }
}

// Get bank details for display
$bankDetailsQ = mysqli_query($conn, "
    SELECT * FROM payment_methods
    WHERE is_active = TRUE
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .payment-status {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .payment-status.pending {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .payment-status.completed {
            background: #d4edda;
            border: 1px solid #28a745;
        }

        .payment-status h2 {
            margin: 10px 0;
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .instruction-box {
            background: #f0f7ff;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .instruction-box strong {
            color: #1976d2;
        }

        .bank-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-family: monospace;
        }

        .bank-details p {
            margin: 5px 0;
            word-break: break-all;
        }

        .bank-details .label {
            font-weight: 600;
            color: #333;
            font-family: Poppins, sans-serif;
            font-size: 13px;
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

        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Poppins, sans-serif;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .btn-confirm {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-confirm:hover {
            background: #218838;
        }

        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-details > div {
            font-size: 14px;
        }

        .payment-details strong {
            color: #333;
        }

        @media (max-width: 600px) {
            .payment-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="confirmation-container">

    <div style="margin-bottom: 30px;">
        <h2 style="margin-bottom: 5px;">Payment Confirmation</h2>
        <p class="muted">Booking #<?= $payment['booking_id'] ?> • <?= $payment['company_name'] ?></p>
    </div>

    <?php if ($error != "") { ?>
        <div class="alert error"><?= $error ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success"><?= $success ?></div>
    <?php } ?>

    <!-- Payment Status -->
    <div class="payment-status <?= strtolower($payment['payment_status']) ?>">
        <div class="status-icon">
            <?php if ($payment['payment_status'] == 'Completed') { ?>
                ✅
            <?php } else { ?>
                ⏳
            <?php } ?>
        </div>
        <h2><?= $payment['payment_status'] ?></h2>
        <p>Deposit Payment: <strong>RM <?= number_format($payment['deposit_amount'], 2) ?></strong></p>
    </div>

    <div class="confirmation-card">

        <h3>📝 Payment Details</h3>

        <div class="payment-details">
            <div>
                <strong>Deposit Amount:</strong>
                RM <?= number_format($payment['deposit_amount'], 2) ?>
            </div>
            <div>
                <strong>Payment Method:</strong>
                <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?>
            </div>
            <div>
                <strong>Total Amount:</strong>
                RM <?= number_format($payment['amount'], 2) ?>
            </div>
            <div>
                <strong>Remaining Balance:</strong>
                RM <?= number_format($payment['remaining_amount'], 2) ?>
            </div>
        </div>

    </div>

    <!-- Payment Instructions -->
    <?php if ($payment['payment_status'] == 'Pending') { ?>
        
        <div class="confirmation-card">
            <h3>💳 Payment Instructions</h3>

            <div class="instruction-box">
                <strong>⚠️ Important:</strong> Please make the payment to the account details below using your preferred payment method.
            </div>

            <h4>Bank Transfer / FPX Details:</h4>
            <div class="bank-details">
                <p><span class="label">Bank Name:</span> MAYBANK BERHAD</p>
                <p><span class="label">Account Holder:</span> EVENT SAAS MANAGEMENT</p>
                <p><span class="label">Account Number:</span> 170098765432</p>
                <p><span class="label">Reference:</span> USE BOOKING ID #<?= $payment['booking_id'] ?></p>
            </div>

            <h4>Online Banking (Malaysia):</h4>
            <div class="instruction-box">
                <p>You can also transfer using your bank's mobile app or online banking.</p>
                <p><strong>FPX Code:</strong> 01087000</p>
                <p><strong>Reference:</strong> Booking #<?= $payment['booking_id'] ?></p>
            </div>

            <h4>Other Payment Methods:</h4>
            <div class="instruction-box">
                <p>• <strong>E-Wallet:</strong> GCash, Boost, Touch n Go</p>
                <p>• <strong>Credit Card:</strong> Visa, Mastercard (if available)</p>
                <p>Contact our support for alternative payment methods.</p>
            </div>

            <!-- Upload Proof Form -->
            <form method="POST" enctype="multipart/form-data" style="margin-top: 30px;">

                <div class="form-group">
                    <label for="payment_proof">📸 Upload Payment Proof (Optional)</label>
                    <p class="muted" style="font-size: 13px; margin: 5px 0 10px 0;">
                        Upload screenshot, transfer receipt or bank slip. Allowed formats: PDF, JPG, PNG, GIF (Max 5MB)
                    </p>
                    <input type="file" 
                           id="payment_proof" 
                           name="payment_proof" 
                           accept=".pdf,.jpg,.jpeg,.png,.gif">
                </div>

                <div class="form-group">
                    <label for="reference">Reference / Transaction Number (Optional)</label>
                    <input type="text" 
                           id="reference" 
                           name="bank_ref" 
                           placeholder="e.g., FPX Reference, Bank Transaction ID">
                </div>

                <button type="submit" name="confirm_payment" class="btn-confirm">
                    ✓ Confirm Payment Received
                </button>

            </form>

        </div>

    <?php } else { ?>

        <!-- Confirmed -->
        <div class="confirmation-card" style="background: #e8f5e9; border-color: #28a745;">
            <h3>✅ Payment Confirmed!</h3>
            <p>Your deposit payment has been received and your booking is confirmed.</p>
            
            <div class="instruction-box" style="background: #e8f5e9; border-color: #28a745;">
                <strong>Next Steps:</strong>
                <p style="margin-top: 10px;">
                    • You will receive a confirmation email at <strong><?= $payment['email'] ?></strong><br>
                    • Save the booking details for your records<br>
                    • Remaining balance (RM <?= number_format($payment['remaining_amount'], 2) ?>) is due before the event date<br>
                    • Check your booking details for any additional requirements
                </p>
            </div>

            <a href="my_bookings.php" class="btn btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none;">
                ← Back to My Bookings
            </a>
        </div>

    <?php } ?>

</div>

<?php include('header.php'); /* Close HTML */ ?>

</body>
</html>
