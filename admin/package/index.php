<?php
session_start();
include('../../config/db.php');
include('../../config/plan.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* COUNT */
$countQ = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM packages 
    WHERE company_id='$company_id'
");
$countData = mysqli_fetch_assoc($countQ);

/* FETCH */
$result = mysqli_query($conn, "
    SELECT p.*, c.name as category_name
    FROM packages p
    JOIN event_categories c ON p.event_category_id = c.id
    WHERE p.company_id='$company_id'
");
?>

<style>
.package-image {
    width: 140px;
    height: 100px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 10px;
    border: 1px solid #eee;
}

.card-details {
    margin-top: 10px;
    font-size: 14px;
    color: #444;
    line-height: 1.6;
}

.card-details p {
    margin: 4px 0;
}

.card-actions {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.card-actions .btn {
    min-width: 70px;
    height: 40px;
    padding: 0 14px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}
</style>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Manage Packages</h2>
            <p class="muted">
                Package Limit: <?= $limits['packages_per_category'] ?> per category
            </p>
        </div>

        <a href="add.php" class="btn btn-primary">
            + Add Package
        </a>
    </div>

    <?php if(mysqli_num_rows($result) == 0){ ?>
        <div class="empty-state">
            <h3>No packages yet</h3>
            <p>Create your first package to start selling</p>
        </div>
    <?php } ?>

    <?php while($row = mysqli_fetch_assoc($result)){ ?>

        <div class="card">

            <div class="card-content">

                <?php if (!empty($row['image'])) { ?>
                    <img src="../../uploads/packages/<?= $row['image'] ?>" 
                         alt="Package Image" 
                         class="package-image">
                <?php } ?>

                <div class="card-title">
                    <?= $row['package_name'] ?>
                </div>

                <div class="card-meta">
                    RM <?= number_format($row['base_price'], 2) ?> • 
                    <?= $row['category_name'] ?>
                </div>

                <div class="card-details">
                    <p><strong>Pax:</strong> <?= $row['pax'] ?> pax</p>
                    <p><strong>Description:</strong> <?= $row['description'] ?></p>
                    <p><strong>Menu:</strong> <?= $row['menu'] ?></p>
                </div>

            </div>

            <div class="card-actions">
                <a href="edit.php?id=<?= $row['id'] ?>" 
                   class="btn btn-success">
                    Edit
                </a>

                <a href="delete.php?id=<?= $row['id'] ?>" 
                   class="btn btn-delete"
                   onclick="return confirm('Are you sure you want to delete this package?');">
                    Delete
                </a>
            </div>

        </div>

    <?php } ?>

</div>