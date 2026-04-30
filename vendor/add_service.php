<?php
session_start();
include('../config/db.php');
include('../config/plan.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* GET PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);

$limits = getPlanLimits($data['plan']);

/* SUBMIT */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 🔥 CHECK LIMIT
    $countQ = mysqli_query($conn, "SELECT COUNT(*) as total FROM vendor_services WHERE company_id='$company_id'");
    $countData = mysqli_fetch_assoc($countQ);

    if ($countData['total'] >= $limits['vendor_services']) {
        die("❌ You reached vendor service limit.");
    }

    $name = $_POST['service_name'];
    $price = $_POST['price'];

    mysqli_query($conn, "INSERT INTO vendor_services (service_name, price, company_id)
    VALUES ('$name','$price','$company_id')");

    header("Location: services.php");
}
?>