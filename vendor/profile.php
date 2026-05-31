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

$userQ = mysqli_query($conn, "SELECT * FROM users WHERE id='$vendor_id' AND role='vendor'");
$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../auth/login.php");
    exit;
}

$profileQ = mysqli_query($conn, "SELECT * FROM vendor_profiles WHERE user_id='$vendor_id'");
$profile = mysqli_fetch_assoc($profileQ);

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
    } elseif (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone)) {
        $error = "Phone number must follow the format 012-3456789.";
    } else {

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

            $profileQ = mysqli_query($conn, "SELECT * FROM vendor_profiles WHERE user_id='$vendor_id'");
            $profile = mysqli_fetch_assoc($profileQ);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #0f172a;
        }

        .profile-wrapper {
            padding: 38px 55px;
        }

        .profile-hero {
            background: linear-gradient(135deg, #0f766e, #14b8a6, #2563eb);
            color: white;
            padding: 35px 40px;
            border-radius: 28px;
            box-shadow: 0 18px 45px rgba(15,118,110,0.25);
            margin-bottom: 30px;
        }

        .profile-hero h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }

        .profile-hero p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: .95;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 28px;
            align-items: start;
        }

        .profile-card,
        .form-card {
            background: white;
            border-radius: 26px;
            box-shadow: 0 14px 35px rgba(15,23,42,0.08);
            border: 1px solid #e5e7eb;
        }

        .profile-card {
            padding: 28px;
            position: sticky;
            top: 100px;
        }

        .avatar-box {
            width: 100%;
            height: 230px;
            border-radius: 22px;
            overflow: hidden;
            background: #ecfeff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }

        .avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            font-size: 58px;
        }

        .profile-card h2 {
            margin: 0;
            font-size: 22px;
        }

        .profile-card p {
            margin: 8px 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
        }

        .price-box {
            margin-top: 20px;
            padding: 18px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .price-box span {
            color: #64748b;
            font-size: 13px;
        }

        .price-box h3 {
            margin: 6px 0 0;
            font-size: 25px;
            color: #0f766e;
        }

        .form-card {
            padding: 34px;
        }

        .form-card h2 {
            margin: 0;
            font-size: 25px;
        }

        .form-subtitle {
            color: #64748b;
            font-size: 14px;
            margin: 6px 0 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 13px 15px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            background: #fff;
            transition: .25s;
        }

        input:focus,
        textarea:focus {
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20,184,166,0.12);
        }

        input:disabled {
            background: #f8fafc;
            color: #64748b;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 16px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .upload-box {
            border: 2px dashed #cbd5e1;
            padding: 24px;
            border-radius: 20px;
            background: #f8fafc;
        }

        .save-btn {
            border: none;
            background: linear-gradient(135deg, #0f766e, #2563eb);
            color: white;
            padding: 14px 28px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s;
            margin-top: 8px;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(37,99,235,0.25);
        }

        @media(max-width: 1000px) {
            .profile-wrapper {
                padding: 25px;
            }

            .profile-layout,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .profile-card {
                position: static;
            }
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="profile-wrapper">

    <div class="profile-hero">
        <h1>Vendor Profile</h1>
        <p>Manage your service information, pricing, portfolio and vendor details professionally.</p>
    </div>

    <div class="profile-layout">

        <div class="profile-card">

            <div class="avatar-box">
                <?php if ($profile && !empty($profile['image'])) { ?>
                    <img src="../uploads/vendors/<?= htmlspecialchars($profile['image']) ?>" alt="Vendor Portfolio">
                <?php } else { ?>
                    <div class="avatar-placeholder">🎉</div>
                <?php } ?>
            </div>

            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>

            <span class="badge">Active Vendor</span>

            <div class="price-box">
                <span>Base Price</span>
                <h3>RM <?= $profile ? number_format($profile['price'], 2) : '0.00' ?></h3>
            </div>

            <div class="price-box">
                <span>Service Type</span>
                <h3 style="font-size:18px;">
                    <?= $profile ? htmlspecialchars($profile['service_type']) : 'Not Set' ?>
                </h3>
            </div>

        </div>

        <div class="form-card">

            <h2>Service Information</h2>
            <p class="form-subtitle">Complete your vendor details so customers can view and book your service.</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Vendor Name</label>
                        <input type="text" value="<?= htmlspecialchars($user['name']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" value="<?= ucfirst(htmlspecialchars($user['approval_status'])) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel"
                               name="phone"
                               placeholder="Example: 012-3456789"
                               pattern="01[0-9]-[0-9]{7,8}"
                               title="Phone number must follow the format 012-3456789"
                               value="<?= $profile ? htmlspecialchars($profile['phone']) : '' ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Service Type</label>
                        <input type="text"
                               name="service_type"
                               placeholder="Example: Photographer, Makeup Artist, Dessert Table"
                               value="<?= $profile ? htmlspecialchars($profile['service_type']) : '' ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Base Price (RM)</label>
                        <input type="number"
                               name="price"
                               placeholder="Example: 1200"
                               min="0"
                               step="0.01"
                               value="<?= $profile ? htmlspecialchars($profile['price']) : '' ?>"
                               required>
                    </div>

                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description"
                                  placeholder="Brief description about your service"
                                  required><?= $profile ? htmlspecialchars($profile['description']) : '' ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Service Details</label>
                        <textarea name="service_details"
                                  placeholder="Example: 6 hours coverage, 300 edited photos, online gallery"
                                  required><?= $profile ? htmlspecialchars($profile['service_details']) : '' ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Equipment / Items Included</label>
                        <textarea name="equipment"
                                  placeholder="Example: DSLR camera, lighting setup, backdrop, props"
                                  required><?= $profile ? htmlspecialchars($profile['equipment']) : '' ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Portfolio Image</label>
                        <div class="upload-box">
                            <input type="file" name="image" accept="image/*">
                        </div>
                    </div>

                </div>

                <button class="save-btn" type="submit">
                    Save Profile
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>