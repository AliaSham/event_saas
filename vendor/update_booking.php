<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_POST['id'];
$status = $_POST['status'];

mysqli_query($conn, "UPDATE bookings SET status='$status' WHERE id='$id'");

header("Location: manage_bookings.php");