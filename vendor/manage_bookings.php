<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* FETCH BOOKINGS */
$query = "
SELECT b.*, u.name AS customer_name, p.package_name
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN packages p ON b.package_id = p.id
WHERE b.company_id = '$company_id'
ORDER BY b.id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Bookings</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

<style>
body { font-family:'Poppins'; background:#f5f6fa; }

.container {
    max-width:900px;
    margin:50px auto;
}

.card {
    background:white;
    padding:20px;
    border-radius:12px;
    margin-bottom:15px;
    box-shadow:0 4px 10px rgba(0,0,0,0.08);
}

.actions button {
    margin-right:10px;
    padding:8px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

.approve { background:#28a745; color:white; }
.reject { background:#dc3545; color:white; }
</style>
</head>

<body>

<div class="container">

<h2>Manage Bookings</h2>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<div class="card">

    <h3><?= $row['package_name'] ?></h3>

    <p><b>Customer:</b> <?= $row['customer_name'] ?></p>
    <p><b>Date:</b> <?= $row['event_date'] ?></p>
    <p><b>Total:</b> RM <?= $row['total_price'] ?></p>
    <p><b>Status:</b> <?= $row['status'] ?></p>

    <?php if($row['status'] == 'Pending'){ ?>
    <div class="actions">

        <form method="POST" action="update_booking.php" style="display:inline;">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="status" value="Approved">
            <button class="approve">Approve</button>
        </form>

        <form method="POST" action="update_booking.php" style="display:inline;">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="status" value="Rejected">
            <button class="reject">Reject</button>
        </form>

    </div>
    <?php } ?>

</div>

<?php } ?>

</div>

</body>
</html>