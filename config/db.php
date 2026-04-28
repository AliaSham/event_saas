<?php
$conn = mysqli_connect("localhost", "root", "", "event_saas");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>