<?php
session_start();
include('../../config/db.php');
include('../../config/plan.php');
include('../header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$error = "";
$success = "";

/* GET COMPANY PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* COUNT SELECTED VENDORS */
$countQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM company_vendors 
    WHERE company_id='$company_id'
");
$countData = mysqli_fetch_assoc($countQ);
$currentCount = $countData['total'];

/* SELECT VENDOR */
if (isset($_GET['select'])) {

    $vendor_id = $_GET['select'];

    /* Check selected limit */
    $countQ = mysqli_query($conn, "
        SELECT COUNT(*) AS total 
        FROM company_vendors 
        WHERE company_id='$company_id'
    ");
    $countData = mysqli_fetch_assoc($countQ);
    $currentCount = $countData['total'];

    if ($currentCount >= $limits['services']) {
        $error = "You have reached your vendor limit for this plan.";
    } else {

        /* Check duplicate */
        $check = mysqli_query($conn, "
            SELECT * FROM company_vendors
            WHERE company_id='$company_id'
            AND vendor_user_id='$vendor_id'
        ");

        if (mysqli_num_rows($check) > 0) {
            $error = "Vendor already selected.";
        } else {

            mysqli_query($conn, "
                INSERT INTO company_vendors
                (company_id, vendor_user_id)
                VALUES
                ('$company_id', '$vendor_id')
            ");

            header("Location: index.php");
            exit;
        }
    }
}

/* REMOVE VENDOR */
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

/* FETCH ACTIVE VENDORS WITH COMPLETE PROFILE */
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
?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Manage Vendors</h2>
            <p class="muted">
                Vendors Selected: <?= $currentCount ?> / <?= $limits['services'] ?>
            </p>
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

                <?php if (!empty($vendor['image'])) { ?>
                    <img src="../../uploads/vendors/<?= $vendor['image'] ?>" 
                         class="vendor-card-img"
                         alt="Vendor Image">
                <?php } ?>

                <div class="vendor-card-body">

                    <h3><?= $vendor['name'] ?></h3>

                    <p class="vendor-type">
                        <?= $vendor['service_type'] ?> • RM <?= number_format($vendor['price'], 2) ?>
                    </p>

                    <p><strong>Phone:</strong> <?= $vendor['phone'] ?></p>
                    <p><strong>Description:</strong> <?= $vendor['description'] ?></p>
                    <p><strong>Service Details:</strong> <?= $vendor['service_details'] ?></p>
                    <p><strong>Equipment:</strong> <?= $vendor['equipment'] ?></p>

                    <?php if ($isSelected) { ?>
                        <a href="index.php?remove=<?= $vendor_id ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Remove this vendor from your company?');">
                            Remove
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

<?php include('../footer.php'); ?>
</div>