<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

if (isset($_POST['approve_payment'])) {
    $payment_id = intval($_POST['payment_id']);

    $stmt = $conn->prepare("
        UPDATE payments
        SET payment_status = 'Completed', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        $payQ = mysqli_query($conn, "
            SELECT b.id AS booking_id 
            FROM payments p 
            JOIN bookings b ON p.booking_id = b.id 
            WHERE p.id = '$payment_id' AND b.company_id = '$company_id'
        ");
        $payData = mysqli_fetch_assoc($payQ);

        if ($payData) {
            mysqli_query($conn, "
                UPDATE bookings
                SET payment_status = 'Partial', status = 'Confirmed'
                WHERE id = '{$payData['booking_id']}'
            ");
        }
    }
}

if (isset($_POST['reject_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $reject_reason = $_POST['reject_reason'] ?? '';

    $payQ = mysqli_query($conn, "
        SELECT p.id 
        FROM payments p 
        JOIN bookings b ON p.booking_id = b.id 
        WHERE p.id = '$payment_id' AND b.company_id = '$company_id'
    ");

    if (mysqli_num_rows($payQ) > 0) {
        $stmt = $conn->prepare("
            UPDATE payments
            SET payment_status = 'Failed', notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reject_reason, $payment_id);
        $stmt->execute();
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'Pending';

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
    $query .= " WHERE b.company_id = '$company_id' AND p.payment_status = '$filter'";
} else {
    $query .= " WHERE b.company_id = '$company_id'";
}

$query .= " ORDER BY p.created_at DESC";
$paymentsQ = mysqli_query($conn, $query);

$statsQ = mysqli_query($conn, "
    SELECT 
        p.payment_status,
        COUNT(*) as count,
        SUM(p.deposit_amount) as total_amount
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE b.company_id = '$company_id'
    GROUP BY p.payment_status
");

$stats = [];
while ($stat = mysqli_fetch_assoc($statsQ)) {
    $stats[$stat['payment_status']] = $stat;
}

$all_count = 0;
$all_amount = 0;
foreach ($stats as $data) {
    $all_count += $data['count'];
    $all_amount += $data['total_amount'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(0, 255, 153, 0.10), transparent 30%),
                linear-gradient(135deg, #f6fbf8, #eef5f2);
            color: #0f172a;
        }

        .payment-management-container {
            padding: 40px 45px;
            max-width: 1180px;
            margin: 0 auto;
        }

        .hero-payment {
            background: linear-gradient(135deg, #06281b, #0b4d32, #12b76a);
            border-radius: 28px;
            padding: 34px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 24px 55px rgba(6, 40, 27, 0.25);
            margin-bottom: 28px;
        }

        .hero-payment::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            top: -90px;
            right: -70px;
        }

        .hero-payment h1 {
            font-size: 32px;
            margin: 0 0 8px;
            font-weight: 800;
        }

        .hero-payment p {
            margin: 0;
            opacity: .85;
            font-size: 14px;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 13px;
            backdrop-filter: blur(10px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(255,255,255,0.86);
            padding: 24px;
            border-radius: 22px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 5px;
            width: 100%;
            background: #0ea5e9;
        }

        .stat-card.pending::before { background: #f59e0b; }
        .stat-card.completed::before { background: #22c55e; }
        .stat-card.failed::before { background: #ef4444; }

        .stat-card h3 {
            margin: 0 0 12px;
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .stat-card .amount {
            font-size: 25px;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-card .count {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .panel {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 26px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .panel-header {
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-tabs a {
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            color: #475569;
            background: #f1f5f9;
            font-size: 13px;
            font-weight: 600;
            transition: .25s ease;
        }

        .filter-tabs a:hover,
        .filter-tabs a.active {
            background: #06281b;
            color: #fff;
            transform: translateY(-2px);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .payments-table thead {
            background: #f8fafc;
        }

        .payments-table th {
            padding: 16px 18px;
            text-align: left;
            font-size: 12px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .payments-table td {
            padding: 18px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            vertical-align: middle;
        }

        .payments-table tbody tr {
            transition: .25s ease;
        }

        .payments-table tbody tr:hover {
            background: #f8fafc;
        }

        .customer-name {
            font-weight: 700;
            color: #0f172a;
        }

        .customer-email {
            color: #64748b;
            font-size: 11px;
        }

        .amount-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            line-height: 1.8;
            min-width: 180px;
        }

        .amount-box strong {
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-badge.pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge.failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .payment-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .payment-actions button,
        .payment-actions a,
        .proof-link {
            border: none;
            border-radius: 12px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: .25s ease;
            display: inline-block;
        }

        .approve {
            background: #16a34a;
            color: white;
        }

        .reject-btn {
            background: #ef4444;
            color: white;
        }

        .view-proof,
        .proof-link {
            background: #e0f2fe;
            color: #0369a1;
        }

        .payment-actions button:hover,
        .payment-actions a:hover,
        .proof-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15,23,42,0.12);
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state .icon {
            font-size: 46px;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .empty-state p {
            color: #64748b;
            margin: 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(7px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            padding: 28px;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.25);
            max-width: 520px;
            width: 92%;
            animation: pop .25s ease;
        }

        @keyframes pop {
            from {
                opacity: 0;
                transform: scale(.95) translateY(15px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }

        .close-btn {
            background: #f1f5f9;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 22px;
            cursor: pointer;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #334155;
        }

        .form-group textarea {
            width: 100%;
            min-height: 120px;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            font-family: Poppins, sans-serif;
            resize: vertical;
            outline: none;
        }

        .form-group textarea:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239,68,68,0.12);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .modal-actions button {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-cancel {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-confirm {
            background: #ef4444;
            color: white;
        }

        @media (max-width: 768px) {
            .payment-management-container {
                padding: 25px 18px;
            }

            .hero-top,
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-payment h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="payment-management-container">

    <div class="hero-payment">
        <div class="hero-top">
            <div>
                <h1>💳 Payment Management</h1>
                <p>Monitor, verify and manage customer deposit payments in one premium dashboard.</p>
            </div>
            <div class="hero-badge">
                Secure Admin Verification
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <?php foreach ($stats as $status => $data) { ?>
            <div class="stat-card <?= strtolower($status) ?>">
                <h3><?= $status ?></h3>
                <div class="amount">RM <?= number_format($data['total_amount'] ?? 0, 2) ?></div>
                <div class="count"><?= $data['count'] ?> transaction(s)</div>
            </div>
        <?php } ?>

        <div class="stat-card">
            <h3>Total Collection</h3>
            <div class="amount">RM <?= number_format($all_amount, 2) ?></div>
            <div class="count"><?= $all_count ?> total transaction(s)</div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Payment Records</h2>

            <div class="filter-tabs">
                <a href="?filter=All" class="<?= $filter == 'All' ? 'active' : '' ?>">All</a>
                <a href="?filter=Pending" class="<?= $filter == 'Pending' ? 'active' : '' ?>">Pending</a>
                <a href="?filter=Completed" class="<?= $filter == 'Completed' ? 'active' : '' ?>">Completed</a>
                <a href="?filter=Failed" class="<?= $filter == 'Failed' ? 'active' : '' ?>">Failed</a>
            </div>
        </div>

        <?php if (mysqli_num_rows($paymentsQ) == 0) { ?>
            <div class="empty-state">
                <div class="icon">🧾</div>
                <h3>No payments found</h3>
                <p>There are no <?= $filter ?> payments at the moment.</p>
            </div>
        <?php } else { ?>
            <div class="table-wrap">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Customer</th>
                            <th>Company</th>
                            <th>Event Date</th>
                            <th>Amount Breakdown</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Proof</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($payment = mysqli_fetch_assoc($paymentsQ)) { ?>
                            <tr>
                                <td><strong>#<?= $payment['booking_id'] ?></strong></td>

                                <td>
                                    <div class="customer-name"><?= $payment['customer_name'] ?></div>
                                    <div class="customer-email"><?= $payment['customer_email'] ?></div>
                                </td>

                                <td><?= $payment['company_name'] ?></td>

                                <td><?= date('d M Y', strtotime($payment['event_date'])) ?></td>

                                <td>
                                    <div class="amount-box">
                                        <div><strong>Total:</strong> RM <?= number_format($payment['amount'], 2) ?></div>
                                        <div><strong style="color:#16a34a;">Deposit:</strong> RM <?= number_format($payment['deposit_amount'], 2) ?></div>
                                        <div><strong style="color:#64748b;">Remaining:</strong> RM <?= number_format($payment['remaining_amount'], 2) ?></div>
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
                                        <a class="proof-link" href="../uploads/payments/<?= $payment['payment_proof_file'] ?>" target="_blank">
                                            View Proof
                                        </a>
                                    <?php } else { ?>
                                        <small>-</small>
                                    <?php } ?>
                                </td>

                                <td>
                                    <div class="payment-actions">
                                        <?php if ($payment['payment_status'] == 'Pending') { ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <button type="submit" name="approve_payment" class="approve" onclick="return confirm('Approve this payment?')">
                                                    Approve
                                                </button>
                                            </form>

                                            <button type="button" onclick="openRejectModal(<?= $payment['id'] ?>)" class="reject-btn">
                                                Reject
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
            </div>
        <?php } ?>
    </div>

</div>

<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reject Payment</h2>
            <button type="button" class="close-btn" onclick="closeRejectModal()">×</button>
        </div>

        <form method="POST">
            <input type="hidden" id="rejectPaymentId" name="payment_id">

            <div class="form-group">
                <label for="reject_reason">Reason for Rejection</label>
                <textarea name="reject_reason" id="reject_reason" placeholder="Write the reason here..." required></textarea>
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

<?php include('footer.php'); ?>