<?php
session_start();
include('../config/db.php');
include('../admin/header.php'); // ✅ FIXED PATH

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

/* GET ASSIGNED BOOKINGS ONLY */
$result = mysqli_query($conn, "
    SELECT b.*, u.name as customer_name, p.package_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN packages p ON b.package_id = p.id
    WHERE b.vendor_id='$vendor_id'
    ORDER BY b.id DESC
");
?>

<div class="container">

<div class="page-header">
    <div>
        <h2>My Assigned Jobs</h2>
        <p class="muted">Assigned by admin</p>
    </div>
</div>

<?php if(mysqli_num_rows($result) == 0){ ?>
    <p class="muted">No jobs assigned yet.</p>
<?php } ?>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="card-row">

    <div>
        <div class="card-title"><?= $row['package_name'] ?></div>
        <div class="card-sub">
            <?= $row['customer_name'] ?> • <?= $row['event_date'] ?>
        </div>
    </div>

    <div class="card-actions">
        <span class="status-badge"><?= $row['status'] ?></span>
    </div>

</div>

<?php } ?>

</div>