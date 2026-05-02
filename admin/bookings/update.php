<?php
session_start();
include('../../config/db.php');

if (!isset($_POST['booking_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_POST['booking_id'];

/* APPROVE */
if (isset($_POST['approve'])) {
    mysqli_query($conn, "
        UPDATE bookings 
        SET status='Approved' 
        WHERE id='$id'
    ");
}

/* REJECT */
if (isset($_POST['reject'])) {
    mysqli_query($conn, "
        UPDATE bookings 
        SET status='Rejected' 
        WHERE id='$id'
    ");
}

/* ASSIGN VENDOR */
if (isset($_POST['assign'])) {
    $vendor_id = $_POST['vendor_id'];

    mysqli_query($conn, "
        UPDATE bookings 
        SET vendor_id='$vendor_id'
        WHERE id='$id'
    ");
}

header("Location: index.php");
exit;