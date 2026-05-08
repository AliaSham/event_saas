<?php
session_start();
include('../config/db.php');
include('header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$error = "";
$success = "";

/* GET COMPANY DATA */
$companyQ = mysqli_query($conn, "
    SELECT * FROM companies 
    WHERE id='$company_id'
");

$company = mysqli_fetch_assoc($companyQ);

if (!$company) {
    header("Location: dashboard.php");
    exit;
}

/* UPDATE COMPANY PROFILE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $description = trim($_POST['description']);

    $logoName = $company['logo'];

    if ($company_name == "" || $email == "" || $phone == "" || $address == "" || $description == "") {
        $error = "All fields are required.";
    }
    elseif (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone)) {
        $error = "Phone number must follow the format 012-3456789.";
    }
    else {

        /* UPLOAD LOGO IF SELECTED */
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {

            $fileTmp = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
            } else {

                $newLogoName = time() . "_" . rand(1000, 9999) . "." . $ext;

                $uploadDir = __DIR__ . "/../uploads/companies/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploadPath = $uploadDir . $newLogoName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {

                    if (!empty($logoName)) {
                        $oldLogoPath = $uploadDir . $logoName;

                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }

                    $logoName = $newLogoName;

                } else {
                    $error = "Failed to upload logo.";
                }
            }
        }

        if ($error == "") {

            mysqli_query($conn, "
                UPDATE companies SET
                    company_name = '$company_name',
                    email = '$email',
                    phone = '$phone',
                    address = '$address',
                    description = '$description',
                    logo = '$logoName'
                WHERE id = '$company_id'
            ");

            $success = "Company profile updated successfully.";

            $companyQ = mysqli_query($conn, "
                SELECT * FROM companies 
                WHERE id='$company_id'
            ");

            $company = mysqli_fetch_assoc($companyQ);
        }
    }
}
?>

<style>
.company-logo-preview {
    width: 160px;
    height: 110px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
}
</style>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Company Profile</h2>
            <p class="muted">Update your company information</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= $success ?></div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <label>Company Name</label>
                <input type="text"
                       name="company_name"
                       value="<?= $company['company_name'] ?>"
                       required>

                <label>Email</label>
                <input type="email"
                       name="email"
                       value="<?= $company['email'] ?>"
                       required>

                <label>Phone Number</label>
                <input type="tel"
                       name="phone"
                       placeholder="Example: 012-3456789"
                       pattern="01[0-9]-[0-9]{7,8}"
                       title="Phone number must follow the format 012-3456789"
                       value="<?= $company['phone'] ?>"
                       required>

                <label>Address</label>
                <input type="text"
                       name="address"
                       value="<?= $company['address'] ?>"
                       required>

                <label>Description</label>
                <textarea name="description"
                          placeholder="Describe your company"
                          required><?= $company['description'] ?></textarea>

                <?php if (!empty($company['logo'])) { ?>
                    <label>Current Logo / Image</label>
                    <img src="../uploads/companies/<?= $company['logo'] ?>" 
                         class="company-logo-preview"
                         alt="Company Logo">
                <?php } ?>

                <label>Upload Logo / Image</label>
                <input type="file"
                       name="logo"
                       accept="image/*">

                <button class="btn btn-primary">
                    Save Profile
                </button>

            </form>

        </div>

    </div>

</div>