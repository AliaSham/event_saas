<?php
include('config/db.php');

$result = [];
$success = false;

try {
    // Drop the old payments table if it exists
    $dropResult = mysqli_query($conn, "DROP TABLE IF EXISTS payments");
    $result['drop_table'] = $dropResult ? '✓ Old table dropped' : '✗ Could not drop table';

    // Create the correct payments table
    $createPayments = "
    CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL UNIQUE,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        deposit_amount DECIMAL(10, 2) NOT NULL,
        remaining_amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
        transaction_id VARCHAR(100),
        payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        payment_proof_file VARCHAR(255),
        bank_ref_number VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $createResult = mysqli_query($conn, $createPayments);
    $result['create_payments'] = $createResult ? '✓ Payments table created' : '✗ Error: ' . mysqli_error($conn);

    // Create indexes
    $idx1 = mysqli_query($conn, "CREATE INDEX idx_booking_payment ON payments(booking_id)");
    $result['index_booking'] = $idx1 ? '✓ Booking index created' : '✗ Could not create index';

    $idx2 = mysqli_query($conn, "CREATE INDEX idx_user_payment ON payments(user_id)");
    $result['index_user'] = $idx2 ? '✓ User index created' : '✗ Could not create index';

    $idx3 = mysqli_query($conn, "CREATE INDEX idx_payment_status ON payments(payment_status)");
    $result['index_status'] = $idx3 ? '✓ Status index created' : '✗ Could not create index';

    $success = true;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment System - Fix Tables</title>
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

        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }

        .result-item.success {
            background: #e8f5e9;
            border-left-color: #28a745;
        }

        .result-item.error {
            background: #ffebee;
            border-left-color: #dc3545;
        }

        .result-label {
            font-weight: 500;
            color: #333;
        }

        .result-value {
            color: #666;
            font-size: 13px;
            font-weight: 600;
        }

        .result-item.success .result-value {
            color: #28a745;
        }

        .result-item.error .result-value {
            color: #dc3545;
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

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
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

    <h1>🔧 Payment System - Table Repair</h1>
    <p class="subtitle">Fixing the payments table structure...</p>

    <?php foreach ($result as $label => $value) { 
        $isSuccess = strpos($value, '✓') !== false;
    ?>
        <div class="result-item <?= $isSuccess ? 'success' : 'error' ?>">
            <span class="result-label"><?= ucfirst(str_replace('_', ' ', $label)) ?></span>
            <span class="result-value"><?= htmlspecialchars($value) ?></span>
        </div>
    <?php } ?>

    <div class="summary">
        <h4><?= $success ? '✅ Success!' : '⚠️ Issues Found' ?></h4>
        <p><?= $success ? 'The payments table has been successfully recreated with the correct structure.' : 'Please check the errors above and try again.' ?></p>
    </div>

    <div class="action-buttons">
        <a href="payment_check.php" class="btn btn-primary">← Check Setup</a>
        <a href="javascript:location.reload()" class="btn btn-success">🔄 Refresh</a>
    </div>

</div>

</body>
</html>
