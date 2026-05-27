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

    if (
        $company_name == "" ||
        $email == "" ||
        $phone == "" ||
        $address == "" ||
        $description == ""
    ) {
        $error = "All fields are required.";
    }
    elseif (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone)) {
        $error = "Phone number must follow the format 012-3456789.";
    }
    else {

        /* UPLOAD LOGO */
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {

            $fileTmp = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
            } else {

                $newLogoName = time() . "_" . rand(1000,9999) . "." . $ext;

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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

.profile-page{
    min-height:100vh;
    padding:40px;
    background:
    linear-gradient(
        135deg,
        #edf2f7,
        #f7fafc
    );
}

.profile-wrapper{
    max-width:1200px;
    margin:auto;
}

.profile-card{
    background:#fff;
    border-radius:28px;
    overflow:hidden;
    display:grid;
    grid-template-columns:340px 1fr;
    box-shadow:
    0 20px 50px rgba(0,0,0,0.08);
}

/* LEFT SIDE */

.profile-sidebar{
    background:
    linear-gradient(
        160deg,
        #041f17,
        #0a3b2b,
        #0d5b40
    );

    color:white;
    padding:40px;
    position:relative;
}

.profile-sidebar::before{
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:180px;
    height:180px;
    background:rgba(255,255,255,0.05);
    border-radius:50%;
}

.profile-sidebar::after{
    content:'';
    position:absolute;
    bottom:-70px;
    left:-70px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,0.05);
    border-radius:50%;
}

.company-badge{
    display:inline-block;
    padding:8px 14px;
    border-radius:50px;
    background:rgba(255,255,255,0.12);
    font-size:13px;
    margin-bottom:20px;
}

.sidebar-title{
    font-size:30px;
    font-weight:700;
    margin-bottom:14px;
    line-height:1.2;
}

.sidebar-desc{
    color:#d8e7df;
    line-height:1.8;
    font-size:14px;
}

.company-logo-preview{
    width:100%;
    height:220px;
    object-fit:cover;
    border-radius:20px;
    margin-top:35px;
    border:3px solid rgba(255,255,255,0.15);
    box-shadow:
    0 10px 30px rgba(0,0,0,0.2);
}

/* RIGHT SIDE */

.profile-content{
    padding:45px;
}

.top-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:35px;
}

.header-title h2{
    font-size:32px;
    color:#16251d;
    margin-bottom:8px;
}

.header-title p{
    color:#7b8794;
    font-size:14px;
}

/* ALERT */

.alert{
    padding:15px 18px;
    border-radius:14px;
    margin-bottom:22px;
    font-weight:600;
    font-size:14px;
}

.alert.error{
    background:#ffe9e9;
    color:#c62828;
}

.alert.success{
    background:#e7f9ef;
    color:#0c8f52;
}

/* FORM */

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group.full{
    grid-column:1 / -1;
}

label{
    margin-bottom:8px;
    color:#233229;
    font-weight:600;
    font-size:14px;
}

input,
textarea{
    width:100%;
    padding:15px 16px;
    border-radius:14px;
    border:1px solid #d9e0e6;
    background:#fafafa;
    font-size:14px;
    transition:0.25s ease;
    outline:none;
}

textarea{
    min-height:140px;
    resize:vertical;
}

input:focus,
textarea:focus{
    border-color:#0bbf66;
    background:white;
    box-shadow:
    0 0 0 5px rgba(11,191,102,0.08);
}

/* FILE INPUT */

.file-upload{
    padding:12px;
    background:#f5f7fa;
    border:2px dashed #cfd8dc;
    border-radius:16px;
}

/* BUTTON */

.btn-save{
    margin-top:28px;
    border:none;
    background:
    linear-gradient(
        135deg,
        #0b8f4d,
        #10c96d
    );

    color:white;
    padding:16px 30px;
    border-radius:16px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;

    box-shadow:
    0 12px 30px rgba(16,201,109,0.28);

    transition:0.25s ease;
}

.btn-save:hover{
    transform:translateY(-3px);
}

/* RESPONSIVE */

@media(max-width:950px){

    .profile-card{
        grid-template-columns:1fr;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .profile-page{
        padding:20px;
    }

    .profile-content{
        padding:30px;
    }
}

</style>

<div class="profile-page">

    <div class="profile-wrapper">

        <div class="profile-card">

            <!-- LEFT -->
            <div class="profile-sidebar">

                <div class="company-badge">
                    SaaS Company Panel
                </div>

                <div class="sidebar-title">
                    Company Profile
                </div>

                <div class="sidebar-desc">
                    Manage your company branding, business information,
                    and organization details professionally.
                </div>

                <?php if (!empty($company['logo'])) { ?>

                    <img 
                        src="../uploads/companies/<?= $company['logo'] ?>" 
                        class="company-logo-preview"
                        alt="Company Logo"
                    >

                <?php } ?>

            </div>

            <!-- RIGHT -->
            <div class="profile-content">

                <div class="top-header">

                    <div class="header-title">
                        <h2>Update Information</h2>
                        <p>
                            Keep your company details updated for better professionalism.
                        </p>
                    </div>

                </div>

                <?php if ($error != "") { ?>
                    <div class="alert error">
                        <?= $error ?>
                    </div>
                <?php } ?>

                <?php if ($success != "") { ?>
                    <div class="alert success">
                        <?= $success ?>
                    </div>
                <?php } ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-grid">

                        <div class="form-group">

                            <label>Company Name</label>

                            <input 
                                type="text"
                                name="company_name"
                                value="<?= $company['company_name'] ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Email Address</label>

                            <input 
                                type="email"
                                name="email"
                                value="<?= $company['email'] ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Phone Number</label>

                            <input 
                                type="tel"
                                name="phone"
                                placeholder="012-3456789"
                                pattern="01[0-9]-[0-9]{7,8}"
                                value="<?= $company['phone'] ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Company Address</label>

                            <input 
                                type="text"
                                name="address"
                                value="<?= $company['address'] ?>"
                                required
                            >

                        </div>

                        <div class="form-group full">

                            <label>Description</label>

                            <textarea 
                                name="description"
                                required
                            ><?= $company['description'] ?></textarea>

                        </div>

                        <div class="form-group full">

                            <label>Upload Logo / Banner</label>

                            <input 
                                type="file"
                                name="logo"
                                class="file-upload"
                                accept="image/*"
                            >

                        </div>

                    </div>

                    <button class="btn-save">
                        Save Company Profile
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<?php include('footer.php'); ?>