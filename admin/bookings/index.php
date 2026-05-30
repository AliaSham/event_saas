<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        mysqli_query($conn, "UPDATE bookings SET status='Confirmed' WHERE id='$id' AND company_id='$company_id'");
    }

    if ($action == "reject") {
        mysqli_query($conn, "UPDATE bookings SET status='Rejected' WHERE id='$id' AND company_id='$company_id'");
    }

    header("Location: index.php");
    exit;
}

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

<style>
.container {
    padding: 40px 55px;
    background: #f6f8fb;
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #06281d, #0f5132, #16a34a);
    color: white;
    padding: 35px;
    border-radius: 26px;
    margin-bottom: 30px;
    box-shadow: 0 18px 45px rgba(0,0,0,0.12);
}

.page-title {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}

.muted {
    color: #6b7280;
    font-size: 13px;
}

.page-header .muted {
    color: #d1fae5;
}

.booking-list {
    display: grid;
    gap: 25px;
}

.booking-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 28px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
    transition: .3s ease;
}

.booking-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 25px 55px rgba(15, 23, 42, 0.13);
}

.booking-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eef2f7;
    padding-bottom: 18px;
    margin-bottom: 22px;
}

.booking-card-header h3 {
    font-size: 22px;
    font-weight: 800;
    color: #06281d;
}

.status-badge {
    padding: 9px 18px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-confirmed {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.booking-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    background: #f8fafc;
    padding: 20px;
    border-radius: 18px;
    margin-bottom: 22px;
}

.info-box {
    background: white;
    padding: 15px;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
}

.info-box strong {
    font-size: 12px;
    color: #64748b;
}

.info-box p:last-child {
    font-weight: 700;
    color: #111827;
    margin-top: 5px;
}

.booking-section {
    margin-top: 18px;
}

.booking-section strong {
    color: #06281d;
}

.booking-section p {
    margin-top: 6px;
    line-height: 1.6;
}

.addon-list {
    margin-top: 14px;
    display: grid;
    gap: 12px;
}

.addon-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border: 1px solid #e5e7eb;
    border-left: 5px solid #16a34a;
    padding: 16px 18px;
    border-radius: 18px;
}

.addon-item strong {
    color: #111827;
}

.addon-item p {
    color: #64748b;
    font-size: 13px;
    margin: 4px 0 0;
}

.addon-item span {
    font-weight: 800;
    color: #0f5132;
}

.card-actions {
    margin-top: 25px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn {
    padding: 11px 22px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 800;
    font-size: 13px;
    transition: .2s ease;
}

.btn-success {
    background: #16a34a;
    color: white;
}

.btn-success:hover {
    background: #15803d;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
}

.empty-state {
    background: white;
    border-radius: 22px;
    padding: 45px;
    text-align: center;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
}

.empty-state h3 {
    color: #06281d;
    font-size: 24px;
}

@media(max-width: 900px) {
    .container {
        padding: 25px;
    }

    .booking-info-grid {
        grid-template-columns: 1fr;
    }

    .booking-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<div class="container">

    <div class="page-header">
        <h2 class="page-title">Manage Bookings</h2>
        <p class="muted">View customer bookings, selected vendors, total price and approve or reject requests.</p>
    </div>

    <?php if (mysqli_num_rows($result) == 0) { ?>
        <div class="empty-state">
            <h3>No bookings yet</h3>
            <p class="muted">Customer bookings will appear here.</p>
        </div>
    <?php } ?>

    <div class="booking-list">

        <?php while($row = mysqli_fetch_assoc($result)) { ?>

            <?php
            $booking_id = $row['id'];

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
                        <p class="muted">Booking ID: #<?= $row['id'] ?></p>
                    </div>

                    <span class="status-badge status-<?= strtolower($row['status']) ?>">
                        <?= $row['status'] ?>
                    </span>
                </div>

                <div class="booking-info-grid">

                    <div class="info-box">
                        <strong>Customer</strong>
                        <p><?= $row['customer_name'] ?></p>
                    </div>

                    <div class="info-box">
                        <strong>Email</strong>
                        <p><?= $row['customer_email'] ?></p>
                    </div>

                    <div class="info-box">
                        <strong>Event Date</strong>
                        <p><?= $row['event_date'] ?></p>
                    </div>

                    <div class="info-box">
                        <strong>Total Price</strong>
                        <p>RM <?= number_format($row['total_price'], 2) ?></p>
                    </div>

                    <div class="info-box">
                        <strong>Pax</strong>
                        <p><?= $row['pax'] ?> pax</p>
                    </div>

                    <div class="info-box">
                        <strong>Status</strong>
                        <p><?= $row['status'] ?></p>
                    </div>

                </div>

                <div class="booking-section">
                    <strong>Package Description</strong>
                    <p><?= $row['package_description'] ?></p>
                </div>

                <div class="booking-section">
                    <strong>Menu</strong>
                    <p><?= $row['menu'] ?></p>
                </div>

                <div class="booking-section">
                    <strong>Selected Add-On / Vendor</strong>

                    <?php if (mysqli_num_rows($vendorQ) == 0) { ?>

                        <p class="muted">No add-on selected.</p>

                    <?php } else { ?>

                        <div class="addon-list">

                            <?php while($vendor = mysqli_fetch_assoc($vendorQ)) { ?>

                                <div class="addon-item">
                                    <div>
                                        <strong><?= $vendor['vendor_name'] ?></strong>
                                        <p><?= $vendor['service_type'] ?></p>
                                    </div>

                                    <span>RM <?= number_format($vendor['price'], 2) ?></span>
                                </div>

                            <?php } ?>

                        </div>

                    <?php } ?>
                </div>

                <div class="card-actions">

                    <?php if ($row['status'] == 'Pending') { ?>

                        <a href="index.php?action=approve&id=<?= $row['id'] ?>" 
                           class="btn btn-success"
                           onclick="return confirm('Approve this booking?');">
                            Approve Booking
                        </a>

                        <a href="index.php?action=reject&id=<?= $row['id'] ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Reject this booking?');">
                            Reject Booking
                        </a>

                    <?php } else { ?>

                        <span class="muted">No action available</span>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

</div>

<?php include('../footer.php'); ?>