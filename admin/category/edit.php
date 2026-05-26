<?php
session_start();
include('../../config/db.php');
include('../header.php');

// PROTECT
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

// CHECK ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// PREDEFINED OPTIONS (same as add)
$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

// GET CURRENT DATA
$query = "SELECT * FROM categories WHERE id='$id' AND company_id='$company_id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("Category not found");
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!empty($_POST['category_name'])) {

        $name = $_POST['category_name'];

        // OPTIONAL: prevent duplicate
        $check = "SELECT * FROM categories 
                  WHERE name='$name' AND company_id='$company_id' AND id != '$id'";
        $checkResult = mysqli_query($conn, $check);

        if (mysqli_num_rows($checkResult) > 0) {
            $error = "Category already exists.";
        } else {
            $update = "UPDATE categories SET name='$name' WHERE id='$id'";
            mysqli_query($conn, $update);

            header("Location: index.php");
            exit;
        }

    } else {
        $error = "Please select a category.";
    }
}
?>

<div class="container">

    <h2>Edit Category</h2>

    <div class="form-container">
        <div class="form-box">

            <!-- ERROR -->
            <?php if (isset($error)) { ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php } ?>

            <form method="POST">

                <select name="category_name" required>

                    <option value="">Select Category</option>

                    <?php foreach ($categories as $cat) { ?>
                        <option value="<?php echo $cat; ?>"
                            <?php if ($row['name'] == $cat) echo "selected"; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php } ?>

                </select>

                <button class="btn btn-primary" style="width:100%;">
                    Update Category
                </button>

            </form>

        </div>
    </div>

</div>

<?php include('../footer.php'); ?>