<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

$result = mysqli_query($conn, "
    SELECT * FROM event_categories 
    WHERE company_id='$company_id'
");
?>

<div class="container">

    <div class="page-header">
        <h2 class="page-title">Manage Categories</h2>

        <a href="add.php" class="btn btn-primary">
            + Add Category
        </a>
    </div>

    <?php if(mysqli_num_rows($result) == 0){ ?>
        <div class="empty-state">
            <h3>No categories yet</h3>
            <p>Start by adding your first event category</p>
        </div>
    <?php } ?>

    <?php while($row=mysqli_fetch_assoc($result)){ ?>
        <div class="card">

            <div class="card-title">
                <?= $row['name'] ?>
            </div>

            <a href="delete.php?id=<?= $row['id'] ?>" 
               class="btn btn-delete">
               Delete
            </a>

        </div>
    <?php } ?>

</div>