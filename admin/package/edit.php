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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

/* GET PACKAGE DATA */
$packageQ = mysqli_query($conn, "
    SELECT * FROM packages 
    WHERE id='$id' AND company_id='$company_id'
");

if (mysqli_num_rows($packageQ) == 0) {
    header("Location: index.php");
    exit;
}

$package = mysqli_fetch_assoc($packageQ);

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

    $imageName = $package['image'];

    if ($name == "" || $pax == "" || $description == "" || $price == "" || $category == "" || $menu == "") {
        $error = "All fields are required";
    } 
    else {

        /* IF USER UPLOAD NEW IMAGE */
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

            $fileTmp = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
            } 
            else {

                $newImageName = time() . "_" . rand(1000, 9999) . "." . $ext;

                $uploadDir = __DIR__ . "/../../uploads/packages/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploadPath = $uploadDir . $newImageName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {

                    /* DELETE OLD IMAGE */
                    if (!empty($package['image'])) {
                        $oldImagePath = $uploadDir . $package['image'];

                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageName = $newImageName;

                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error == "") {

            mysqli_query($conn, "
                UPDATE packages SET
                    event_category_id = '$category',
                    package_name = '$name',
                    pax = '$pax',
                    description = '$description',
                    base_price = '$price',
                    menu = '$menu',
                    image = '$imageName'
                WHERE id = '$id' AND company_id = '$company_id'
            ");

            header("Location: index.php");
            exit;
        }
    }
}
?>

<style>
.form-box input,
.form-box select,
.form-box textarea {
    width: 100% !important;
    padding: 12px !important;
    margin-bottom: 15px !important;
    border-radius: 8px !important;
    border: 1px solid #ddd !important;
    font-family: 'Poppins', sans-serif !important;
    font-size: 14px !important;
    outline: none !important;
    box-sizing: border-box !important;
}

.form-box textarea {
    min-height: 100px !important;
    resize: vertical !important;
}

.form-box input:focus,
.form-box select:focus,
.form-box textarea:focus {
    border-color: #1677ff !important;
    box-shadow: 0 0 0 2px rgba(22,119,255,0.1) !important;
}

.current-image {
    width: 160px;
    height: 110px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
}

.image-note {
    font-size: 13px;
    color: #777;
    margin-bottom: 8px;
}


</style>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Edit Package</h2>
            <p class="muted">Update package details</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <input type="text" 
                       name="package_name" 
                       placeholder="Package Name"
                       value="<?= $package['package_name'] ?>"
                       required>

                <input type="number" 
                       name="pax" 
                       placeholder="Pax"
                       min="1"
                       value="<?= $package['pax'] ?>"
                       required>

                <textarea name="description" 
                          placeholder="Package Description"
                          required><?= $package['description'] ?></textarea>

                <input type="number" 
                       name="price" 
                       placeholder="Price (RM)"
                       min="0"
                       step="0.01"
                       value="<?= $package['base_price'] ?>"
                       required>

                <textarea name="menu" 
                          placeholder="Menu"
                          required><?= $package['menu'] ?></textarea>

                <select name="category_id" required>
                    <option value="">Select Category</option>

                    <?php while($cat = mysqli_fetch_assoc($catResult)){ ?>
                        <option value="<?= $cat['id'] ?>"
                            <?php if ($cat['id'] == $package['event_category_id']) { echo "selected"; } ?>>
                            <?= $cat['name'] ?>
                        </option>
                    <?php } ?>
                </select>

                <?php if (!empty($package['image'])) { ?>
                    <p class="image-note">Current Image:</p>
                    <img src="../../uploads/packages/<?= $package['image'] ?>" 
                         class="current-image"
                         alt="Package Image">
                <?php } ?>

                <p class="image-note">Upload new image only if you want to replace the current image.</p>

                <input type="file" 
                       name="image" 
                       accept="image/*">

                <button class="btn btn-primary">
                    Update Package
                </button>

            </form>

            <a href="index.php" class="back-link">
                ← Back to packages
            </a>

        </div>

    </div>

</div>