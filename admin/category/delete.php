<?php
session_start();
include('../../config/db.php');

// PROTECT PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = $_GET['id'];
$company_id = $_SESSION['company_id'];

// DELETE ONLY IF BELONGS TO COMPANY
mysqli_query($conn, 
    "DELETE FROM categories 
     WHERE id='$id' AND company_id='$company_id'"
);

header("Location: index.php");
exit;