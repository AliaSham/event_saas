<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* VALIDATE ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: index.php?error=invalid");
    exit;
}

$id = intval($_GET['id']);

/* GET PACKAGE */
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM packages
    WHERE id = ?
    AND company_id = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

/* IF PACKAGE NOT FOUND */
if (!$data) {

    header("Location: index.php?error=notfound");
    exit;
}

/* DELETE PROCESS */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* DELETE IMAGE */
    if (!empty($data['image'])) {

        $imagePath = __DIR__ . "/../../uploads/packages/" . $data['image'];

        if (file_exists($imagePath) && is_file($imagePath)) {
            unlink($imagePath);
        }
    }

    /* DELETE PACKAGE */
    $deleteStmt = mysqli_prepare($conn, "
        DELETE FROM packages
        WHERE id = ?
        AND company_id = ?
    ");

    mysqli_stmt_bind_param($deleteStmt, "ii", $id, $company_id);
    mysqli_stmt_execute($deleteStmt);

    header("Location: index.php?deleted=1");
    exit;
}
?>

<style>

.page-wrapper{
    min-height:100vh;
    padding:40px;
    background:
        radial-gradient(circle at top left, #d1fae5 0, transparent 30%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.delete-container{
    max-width:700px;
    margin:auto;
}

.delete-card{
    background:rgba(255,255,255,0.92);
    backdrop-filter:blur(14px);
    border-radius:28px;
    padding:40px;
    box-shadow:0 25px 60px rgba(15,23,42,0.10);
    text-align:center;
}

.icon-box{
    width:100px;
    height:100px;
    margin:auto;
    border-radius:50%;
    background:#fee2e2;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:42px;
    margin-bottom:25px;
}

.delete-card h1{
    font-size:32px;
    margin-bottom:12px;
    color:#111827;
}

.delete-card p{
    color:#6b7280;
    line-height:1.7;
    margin-bottom:30px;
}

.package-preview{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:22px;
    padding:20px;
    margin-bottom:30px;
}

.package-preview img{
    width:100%;
    height:260px;
    object-fit:cover;
    border-radius:18px;
    margin-bottom:18px;
}

.package-name{
    font-size:22px;
    font-weight:800;
    color:#111827;
    margin-bottom:8px;
}

.package-info{
    color:#6b7280;
    font-size:14px;
}

.button-group{
    display:flex;
    justify-content:center;
    gap:16px;
}

.cancel-btn{
    padding:14px 24px;
    border-radius:16px;
    background:#e5e7eb;
    color:#111827;
    text-decoration:none;
    font-weight:700;
    transition:.2s;
}

.cancel-btn:hover{
    background:#d1d5db;
}

.delete-btn{
    padding:14px 24px;
    border:none;
    border-radius:16px;
    background:linear-gradient(135deg,#ef4444,#b91c1c);
    color:white;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 12px 25px rgba(239,68,68,0.35);
    transition:.2s;
}

.delete-btn:hover{
    transform:translateY(-2px);
    background:linear-gradient(135deg,#dc2626,#991b1b);
}

.warning-text{
    margin-top:22px;
    font-size:13px;
    color:#9ca3af;
}

@media(max-width:768px){

    .page-wrapper{
        padding:20px;
    }

    .delete-card{
        padding:25px;
    }

    .button-group{
        flex-direction:column;
    }

    .cancel-btn,
    .delete-btn{
        width:100%;
    }
}

</style>

<div class="page-wrapper">

    <div class="delete-container">

        <div class="delete-card">

            <div class="icon-box">
                ⚠️
            </div>

            <h1>Delete Package</h1>

            <p>
                Are you sure you want to permanently delete this package?
                This action cannot be undone.
            </p>

            <div class="package-preview">

                <?php if (!empty($data['image'])) { ?>

                    <img src="../../uploads/packages/<?= $data['image'] ?>">

                <?php } ?>

                <div class="package-name">
                    <?= htmlspecialchars($data['package_name']) ?>
                </div>

                <div class="package-info">
                    Pax:
                    <?= htmlspecialchars($data['pax']) ?>
                    •
                    RM <?= number_format($data['base_price'], 2) ?>
                </div>

            </div>

            <form method="POST">

                <div class="button-group">

                    <a href="index.php" class="cancel-btn">
                        Cancel
                    </a>

                    <button type="submit" class="delete-btn">
                        Yes, Delete Package
                    </button>

                </div>

            </form>

            <div class="warning-text">
                Deleted package data cannot be recovered.
            </div>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>