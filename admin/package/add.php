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

/* FUNCTION: GET PACKAGE COUNT BY CATEGORY */
function getPackageCountByCategory($conn, $company_id, $category_id) {
    $q = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM packages 
        WHERE company_id='$company_id'
        AND event_category_id='$category_id'
    ");

    $row = mysqli_fetch_assoc($q);
    return $row['total'];
}

/* GET CATEGORIES */
$catResult = mysqli_query($conn, "
    SELECT * FROM event_categories 
    WHERE company_id='$company_id'
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['package_name']);
    $pax = trim($_POST['pax']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = $_POST['category_id'];
    $menu = trim($_POST['menu']);

    if ($name == "" || $pax == "" || $description == "" || $price == "" || $category == "" || $menu == "") {
        $error = "All fields are required";
    }
    else {

        /* CHECK LIMIT BASED ON SELECTED CATEGORY */
        $currentCount = getPackageCountByCategory($conn, $company_id, $category);

        if ($currentCount >= $limits['packages_per_category']) {
            $error = "This category has reached the package limit for your plan.";
        }
        else {

            $imageName = "";

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

                $fileTmp = $_FILES['image']['tmp_name'];
                $fileName = $_FILES['image']['name'];

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
                } 
                else {

                    $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

                    $uploadDir = __DIR__ . "/../../uploads/packages/";

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $uploadPath = $uploadDir . $imageName;

                    if (!move_uploaded_file($fileTmp, $uploadPath)) {
                        $error = "Failed to upload image.";
                    }
                }

            } else {
                $error = "Package image is required.";
            }

            if ($error == "") {

                mysqli_query($conn, "
                    INSERT INTO packages 
                    (event_category_id, package_name, pax, description, base_price, company_id, menu, image)
                    VALUES 
                    ('$category', '$name', '$pax', '$description', '$price', '$company_id', '$menu', '$imageName')
                ");

                header("Location: index.php");
                exit;
            }
        }
    }
}
?>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Add Package</h2>
            <p class="muted">
                Package limit: <?= $limits['packages_per_category'] ?> per category
            </p>

            <?php if ($error != "") { ?>
                <div class="alert error">
                    <?= $error ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <input type="text" 
                       name="package_name" 
                       placeholder="Package Name" 
                       required>

                <input type="number" 
                       name="pax" 
                       placeholder="Pax"
                       min="1"
                       required>

                <textarea name="description" 
                          placeholder="Package Description"
                          required></textarea>

                <input type="number" 
                       name="price" 
                       placeholder="Price (RM)"
                       min="0"
                       step="0.01"
                       required>

                <textarea name="menu" 
                          placeholder="Menu"
                          required></textarea>

                <select name="category_id" required>
                    <option value="">Select Category</option>

                    <?php while($cat = mysqli_fetch_assoc($catResult)){ 
                        $catCount = getPackageCountByCategory($conn, $company_id, $cat['id']);
                    ?>
                        <option value="<?= $cat['id'] ?>" 
                            <?php if ($catCount >= $limits['packages_per_category']) { echo "disabled"; } ?>>
                            <?= $cat['name'] ?> 
                            (<?= $catCount ?> / <?= $limits['packages_per_category'] ?> used)
                        </option>
                    <?php } ?>

                </select>

                <input type="file" 
                       name="image" 
                       accept="image/*" 
                       required>

                <button class="btn btn-primary">
                    Add Package
                </button>

            </form>

            <a href="index.php" class="back-link">
                ← Back to packages
            </a>

        </div>

    </div>

</div>