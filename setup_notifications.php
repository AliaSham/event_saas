<?php
session_start();
include('config/db.php');

// Security check - only allow access from localhost or with a secret key
$secret_key = $_GET['key'] ?? '';
$allowed_key = md5('setup_admin_notifications_2026');

// Check if admin is logged in OR valid secret key provided
$is_admin = isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin';
$has_valid_key = $secret_key === $allowed_key;

if (!$is_admin && !$has_valid_key) {
    http_response_code(403);
    echo "❌ Access Denied. Please login as admin first.";
    exit;
}

$message = '';
$error = '';
$table_exists = false;

// Check if table already exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'admin_notifications'");
$table_exists = mysqli_num_rows($check_table) > 0;

// Create table if not exists
if (!$table_exists) {
    $sql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL COMMENT 'payment_deposit, payment_completed, payment_failed, booking_created, etc',
        message TEXT NOT NULL,
        company_id INT,
        booking_id INT,
        payment_id INT,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
        INDEX idx_type (type),
        INDEX idx_company (company_id),
        INDEX idx_read (is_read),
        INDEX idx_created (created_at)
    )";

    if (mysqli_query($conn, $sql)) {
        $message = "✅ SUCCESS! admin_notifications table created successfully!";
        $table_exists = true;
    } else {
        $error = "❌ ERROR: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - Setup</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .setup-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .setup-header p {
            color: #666;
            font-size: 14px;
        }

        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-box.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .table-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .table-info h3 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .table-info ul {
            list-style: none;
            color: #555;
            line-height: 1.8;
        }

        .table-info li::before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 15px 0;
        }

        .footer-text {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <div class="icon">🔔</div>
            <h1>Admin Notifications Setup</h1>
            <p>Create database table for payment notifications</p>
        </div>

        <?php if ($error): ?>
            <div class="status-box error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="status-box success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($table_exists): ?>
            <div class="table-info">
                <h3>✅ Table Information</h3>
                <ul>
                    <li>Table: <strong>admin_notifications</strong></li>
                    <li>Status: <strong>Ready to Use</strong></li>
                    <li>Features: Payment notifications, Booking alerts</li>
                    <li>Auto-reads: Yes</li>
                </ul>
            </div>

            <div class="status-box info">
                📧 The notification system is now active! 
                <br><br>
                Admins will receive notifications when:
                <ul style="margin-top: 10px; margin-left: 20px; list-style: disc;">
                    <li>Customers submit deposit payments</li>
                    <li>Payment status changes</li>
                    <li>New bookings are created</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="admin/admin_notifications.php" class="btn btn-success">View Notifications</a>
                <a href="admin/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="table-info">
                <h3>What This Will Do</h3>
                <ul>
                    <li>Create admin_notifications table</li>
                    <li>Setup payment deposit alerts</li>
                    <li>Enable notification dashboard</li>
                    <li>Add notification badge to admin header</li>
                </ul>
            </div>

            <div class="status-box info">
                Click "Create Table" below to continue...
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <?php if (!$table_exists): ?>
                <form method="GET" style="width: 100%;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        🚀 Create Table Now
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="footer-text">
            <p>✅ Your notification system is set up!</p>
            <p>You can now <a href="admin/dashboard.php" style="color: #007bff; text-decoration: none;">go to dashboard</a></p>
        </div>
    </div>
</body>
</html>
