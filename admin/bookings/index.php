<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* GET BOOKINGS */
$result = mysqli_query($conn, "
    SELECT b.*, u.name as customer_name, p.package_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN packages p ON b.package_id = p.id
    WHERE b.company_id='$company_id'
    ORDER BY b.id DESC
");

/* GET VENDORS */
$vendors = mysqli_query($conn, "
    SELECT id, name FROM users 
    WHERE role='vendor' AND company_id='$company_id'
");

/* HANDLE ACTION */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        mysqli_query($conn, "UPDATE bookings SET status='Approved' WHERE id='$id'");
    }

    if ($action == "reject") {
        mysqli_query($conn, "UPDATE bookings SET status='Rejected' WHERE id='$id'");
    }

    header("Location: index.php");
    exit;
}

/* ASSIGN VENDOR */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $booking_id = $_POST['booking_id'];
    $vendor_id = $_POST['vendor_id'];

    mysqli_query($conn, "
        UPDATE bookings 
        SET vendor_id='$vendor_id'
        WHERE id='$booking_id'
    ");

    header("Location: index.php");
    exit;
}
?>

<div class="container">

<div class="page-header">
    <div>
        <h2>Manage Bookings</h2>
        <p class="muted">Approve and assign vendors</p>
    </div>
</div>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="card-row">

    <div>
        <div class="card-title"><?= $row['package_name'] ?></div>
        <div class="card-sub">
            <?= $row['customer_name'] ?> • <?= $row['event_date'] ?>
        </div>

        <div class="card-sub">
            Status: <b><?= $row['status'] ?></b>
        </div>
    </div>

    <div class="card-actions">

        <!-- APPROVAL -->
        <?php if ($row['status'] == 'Pending'){ ?>
            <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-success">Approve</a>
            <a href="?action=reject&id=<?= $row['id'] ?>" class="btn btn-danger">Reject</a>
        <?php } ?>

        <!-- ASSIGN VENDOR -->
        <form method="POST" style="display:flex; gap:8px;">
            <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">

            <select name="vendor_id" required>
                <option value="">Assign Vendor</option>

                <?php 
                mysqli_data_seek($vendors, 0);
                while($v=mysqli_fetch_assoc($vendors)){ 
                ?>
                    <option value="<?= $v['id'] ?>">
                        <?= $v['name'] ?>
                    </option>
                <?php } ?>
            </select>

            <button class="btn btn-primary">Assign</button>
        </form>

    </div>

</div>

<?php } ?>

</div>