<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['category_name'];

    if ($name == "") {
        $error = "Please select a category";
    } else {

        $check = mysqli_query($conn,
            "SELECT * FROM event_categories 
             WHERE name='$name' AND company_id='$company_id'"
        );

        if (mysqli_num_rows($check) > 0) {
            $error = "Category already exists";
        } else {

            mysqli_query($conn,
                "INSERT INTO event_categories (name, company_id)
                 VALUES ('$name','$company_id')"
            );

            header("Location: index.php");
            exit;
        }
    }
}
?>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Add Category</h2>
            <p class="muted">Choose a category for your events</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <form method="POST">

                <select name="category_name" required>
                    <option value="">Select Category</option>

                    <?php foreach ($categories as $cat) { ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php } ?>

                </select>

                <button class="btn btn-primary">
                    Add Category
                </button>

            </form>

            <a href="index.php" class="back-link">
                ← Back to categories
            </a>

        </div>

    </div>

</div>