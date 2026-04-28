<?php
session_start();
include('../../config/db.php');
include('../header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $company_id = $_SESSION['company_id'];

    $query = "INSERT INTO categories (name, company_id) 
              VALUES ('$name', '$company_id')";

    mysqli_query($conn, $query);

    header("Location: index.php");
    exit;
}
?>

<h2>Add Category</h2>

<form method="POST">
    <input type="text" name="name" placeholder="Category Name" required>
    <button type="submit">Add</button>
</form>