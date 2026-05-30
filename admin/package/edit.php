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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

/* GET PACKAGE DATA */
$packageQ = mysqli_prepare($conn, "
    SELECT * FROM packages 
    WHERE id = ? 
    AND company_id = ?
");

mysqli_stmt_bind_param($packageQ, "ii", $id, $company_id);
mysqli_stmt_execute($packageQ);

$result = mysqli_stmt_get_result($packageQ);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$package = mysqli_fetch_assoc($result);

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
        $error = "All fields are required.";
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
                $uploadDir = __DIR__ . "/../../uploads/packages/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploadPath = $uploadDir . $newImageName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {

                    if (!empty($package['image'])) {
                        $oldImagePath = $uploadDir . $package['image'];

                        if (file_exists($oldImagePath) && is_file($oldImagePath)) {
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

            $updateStmt = mysqli_prepare($conn, "
                UPDATE packages SET
                    event_category_id = ?,
                    package_name = ?,
                    pax = ?,
                    description = ?,
                    base_price = ?,
                    menu = ?,
                    image = ?
                WHERE id = ?
                AND company_id = ?
            ");

            mysqli_stmt_bind_param(
                $updateStmt,
                "isissssii",
                $category,
                $name,
                $pax,
                $description,
                $price,
                $menu,
                $imageName,
                $id,
                $company_id
            );

            mysqli_stmt_execute($updateStmt);

            header("Location: index.php?updated=1");
            exit;
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

.edit-container{
    max-width:1180px;
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

.status-badge{
    background:rgba(255,255,255,0.14);
    color:white;
    border:1px solid rgba(255,255,255,0.25);
    padding:13px 18px;
    border-radius:18px;
    font-size:14px;
    font-weight:700;
}

.content-grid{
    display:grid;
    grid-template-columns:1.4fr .8fr;
    gap:26px;
}

.form-card,
.preview-card{
    background:rgba(255,255,255,0.9);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,0.7);
    border-radius:28px;
    padding:36px;
    box-shadow:0 25px 60px rgba(15,23,42,0.10);
}

.card-title{
    font-size:22px;
    font-weight:800;
    margin-bottom:6px;
    color:#111827;
}

.card-subtitle{
    color:#6b7280;
    margin-bottom:24px;
    font-size:14px;
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
    box-sizing:border-box;
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
    padding:26px;
}

.upload-box input{
    border:none;
    box-shadow:none;
    background:transparent;
    padding:0;
}

.image-note{
    font-size:13px;
    color:#6b7280;
    margin-top:10px;
}

.current-image{
    width:100%;
    height:260px;
    object-fit:cover;
    border-radius:22px;
    margin-bottom:18px;
    border:1px solid #e5e7eb;
}

.no-image{
    height:260px;
    border-radius:22px;
    background:#f3f4f6;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#9ca3af;
    font-weight:700;
    margin-bottom:18px;
}

.preview-name{
    font-size:24px;
    font-weight:900;
    color:#111827;
    margin-bottom:10px;
}

.preview-detail{
    display:flex;
    justify-content:space-between;
    background:#f9fafb;
    padding:14px 16px;
    border-radius:16px;
    margin-bottom:12px;
    color:#374151;
    font-size:14px;
}

.preview-detail strong{
    color:#111827;
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
    font-weight:700;
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
    font-weight:700;
}

@media(max-width:900px){
    .content-grid{
        grid-template-columns:1fr;
    }
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

    .form-card,
    .preview-card{
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

    <div class="edit-container">

        <div class="top-section">
            <div>
                <span class="small-title">Package Management</span>
                <h1>Edit Package</h1>
                <p>Update your package details, pricing, menu and image.</p>
            </div>

            <div class="status-badge">
                Editing Mode
            </div>
        </div>

        <div class="content-grid">

            <div class="form-card">

                <div class="card-title">Package Information</div>
                <div class="card-subtitle">Make sure all information is correct before saving.</div>

                <?php if ($error != "") { ?>
                    <div class="alert-error">
                        <?= $error ?>
                    </div>
                <?php } ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Package Name</label>
                            <input 
                                type="text" 
                                name="package_name" 
                                value="<?= htmlspecialchars($package['package_name']) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Pax</label>
                            <input 
                                type="number" 
                                name="pax" 
                                min="1"
                                value="<?= htmlspecialchars($package['pax']) ?>"
                                required
                            >
                        </div>

                        <div class="form-group full">
                            <label>Package Description</label>
                            <textarea name="description" required><?= htmlspecialchars($package['description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Price (RM)</label>
                            <input 
                                type="number" 
                                name="price" 
                                min="0"
                                step="0.01"
                                value="<?= htmlspecialchars($package['base_price']) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Select Category</label>
                            <select name="category_id" required>
                                <option value="">Choose Category</option>

                                <?php while($cat = mysqli_fetch_assoc($catResult)){ ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?php if ($cat['id'] == $package['event_category_id']) { echo "selected"; } ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label>Package Menu</label>
                            <textarea name="menu" required><?= htmlspecialchars($package['menu']) ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label>Replace Package Image</label>

                            <div class="upload-box">
                                <input 
                                    type="file" 
                                    name="image" 
                                    accept="image/*"
                                >

                                <div class="image-note">
                                    Upload new image only if you want to replace the current image.
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="action-row">
                        <a href="index.php" class="back-btn">← Back to packages</a>
                        <button class="submit-btn">Save Changes</button>
                    </div>

                </form>

            </div>

            <div class="preview-card">

                <div class="card-title">Current Preview</div>
                <div class="card-subtitle">This is the current package image and details.</div>

                <?php if (!empty($package['image'])) { ?>
                    <img 
                        src="../../uploads/packages/<?= htmlspecialchars($package['image']) ?>" 
                        class="current-image"
                        alt="Package Image"
                    >
                <?php } else { ?>
                    <div class="no-image">
                        No Image Uploaded
                    </div>
                <?php } ?>

                <div class="preview-name">
                    <?= htmlspecialchars($package['package_name']) ?>
                </div>

                <div class="preview-detail">
                    <span>Pax</span>
                    <strong><?= htmlspecialchars($package['pax']) ?></strong>
                </div>

                <div class="preview-detail">
                    <span>Price</span>
                    <strong>RM <?= number_format($package['base_price'], 2) ?></strong>
                </div>

                <div class="preview-detail">
                    <span>Status</span>
                    <strong>Editable</strong>
                </div>

            </div>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>