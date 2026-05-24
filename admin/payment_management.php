<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle payment approval
if (isset($_POST['approve_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    
    $stmt = $conn->prepare("
        UPDATE payments
        SET payment_status = 'Completed', updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $payment_id);
    
    if ($stmt->execute()) {
        // Update booking status
        $payQ = mysqli_query($conn, "SELECT booking_id FROM payments WHERE id = '$payment_id'");
        $payData = mysqli_fetch_assoc($payQ);
        
        mysqli_query($conn, "
            UPDATE bookings
            SET payment_status = 'Partial', status = 'Confirmed'
            WHERE id = '{$payData['booking_id']}'
        ");
    }
}

// Handle payment rejection
if (isset($_POST['reject_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $reject_reason = $_POST['reject_reason'] ?? '';
    
    $stmt = $conn->prepare("
        UPDATE payments
        SET payment_status = 'Failed', notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("si", $reject_reason, $payment_id);
    $stmt->execute();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'Pending';

// Get payments based on filter
$query = "
    SELECT 
        p.*,
        b.event_date,
        b.total_price,
        c.company_name,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN companies c ON b.company_id = c.id
    JOIN users u ON p.user_id = u.id
";

if ($filter != 'All') {
    $query .= " WHERE p.payment_status = '$filter'";
}

$query .= " ORDER BY p.created_at DESC";

$paymentsQ = mysqli_query($conn, $query);

// Get payment statistics
$statsQ = mysqli_query($conn, "
    SELECT 
        payment_status,
        COUNT(*) as count,
        SUM(deposit_amount) as total_amount
    FROM payments
    GROUP BY payment_status
");

$stats = [];
while ($stat = mysqli_fetch_assoc($statsQ)) {
    $stats[$stat['payment_status']] = $stat;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
    <style>
        .payment-management-container {
            margin: 30px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card.pending {
            border-left-color: #ffc107;
        }

        .stat-card.completed {
            border-left-color: #28a745;
        }

        .stat-card.failed {
            border-left-color: #dc3545;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }

        .stat-card .amount {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .stat-card .count {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0;
            flex-wrap: wrap;
        }

        .filter-tabs a {
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: -2px;
        }

        .filter-tabs a:hover {
            color: #2196f3;
        }

        .filter-tabs a.active {
            color: #2196f3;
            border-bottom-color: #2196f3;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payments-table thead {
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            border-bottom: 2px solid #e0e0e0;
        }

        .payments-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .payments-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .payments-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
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

        .payment-actions {
            display: flex;
            gap: 5px;
        }

        .payment-actions button,
        .payment-actions a {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #333;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .payment-actions button:hover,
        .payment-actions a:hover {
            background: #f0f0f0;
        }

        .payment-actions .approve {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .payment-actions .approve:hover {
            background: #c3e6cb;
        }

        .payment-actions .view-proof {
            background: #e7f3ff;
            border-color: #2196f3;
            color: #0056b3;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Poppins, sans-serif;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-confirm {
            background: #dc3545;
            color: white;
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="container payment-management-container">

    <div class="page-header">
        <h2 class="page-title">💳 Payment Management</h2>
        <p class="muted">Monitor and verify customer deposits</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <?php 
        $all_count = 0;
        $all_amount = 0;
        foreach ($stats as $status => $data) {
            $all_count += $data['count'];
            $all_amount += $data['total_amount'];
        ?>
            <div class="stat-card <?= strtolower($status) ?>">
                <h3><?= $status ?></h3>
                <div class="amount">RM <?= number_format($data['total_amount'] ?? 0, 2) ?></div>
                <div class="count"><?= $data['count'] ?> transaction(s)</div>
            </div>
        <?php } ?>
        <div class="stat-card">
            <h3>Total</h3>
            <div class="amount">RM <?= number_format($all_amount, 2) ?></div>
            <div class="count"><?= $all_count ?> transaction(s)</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=All" class="<?= $filter == 'All' ? 'active' : '' ?>">All</a>
        <a href="?filter=Pending" class="<?= $filter == 'Pending' ? 'active' : '' ?>">Pending</a>
        <a href="?filter=Completed" class="<?= $filter == 'Completed' ? 'active' : '' ?>">Completed</a>
        <a href="?filter=Failed" class="<?= $filter == 'Failed' ? 'active' : '' ?>">Failed</a>
    </div>

    <!-- Payments Table -->
    <?php if (mysqli_num_rows($paymentsQ) == 0) { ?>
        <div class="empty-state">
            <h3>No payments found</h3>
            <p>There are no <?= $filter ?> payments at the moment.</p>
        </div>
    <?php } else { ?>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Company</th>
                    <th>Event Date</th>
                    <th>Amount Breakdown</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Proof</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($payment = mysqli_fetch_assoc($paymentsQ)) { ?>
                    <tr>
                        <td>#<?= $payment['booking_id'] ?></td>
                        <td>
                            <strong><?= $payment['customer_name'] ?></strong><br>
                            <small><?= $payment['customer_email'] ?></small>
                        </td>
                        <td><?= $payment['company_name'] ?></td>
                        <td><?= date('d M Y', strtotime($payment['event_date'])) ?></td>
                        <td>
                            <div style="font-size: 12px;">
                                <div style="margin-bottom: 5px;">
                                    <strong style="color: #333;">Total:</strong> RM <?= number_format($payment['amount'], 2) ?>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <strong style="color: #28a745;">Deposit (10%):</strong> RM <?= number_format($payment['deposit_amount'], 2) ?>
                                </div>
                                <div>
                                    <strong style="color: #999;">Remaining (90%):</strong> RM <?= number_format($payment['remaining_amount'], 2) ?>
                                </div>
                            </div>
                        </td>
                        <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($payment['payment_status']) ?>">
                                <?= $payment['payment_status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($payment['payment_proof_file'])) { ?>
                                <a href="../uploads/payments/<?= $payment['payment_proof_file'] ?>" target="_blank" style="color: #2196f3; text-decoration: none;">
                                    📄 View
                                </a>
                            <?php } else { ?>
                                <small>-</small>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="payment-actions">
                                <?php if ($payment['payment_status'] == 'Pending') { ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                        <button type="submit" name="approve_payment" class="approve" onclick="return confirm('Approve this payment?')">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal(<?= $payment['id'] ?>)" class="reject-btn" style="background: #f8d7da; border-color: #dc3545; color: #721c24;">
                                        ✗ Reject
                                    </button>
                                <?php } else { ?>
                                    <a href="payment_details.php?id=<?= $payment['id'] ?>" class="view-proof">View</a>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>

</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reject Payment</h2>
            <button type="button" class="close-btn" onclick="closeRejectModal()">×</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" id="rejectPaymentId" name="payment_id">
            <div class="form-group">
                <label for="reject_reason">Reason for Rejection</label>
                <textarea name="reject_reason" id="reject_reason" placeholder="Enter reason for rejecting this payment..." required></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" name="reject_payment" class="btn-confirm">Reject Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(paymentId) {
    document.getElementById('rejectPaymentId').value = paymentId;
    document.getElementById('rejectModal').classList.add('active');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
    document.getElementById('reject_reason').value = '';
}

window.onclick = function(event) {
    var modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        closeRejectModal();
    }
}
</script>

</body>
</html>
