<?php
session_start();
include('../../config/db.php');
include('../header.php');

// PROTECT PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// GET DATA
$id = $_GET['id'];
$company_id = $_SESSION['company_id'];

// FETCH ONLY IF BELONGS TO COMPANY
$result = mysqli_query($conn, 
    "SELECT * FROM categories 
     WHERE id='$id' AND company_id='$company_id'"
);

$data = mysqli_fetch_assoc($result);

// IF NOT FOUND → BLOCK
if (!$data) {
    die("Access denied or category not found");
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];

    mysqli_query($conn, 
        "UPDATE categories 
         SET name='$name' 
         WHERE id='$id' AND company_id='$company_id'"
    );

    header("Location: index.php");
    exit;
}
?>

<h2>Edit Category</h2>

<form method="POST">
    <input type="text" name="name" value="<?php echo $data['name']; ?>" required>
    <button type="submit">Update</button>
</form>