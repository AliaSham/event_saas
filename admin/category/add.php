<?php 
session_start();
include('../../config/db.php');
include('../../config/plan.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

$error = "";

/* GET COMPANY PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* FUNCTION: GET CATEGORY COUNT */
function getCategoryCount($conn, $company_id) {
    $q = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM event_categories 
        WHERE company_id='$company_id'
    ");

    $row = mysqli_fetch_assoc($q);
    return $row['total'];
}

/* GET CURRENT CATEGORY COUNT */
$currentCount = getCategoryCount($conn, $company_id);

/* CHECK IF CATEGORY LIMIT REACHED */
$limitReached = ($currentCount >= $limits['categories']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['category_name'];

    /* CHECK AGAIN WHEN SUBMIT */
    $currentCount = getCategoryCount($conn, $company_id);

    if ($currentCount >= $limits['categories']) {
        $error = "You have reached your category limit for this plan.";
    }
    elseif ($name == "") {
        $error = "Please select a category";
    } 
    else {

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
            <p class="muted">
                Category Used: <?= $currentCount ?> / <?= $limits['categories'] ?>
            </p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($limitReached) { ?>

                <div class="alert error">
                    You reached your category limit. Upgrade your plan to add more categories.
                </div>

                <a href="../subscription/upgrade.php" class="btn btn-primary">
                    Upgrade Plan
                </a>

            <?php } else { ?>

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

            <?php } ?>

            <a href="index.php" class="back-link">
                ← Back to categories
            </a>

        </div>

    </div>

</div>