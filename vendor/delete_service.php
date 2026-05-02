<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM vendor_services WHERE id='$id'");

header("Location: add_service.php");
exit;