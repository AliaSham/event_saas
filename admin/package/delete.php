<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id = $_GET['id'];
$company_id = $_SESSION['company_id'];

$get = mysqli_query($conn, "SELECT image FROM packages WHERE id='$id' AND company_id='$company_id'");
$data = mysqli_fetch_assoc($get);

if ($data) {
    if (!empty($data['image']) && file_exists("../../uploads/packages/" . $data['image'])) {
        unlink("../../uploads/packages/" . $data['image']);
    }

    mysqli_query($conn, "DELETE FROM packages WHERE id='$id' AND company_id='$company_id'");
}

header("Location: index.php");
exit;
?>