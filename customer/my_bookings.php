<?php
session_start();
include('../config/db.php');

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn, "
SELECT b.*, p.package_name 
FROM bookings b
JOIN packages p ON b.package_id = p.id
WHERE b.user_id = '$user_id'
ORDER BY b.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
<style>
body { font-family:'Poppins'; background:#f5f6fa; padding:40px; }

.card {
    background:white;
    padding:20px;
    margin-bottom:15px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
</style>
</head>

<body>

<h2>My Bookings</h2>

<?php while($row=mysqli_fetch_assoc($query)){ ?>
<div class="card">
    <strong><?= $row['package_name'] ?></strong><br>
    Date: <?= $row['event_date'] ?><br>
    Total: RM <?= $row['total_price'] ?><br>
    Status: <?= $row['status'] ?>
</div>
<?php } ?>

</body>
</html>