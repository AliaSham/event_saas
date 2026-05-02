<?php
session_start();
include('../config/db.php');
include('../admin/header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

$error = "";

/* ADD SERVICE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['service_name'];
    $price = $_POST['price'];

    if ($name == "" || $price == "") {
        $error = "All fields required";
    } else {

        mysqli_query($conn, "
            INSERT INTO vendor_services (vendor_id, service_name, price)
            VALUES ('$vendor_id','$name','$price')
        ");

        header("Location: add_service.php");
        exit;
    }
}

/* GET SERVICES */
$result = mysqli_query($conn, "
    SELECT * FROM vendor_services 
    WHERE vendor_id='$vendor_id'
");
?>

<div class="container">

<div class="page-header">
    <div>
        <h2>Manage Services</h2>
        <p class="muted">Add and manage your services</p>
    </div>
</div>

<div class="form-container">

    <div class="form-box modern-form">

        <h3>Add Service</h3>

        <?php if ($error != "") { ?>
            <div class="alert error"><?= $error ?></div>
        <?php } ?>

        <form method="POST">

            <input type="text" 
                   name="service_name" 
                   placeholder="Service Name (e.g. Photography)" 
                   required>

            <input type="number" 
                   name="price" 
                   placeholder="Price (RM)" 
                   required>

            <button class="btn btn-primary">
                Add Service
            </button>

        </form>

    </div>

</div>

<!-- SERVICE LIST -->
<div style="margin-top:30px;">

<h3>Your Services</h3>

<?php if(mysqli_num_rows($result) == 0){ ?>
    <p class="muted">No services yet.</p>
<?php } ?>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="card-row">

    <div>
        <div class="card-title"><?= $row['service_name'] ?></div>
        <div class="card-sub">RM <?= number_format($row['price'],2) ?></div>
    </div>

    <div class="card-actions">
        <a href="delete_service.php?id=<?= $row['id'] ?>" class="btn btn-danger">Delete</a>
    </div>

</div>

<?php } ?>

</div>

</div>