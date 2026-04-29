<?php
session_start();
include('../../config/db.php');
include('../header.php');

// PROTECT PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

// PREDEFINED OPTIONS
$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!empty($_POST['category_name'])) {

        $name = $_POST['category_name'];

        // CHECK DUPLICATE
        $check = "SELECT * FROM categories 
                  WHERE name='$name' AND company_id='$company_id'";
        $checkResult = mysqli_query($conn, $check);

        if (mysqli_num_rows($checkResult) > 0) {
            $error = "Category already exists.";
        } else {

            $query = "INSERT INTO categories (name, company_id) 
                      VALUES ('$name', '$company_id')";

            if (mysqli_query($conn, $query)) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }

    } else {
        $error = "Please select a category.";
    }
}
?>

<div class="container">

    <h2>Add Category</h2>

    <div class="form-container">
        <div class="form-box">

            <!-- ERROR MESSAGE -->
            <?php if ($error != "") { ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php } ?>

            <form method="POST">

                <select name="category_name" required>
                    <option value="">Select Category</option>

                    <?php foreach ($categories as $cat) { ?>
                        <option value="<?php echo $cat; ?>">
                            <?php echo $cat; ?>
                        </option>
                    <?php } ?>

                </select>

                <button class="btn btn-add">Add Category</button>

            </form>

        </div>
    </div>

</div>