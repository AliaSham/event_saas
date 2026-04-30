<?php
session_start();
include('../../config/db.php');

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM event_categories WHERE id='$id'");

header("Location: index.php");