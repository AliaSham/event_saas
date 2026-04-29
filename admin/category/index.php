<?php
session_start();
include('../../config/db.php');
include('../header.php');

// PROTECT PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

// GET DATA
$query = "SELECT * FROM categories WHERE company_id = '$company_id'";
$result = mysqli_query($conn, $query);

// DEBUG if query fails
if (!$result) {
    die("SQL ERROR: " . mysqli_error($conn));
}
?>

<div class="container">

    <h2>Category List</h2>

    <a href="add.php" class="btn btn-add">+ Add Category</a>

    <hr>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <?php while($row = mysqli_fetch_assoc($result)) { ?>

            <div class="category-item">

                <strong><?php echo $row['name']; ?></strong>

                <div style="margin-top:10px;">
                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>

                    <a href="#"
                       class="btn btn-delete"
                       onclick="openModal('delete.php?id=<?php echo $row['id']; ?>')">
                        Delete
                    </a>
                </div>

            </div>

        <?php } ?>

    <?php } else { ?>
        <p>No categories found.</p>
    <?php } ?>

</div>