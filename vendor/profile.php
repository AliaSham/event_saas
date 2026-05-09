<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];
$error = "";
$success = "";

/* GET VENDOR ACCOUNT */
$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user) {
    header("Location: ../auth/login.php");
    exit;
}

/* BLOCK IF NOT APPROVED */
if ($user['approval_status'] != 'active') {
    header("Location: ../auth/login.php");
    exit;
}

/* GET EXISTING PROFILE */
$profileQ = mysqli_query($conn, "
    SELECT * FROM vendor_profiles 
    WHERE user_id='$vendor_id'
");

$profile = mysqli_fetch_assoc($profileQ);

/* SAVE PROFILE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $phone = trim($_POST['phone']);
    $service_type = trim($_POST['service_type']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $service_details = trim($_POST['service_details']);
    $equipment = trim($_POST['equipment']);

    $imageName = $profile ? $profile['image'] : "";

    if ($phone == "" || $service_type == "" || $price == "" || $description == "" || $service_details == "" || $equipment == "") {
        $error = "All fields are required.";
    }
    elseif (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone)) {
        $error = "Phone number must follow the format 012-3456789.";
    }
    else {

        /* UPLOAD NEW IMAGE IF SELECTED */
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

            $fileTmp = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
            } else {

                $newImageName = time() . "_" . rand(1000, 9999) . "." . $ext;

                $uploadDir = __DIR__ . "/../uploads/vendors/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploadPath = $uploadDir . $newImageName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {

                    /* DELETE OLD IMAGE */
                    if (!empty($imageName)) {
                        $oldImagePath = $uploadDir . $imageName;

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

            if ($profile) {

                mysqli_query($conn, "
                    UPDATE vendor_profiles SET
                        phone='$phone',
                        service_type='$service_type',
                        price='$price',
                        description='$description',
                        service_details='$service_details',
                        equipment='$equipment',
                        image='$imageName'
                    WHERE user_id='$vendor_id'
                ");

            } else {

                mysqli_query($conn, "
                    INSERT INTO vendor_profiles
                    (user_id, phone, service_type, price, description, service_details, equipment, image)
                    VALUES
                    ('$vendor_id', '$phone', '$service_type', '$price', '$description', '$service_details', '$equipment', '$imageName')
                ");
            }

            $success = "Profile updated successfully.";

            /* REFRESH PROFILE */
            $profileQ = mysqli_query($conn, "
                SELECT * FROM vendor_profiles 
                WHERE user_id='$vendor_id'
            ");

            $profile = mysqli_fetch_assoc($profileQ);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Profile</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Vendor Profile</h2>
            <p class="muted">Complete your service information</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= $success ?></div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <label>Vendor Name</label>
                <input type="text"
                       value="<?= $user['name'] ?>"
                       disabled>

                <label>Email</label>
                <input type="email"
                       value="<?= $user['email'] ?>"
                       disabled>

                <label>Status</label>
                <input type="text"
                       value="<?= ucfirst($user['approval_status']) ?>"
                       disabled>

                <label>Phone Number</label>
                <input type="tel"
                       name="phone"
                       placeholder="Example: 012-3456789"
                       pattern="01[0-9]-[0-9]{7,8}"
                       title="Phone number must follow the format 012-3456789"
                       value="<?= $profile ? $profile['phone'] : '' ?>"
                       required>

                <label>Service Type</label>
                <input type="text"
                       name="service_type"
                       placeholder="Example: Photographer, Makeup Artist, Dessert Table"
                       value="<?= $profile ? $profile['service_type'] : '' ?>"
                       required>

                <label>Base Price (RM)</label>
                <input type="number"
                       name="price"
                       placeholder="Example: 1200"
                       min="0"
                       step="0.01"
                       value="<?= $profile ? $profile['price'] : '' ?>"
                       required>

                <label>Description</label>
                <textarea name="description"
                          placeholder="Brief description about your service"
                          required><?= $profile ? $profile['description'] : '' ?></textarea>

                <label>Service Details</label>
                <textarea name="service_details"
                          placeholder="Example: 6 hours coverage, 300 edited photos, online gallery"
                          required><?= $profile ? $profile['service_details'] : '' ?></textarea>

                <label>Equipment / Items Included</label>
                <textarea name="equipment"
                          placeholder="Example: DSLR camera, lighting setup, backdrop, props"
                          required><?= $profile ? $profile['equipment'] : '' ?></textarea>

                <?php if ($profile && !empty($profile['image'])) { ?>
                    <label>Current Portfolio Image</label>
                    <img src="../uploads/vendors/<?= $profile['image'] ?>" 
                         class="current-image"
                         alt="Vendor Portfolio">
                <?php } ?>

                <label>Portfolio Image</label>
                <input type="file"
                       name="image"
                       accept="image/*">

                <button class="btn btn-primary">
                    Save Profile
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>