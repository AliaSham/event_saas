<?php
session_start();
include('../../config/db.php');

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM packages WHERE id='$id'");

header("Location: index.php");