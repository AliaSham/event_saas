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
$error = "";

/* GET COMPANY PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* FUNCTION: GET CURRENT COUNT (IMPORTANT FIX) */
function getPackageCount($conn, $company_id) {
    $q = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM packages 
        WHERE company_id='$company_id'
    ");
    return mysqli_fetch_assoc($q)['total'];
}

/* GET CURRENT COUNT */
$currentCount = getPackageCount($conn, $company_id);

/* GET CATEGORIES */
$catResult = mysqli_query($conn, "
    SELECT * FROM event_categories 
    WHERE company_id='$company_id'
");

/* 🚨 BLOCK ACCESS IF LIMIT REACHED (UI LEVEL) */
$limitReached = ($limits['packages'] != -1 && $currentCount >= $limits['packages']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['package_name']);
    $price = trim($_POST['price']);
    $category = $_POST['category_id'];

    // 🔴 HARD LIMIT CHECK (BACKEND SAFETY)
    $currentCount = getPackageCount($conn, $company_id);

    if ($limits['packages'] != -1 && $currentCount >= $limits['packages']) {
        $error = "You have reached your package limit for this plan.";
    }
    elseif ($name == "" || $price == "" || $category == "") {
        $error = "All fields are required";
    }
    else {

        mysqli_query($conn, "
            INSERT INTO packages (package_name, base_price, event_category_id, company_id)
            VALUES ('$name','$price','$category','$company_id')
        ");

        header("Location: index.php");
        exit;
    }
}
?>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Add Package</h2>
            <p class="muted">
                <?= $currentCount ?> / <?= $limits['packages'] ?> used
            </p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($limitReached) { ?>

                <!-- 🚫 LIMIT REACHED UI -->
                <div class="alert error">
                    You reached your plan limit. Upgrade to add more packages.
                </div>

                <a href="../subscription/upgrade.php" class="btn btn-primary">
                    Upgrade Plan
                </a>

            <?php } else { ?>

                <!-- ✅ NORMAL FORM -->
                <form method="POST">

                    <input type="text" 
                           name="package_name" 
                           placeholder="Package Name" 
                           required>

                    <input type="number" 
                           name="price" 
                           placeholder="Price (RM)" 
                           required>

                    <select name="category_id" required>
                        <option value="">Select Category</option>

                        <?php while($cat=mysqli_fetch_assoc($catResult)){ ?>
                            <option value="<?= $cat['id'] ?>">
                                <?= $cat['name'] ?>
                            </option>
                        <?php } ?>
                    </select>

                    <button class="btn btn-primary">
                        Add Package
                    </button>

                </form>

            <?php } ?>

            <a href="index.php" class="back-link">
                ← Back to packages
            </a>

        </div>

    </div>

</div>