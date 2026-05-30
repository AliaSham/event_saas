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

$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

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
        $error = "All fields are required.";
    } else {

        $currentCount = getPackageCountByCategory($conn, $company_id, $category);

        if ($currentCount >= $limits['packages_per_category']) {
            $error = "This category has reached the package limit for your plan.";
        } else {

            $imageName = "";

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

                $fileTmp = $_FILES['image']['tmp_name'];
                $fileName = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
                } else {

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

<style>
.page-wrapper{
    min-height:100vh;
    padding:45px;
    background:
        radial-gradient(circle at top left, #d1fae5 0, transparent 30%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.package-container{
    max-width:1200px;
    margin:auto;
}

.top-section{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#052e1b;
    color:white;
    padding:30px 34px;
    border-radius:28px;
    margin-bottom:26px;
    box-shadow:0 18px 40px rgba(5,46,27,0.25);
}

.top-section h1{
    margin:8px 0;
    font-size:34px;
    color:white;
}

.top-section p{
    margin:0;
    color:#bbf7d0;
}

.small-title{
    font-size:13px;
    color:#86efac;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.8px;
}

.limit-badge{
    background:rgba(255,255,255,0.14);
    color:white;
    border:1px solid rgba(255,255,255,0.25);
    padding:13px 18px;
    border-radius:18px;
    font-size:14px;
    font-weight:700;
    white-space:nowrap;
}

.package-card{
    background:rgba(255,255,255,0.88);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,0.7);
    border-radius:28px;
    padding:38px;
    box-shadow:0 25px 60px rgba(15,23,42,0.10);
}

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
    grid-column:1 / 3;
}

.form-group label{
    color:#0f172a;
    font-size:14px;
    font-weight:700;
    margin-bottom:10px;
}

.form-group input,
.form-group textarea,
.form-group select{
    width:100%;
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:15px 18px;
    font-size:14px;
    outline:none;
    box-shadow:0 8px 20px rgba(15,23,42,0.04);
    transition:.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{
    border-color:#10b981;
    box-shadow:0 0 0 4px rgba(16,185,129,0.12);
}

.form-group textarea{
    min-height:130px;
    resize:vertical;
}

.upload-box{
    background:linear-gradient(135deg,#f0fdf4,#ffffff);
    border:2px dashed #10b981;
    border-radius:20px;
    padding:28px;
    text-align:center;
}

.upload-box input{
    border:none;
    box-shadow:none;
    background:transparent;
    padding:0;
}

.action-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:30px;
}

.back-btn{
    text-decoration:none;
    color:#2563eb;
    font-weight:600;
}

.submit-btn{
    background:linear-gradient(135deg,#10b981,#047857);
    color:white;
    border:none;
    padding:15px 30px;
    border-radius:16px;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 12px 25px rgba(16,185,129,0.35);
    transition:.2s;
}

.submit-btn:hover{
    background:linear-gradient(135deg,#059669,#065f46);
    transform:translateY(-2px);
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:16px 18px;
    border-radius:16px;
    margin-bottom:24px;
    font-weight:600;
}

@media(max-width:768px){
    .page-wrapper{
        padding:20px;
    }

    .top-section{
        flex-direction:column;
        align-items:flex-start;
        gap:18px;
    }

    .top-section h1{
        font-size:28px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .form-group.full{
        grid-column:1;
    }

    .package-card{
        padding:25px;
    }

    .action-row{
        flex-direction:column;
        gap:18px;
        align-items:stretch;
    }

    .submit-btn{
        width:100%;
    }
}
</style>

<div class="page-wrapper">

    <div class="package-container">

        <div class="top-section">

            <div>
                <span class="small-title">Package Management</span>
                <h1>Add New Package</h1>
                <p>Create a professional event package with price, pax, menu and image.</p>
            </div>

            <div class="limit-badge">
                <?= $limits['packages_per_category'] ?> packages / category
            </div>

        </div>

        <div class="package-card">

            <?php if ($error != "") { ?>
                <div class="alert-error">
                    <?= $error ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Package Name</label>
                        <input type="text" name="package_name" placeholder="Example: Grand Wedding Package" required>
                    </div>

                    <div class="form-group">
                        <label>Pax</label>
                        <input type="number" name="pax" placeholder="Example: 300" min="1" required>
                    </div>

                    <div class="form-group full">
                        <label>Package Description</label>
                        <textarea name="description" placeholder="Describe your package here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" name="price" placeholder="Example: 4500" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Select Category</label>
                        <select name="category_id" required>
                            <option value="">Choose Category</option>

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
                    </div>

                    <div class="form-group full">
                        <label>Package Menu</label>
                        <textarea name="menu" placeholder="Example: Nasi minyak, ayam masak merah, dalca..." required></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Upload Package Image</label>
                        <div class="upload-box">
                            <input type="file" name="image" accept="image/*" required>
                        </div>
                    </div>

                </div>

                <div class="action-row">
                    <a href="index.php" class="back-btn">← Back to packages</a>
                    <button class="submit-btn">Add Package</button>
                </div>

            </form>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>