<?php
session_start();
include('config/db.php');

// Check payment tables and data
$checks = [];

// 1. Check if payments table exists
$paymentsCheck = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$checks['payments_table'] = mysqli_num_rows($paymentsCheck) > 0 ? 'EXISTS ✓' : 'MISSING ✗';

// 2. Check if payment_methods table exists
$methodsCheck = mysqli_query($conn, "SHOW TABLES LIKE 'payment_methods'");
$checks['payment_methods_table'] = mysqli_num_rows($methodsCheck) > 0 ? 'EXISTS ✓' : 'MISSING ✗';

// 3. Check if payment_history table exists
$historyCheck = mysqli_query($conn, "SHOW TABLES LIKE 'payment_history'");
$checks['payment_history_table'] = mysqli_num_rows($historyCheck) > 0 ? 'EXISTS ✓' : 'MISSING ✗';

// 4. Count payment methods
$methodCountQ = mysqli_query($conn, "SELECT COUNT(*) as count FROM payment_methods");
$methodCount = mysqli_fetch_assoc($methodCountQ);
$checks['payment_methods_count'] = $methodCount['count'] . ' methods configured';

// 5. Get payment methods list
$methodsListQ = mysqli_query($conn, "SELECT method_code, method_name, is_active FROM payment_methods");
$methods = [];
while ($m = mysqli_fetch_assoc($methodsListQ)) {
    $methods[] = $m;
}

// 6. Check if uploads/payments directory exists
$checks['uploads_directory'] = is_dir('uploads/payments') ? 'EXISTS ✓' : 'MISSING ✗ (Creating...)';
if (!is_dir('uploads/payments')) {
    @mkdir('uploads/payments', 0755, true);
}

// 7. Check bookings table has payment_status column
$bookingsStructure = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'payment_status'");
$checks['booking_payment_status'] = mysqli_num_rows($bookingsStructure) > 0 ? 'EXISTS ✓' : 'MISSING ✗';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment System - Setup Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }

        .check-item.success {
            background: #e8f5e9;
            border-left-color: #28a745;
        }

        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .check-item.error {
            background: #ffebee;
            border-left-color: #dc3545;
        }

        .check-label {
            font-weight: 500;
            color: #333;
        }

        .check-value {
            color: #666;
            font-size: 13px;
            font-weight: 600;
        }

        .check-item.success .check-value {
            color: #28a745;
        }

        .check-item.warning .check-value {
            color: #ffc107;
        }

        .check-item.error .check-value {
            color: #dc3545;
        }

        .methods-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px solid #e0e0e0;
        }

        .methods-list h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
        }

        .method-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .method-item:last-child {
            border-bottom: none;
        }

        .method-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .method-status.active {
            background: #d4edda;
            color: #155724;
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .summary {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .summary h4 {
            color: #1976d2;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .summary p {
            color: #0d47a1;
            font-size: 13px;
            margin: 5px 0;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>💳 Payment System Setup Verification</h1>
    <p class="subtitle">Checking if all components are properly configured...</p>

    <!-- Database Tables Check -->
    <div class="check-item <?= strpos($checks['payments_table'], '✓') !== false ? 'success' : 'error' ?>">
        <span class="check-label">Payments Table</span>
        <span class="check-value"><?= $checks['payments_table'] ?></span>
    </div>

    <div class="check-item <?= strpos($checks['payment_methods_table'], '✓') !== false ? 'success' : 'error' ?>">
        <span class="check-label">Payment Methods Table</span>
        <span class="check-value"><?= $checks['payment_methods_table'] ?></span>
    </div>

    <div class="check-item <?= strpos($checks['payment_history_table'], '✓') !== false ? 'success' : 'error' ?>">
        <span class="check-label">Payment History Table</span>
        <span class="check-value"><?= $checks['payment_history_table'] ?></span>
    </div>

    <div class="check-item <?= $methodCount['count'] > 0 ? 'success' : 'warning' ?>">
        <span class="check-label">Payment Methods Configured</span>
        <span class="check-value"><?= $checks['payment_methods_count'] ?></span>
    </div>

    <div class="check-item <?= strpos($checks['uploads_directory'], '✓') !== false ? 'success' : 'warning' ?>">
        <span class="check-label">Uploads Directory</span>
        <span class="check-value"><?= $checks['uploads_directory'] ?></span>
    </div>

    <div class="check-item <?= strpos($checks['booking_payment_status'], '✓') !== false ? 'success' : 'warning' ?>">
        <span class="check-label">Booking Payment Status Column</span>
        <span class="check-value"><?= $checks['booking_payment_status'] ?></span>
    </div>

    <!-- Payment Methods List -->
    <?php if (count($methods) > 0) { ?>
        <div class="methods-list">
            <h3>📋 Configured Payment Methods:</h3>
            <?php foreach ($methods as $method) { ?>
                <div class="method-item">
                    <span><?= $method['method_name'] ?></span>
                    <span class="method-status <?= $method['is_active'] ? 'active' : '' ?>">
                        <?= $method['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                    </span>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- Summary -->
    <div class="summary">
        <h4>✅ Status</h4>
        <?php 
        $allGood = true;
        foreach ($checks as $check) {
            if (strpos($check, '✗') !== false) {
                $allGood = false;
                break;
            }
        }
        ?>
        <?php if ($allGood && count($methods) > 0) { ?>
            <p>🎉 <strong>Everything looks good!</strong> Your payment system is ready to use.</p>
            <p>Go test it by creating a booking!</p>
        <?php } else { ?>
            <p>⚠️ <strong>Some issues detected.</strong> Please run the database setup SQL.</p>
        <?php } ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="customer/dashboard.php" class="btn btn-success">← Go to Dashboard</a>
        <a href="javascript:location.reload()" class="btn btn-primary">🔄 Refresh Check</a>
    </div>

</div>

</body>
</html>
