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

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Manage Packages</h2>
            <p class="muted">
                Packages Used: <?= $countData['total'] ?> / <?= $limits['packages'] ?>
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

    <?php while($row=mysqli_fetch_assoc($result)){ ?>
        <div class="card">

            <div class="card-content">
                <div class="card-title">
                    <?= $row['package_name'] ?>
                </div>

                <div class="card-meta">
                    RM <?= number_format($row['base_price'],2) ?> • 
                    <?= $row['category_name'] ?>
                </div>
            </div>

            <a href="delete.php?id=<?= $row['id'] ?>" 
               class="btn btn-delete">
               Delete
            </a>

        </div>
    <?php } ?>

</div>