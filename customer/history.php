<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "
    SELECT b.*, p.package_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    WHERE b.user_id='$user_id'
    ORDER BY b.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>
<style>
body { font-family:Poppins; background:#f5f6fa; }

.container { padding:30px; }

.card {
    background:white;
    padding:15px;
    border-radius:10px;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="container">

<h2>My Bookings</h2>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="card">
    <b><?= $row['package_name'] ?></b><br>
    Date: <?= $row['event_date'] ?><br>
    Total: RM <?= $row['total_price'] ?><br>
    Status: <?= $row['status'] ?>
</div>

<?php } ?>

</div>

</body>
</html>