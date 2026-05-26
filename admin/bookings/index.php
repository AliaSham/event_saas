<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* HANDLE ACTION */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        mysqli_query($conn, "
            UPDATE bookings 
            SET status='Confirmed' 
            WHERE id='$id' 
            AND company_id='$company_id'
        ");
    }

    if ($action == "reject") {
        mysqli_query($conn, "
            UPDATE bookings 
            SET status='Rejected' 
            WHERE id='$id' 
            AND company_id='$company_id'
        ");
    }

    header("Location: index.php");
    exit;
}

/* GET BOOKINGS */
$result = mysqli_query($conn, "
    SELECT 
        b.*,
        u.name AS customer_name,
        u.email AS customer_email,
        p.package_name,
        p.pax,
        p.menu,
        p.description AS package_description
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN packages p ON b.package_id = p.id
    WHERE b.company_id='$company_id'
    ORDER BY b.id DESC
");
?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Manage Bookings</h2>
            <p class="muted">View customer bookings and approve or reject requests</p>
        </div>
    </div>

    <?php if (mysqli_num_rows($result) == 0) { ?>
        <div class="empty-state">
            <h3>No bookings yet</h3>
            <p>Customer bookings will appear here.</p>
        </div>
    <?php } ?>

    <div class="booking-list">

        <?php while($row = mysqli_fetch_assoc($result)) { ?>

            <?php
            $booking_id = $row['id'];

            /* GET SELECTED ADD-ON / VENDORS */
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
            ?>

            <div class="booking-card">

                <div class="booking-card-header">

                    <div>
                        <h3><?= $row['package_name'] ?></h3>
                        <p class="muted">
                            Booking ID: #<?= $row['id'] ?>
                        </p>
                    </div>

                    <span class="status-badge status-<?= strtolower($row['status']) ?>">
                        <?= $row['status'] ?>
                    </span>

                </div>

                <div class="booking-info-grid">

                    <div>
                        <p><strong>Customer:</strong></p>
                        <p><?= $row['customer_name'] ?></p>
                    </div>

                    <div>
                        <p><strong>Email:</strong></p>
                        <p><?= $row['customer_email'] ?></p>
                    </div>

                    <div>
                        <p><strong>Event Date:</strong></p>
                        <p><?= $row['event_date'] ?></p>
                    </div>

                    <div>
                        <p><strong>Total Price:</strong></p>
                        <p>RM <?= number_format($row['total_price'], 2) ?></p>
                    </div>

                    <div>
                        <p><strong>Pax:</strong></p>
                        <p><?= $row['pax'] ?> pax</p>
                    </div>

                    <div>
                        <p><strong>Status:</strong></p>
                        <p><?= $row['status'] ?></p>
                    </div>

                </div>

                <div class="booking-section">
                    <p><strong>Package Description:</strong></p>
                    <p><?= $row['package_description'] ?></p>
                </div>

                <div class="booking-section">
                    <p><strong>Menu:</strong></p>
                    <p><?= $row['menu'] ?></p>
                </div>

                <div class="booking-section">
                    <p><strong>Selected Add-On / Vendor:</strong></p>

                    <?php if (mysqli_num_rows($vendorQ) == 0) { ?>

                        <p>No add-on selected.</p>

                    <?php } else { ?>

                        <div class="addon-list">

                            <?php while($vendor = mysqli_fetch_assoc($vendorQ)) { ?>

                                <div class="addon-item">
                                    <div>
                                        <strong><?= $vendor['vendor_name'] ?></strong>
                                        <p><?= $vendor['service_type'] ?></p>
                                    </div>

                                    <span>
                                        RM <?= number_format($vendor['price'], 2) ?>
                                    </span>
                                </div>

                            <?php } ?>

                        </div>

                    <?php } ?>

                </div>

                <div class="card-actions" style="margin-top:20px;">

                    <?php if ($row['status'] == 'Pending') { ?>

                        <a href="index.php?action=approve&id=<?= $row['id'] ?>" 
                           class="btn btn-success"
                           onclick="return confirm('Approve this booking?');">
                            Approve
                        </a>

                        <a href="index.php?action=reject&id=<?= $row['id'] ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Reject this booking?');">
                            Reject
                        </a>

                    <?php } else { ?>

                        <span class="muted">
                            No action available
                        </span>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

</div>

<?php include('../footer.php'); ?>