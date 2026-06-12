<?php
session_start();
include('../../config/db.php');
include('../../config/plan.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$error = "";
$success = "";

$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

$countQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM company_vendors 
    WHERE company_id='$company_id'
");
$countData = mysqli_fetch_assoc($countQ);
$currentCount = $countData['total'];

if (isset($_GET['select'])) {
    $vendor_id = $_GET['select'];

    if ($currentCount >= $limits['services']) {
        $error = "You have reached your vendor limit for this plan.";
    } else {
        $check = mysqli_query($conn, "
            SELECT * FROM company_vendors
            WHERE company_id='$company_id'
            AND vendor_user_id='$vendor_id'
        ");

        if (mysqli_num_rows($check) > 0) {
            $error = "Vendor already selected.";
        } else {
            mysqli_query($conn, "
                INSERT INTO company_vendors (company_id, vendor_user_id)
                VALUES ('$company_id', '$vendor_id')
            ");

            header("Location: index.php");
            exit;
        }
    }
}

if (isset($_GET['remove'])) {
    $vendor_id = $_GET['remove'];

    mysqli_query($conn, "
        DELETE FROM company_vendors
        WHERE company_id='$company_id'
        AND vendor_user_id='$vendor_id'
    ");

    header("Location: index.php");
    exit;
}

$vendors = mysqli_query($conn, "
    SELECT 
        u.id AS vendor_id,
        u.name,
        u.email,
        vp.phone,
        vp.service_type,
        vp.price,
        vp.description,
        vp.service_details,
        vp.equipment,
        vp.image
    FROM users u
    JOIN vendor_profiles vp ON u.id = vp.user_id
    WHERE u.role='vendor'
    AND u.approval_status='active'
    AND vp.service_type IS NOT NULL
    AND vp.price IS NOT NULL
    ORDER BY u.name ASC
");

include('../header.php');
?>

<style>
.container {
    padding: 42px 55px;
    background: #f6f8fb;
    min-height: 100vh;
}

.vendor-hero {
    background: linear-gradient(135deg, #06281d, #0f5132, #16a34a);
    padding: 34px;
    border-radius: 28px;
    color: white;
    margin-bottom: 28px;
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.16);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vendor-hero h2 {
    font-size: 30px;
    font-weight: 900;
    margin-bottom: 8px;
}

.vendor-hero p {
    color: #dcfce7;
    font-size: 14px;
}

.limit-box {
    background: rgba(255,255,255,0.16);
    padding: 18px 22px;
    border-radius: 20px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.25);
}

.limit-box h3 {
    font-size: 28px;
    margin-bottom: 3px;
}

.limit-box span {
    font-size: 12px;
    color: #dcfce7;
}

.alert {
    padding: 15px 18px;
    border-radius: 16px;
    margin-bottom: 20px;
    font-weight: 700;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
}

.vendor-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 26px;
}

.vendor-card {
    background: white;
    border-radius: 26px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    box-shadow: 0 16px 42px rgba(15,23,42,0.08);
    transition: .3s ease;
    position: relative;
}

.vendor-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 26px 60px rgba(15,23,42,0.14);
}

.vendor-card-img {
    width: 100%;
    height: 195px;
    object-fit: cover;
}

.vendor-card-body {
    padding: 24px;
}

.vendor-card-body h3 {
    color: #06281d;
    font-size: 21px;
    font-weight: 900;
    margin-bottom: 10px;
}

.vendor-type {
    display: inline-block;
    background: #ecfdf5;
    color: #166534;
    padding: 8px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.vendor-info {
    margin-top: 12px;
    display: grid;
    gap: 11px;
}

.vendor-info p {
    font-size: 13px;
    color: #374151;
    line-height: 1.55;
}

.vendor-info strong {
    color: #111827;
}

.selected-ribbon {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #16a34a;
    color: white;
    padding: 8px 13px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    box-shadow: 0 8px 20px rgba(22,163,74,0.35);
}

.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 18px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    transition: .2s ease;
}

.btn-primary {
    background: #16a34a;
    color: white;
}

.btn-primary:hover {
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
    border-radius: 24px;
    padding: 45px;
    text-align: center;
    box-shadow: 0 16px 42px rgba(0,0,0,0.08);
}

.empty-state h3 {
    color: #06281d;
    font-size: 24px;
}

@media(max-width: 1100px) {
    .vendor-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 760px) {
    .container {
        padding: 25px;
    }

    .vendor-hero {
        flex-direction: column;
        align-items: flex-start;
        gap: 18px;
    }

    .vendor-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container">

    <div class="vendor-hero">
        <div>
            <h2>Manage Vendors</h2>
            <p>Select the best vendors for your event package and manage your vendor limit.</p>
        </div>

        <div class="limit-box">
            <h3><?= $currentCount ?> / <?= $limits['services'] ?></h3>
            <span>Vendors Selected</span>
        </div>
    </div>

    <?php if ($error != "") { ?>
        <div class="alert error"><?= $error ?></div>
    <?php } ?>

    <?php if ($success != "") { ?>
        <div class="alert success"><?= $success ?></div>
    <?php } ?>

    <?php if (mysqli_num_rows($vendors) == 0) { ?>
        <div class="empty-state">
            <h3>No vendors available</h3>
            <p>There are no active vendors with completed profiles yet.</p>
        </div>
    <?php } ?>

    <div class="vendor-grid">

        <?php while($vendor = mysqli_fetch_assoc($vendors)) { 

            $vendor_id = $vendor['vendor_id'];

            $selectedQ = mysqli_query($conn, "
                SELECT * FROM company_vendors
                WHERE company_id='$company_id'
                AND vendor_user_id='$vendor_id'
            ");

            $isSelected = mysqli_num_rows($selectedQ) > 0;
        ?>

            <div class="vendor-card">

                <?php if ($isSelected) { ?>
                    <div class="selected-ribbon">SELECTED</div>
                <?php } ?>

                <?php if (!empty($vendor['image'])) { ?>
                    <img src="../../uploads/vendors/<?= $vendor['image'] ?>" 
                         class="vendor-card-img"
                         alt="Vendor Image">
                <?php } ?>

                <div class="vendor-card-body">

                    <h3><?= $vendor['name'] ?></h3>

                    <div class="vendor-type">
                        <?= $vendor['service_type'] ?> • RM <?= number_format($vendor['price'], 2) ?>
                    </div>

                    <div class="vendor-info">
                        <p><strong>Phone:</strong> <?= $vendor['phone'] ?></p>
                        <p><strong>Description:</strong> <?= $vendor['description'] ?></p>
                        <p><strong>Service Details:</strong> <?= $vendor['service_details'] ?></p>
                        <p><strong>Equipment:</strong> <?= $vendor['equipment'] ?></p>
                    </div>

                    <?php if ($isSelected) { ?>
                        <a href="index.php?remove=<?= $vendor_id ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Remove this vendor from your company?');">
                            Remove Vendor
                        </a>
                    <?php } else { ?>
                        <a href="index.php?select=<?= $vendor_id ?>" 
                           class="btn btn-primary">
                            Select Vendor
                        </a>
                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

</div>

<?php include('../footer.php'); ?>