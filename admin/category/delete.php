<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid");
    exit;
}

$id = intval($_GET['id']);
$company_id = intval($_SESSION['company_id']);

/* GET CATEGORY */
$stmt = mysqli_prepare($conn, "
    SELECT id, name 
    FROM event_categories 
    WHERE id = ? 
    AND company_id = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

if (!$category) {
    header("Location: index.php?error=notfound");
    exit;
}

/* CHECK TOTAL PACKAGES */
$packageStmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total 
    FROM packages 
    WHERE event_category_id = ? 
    AND company_id = ?
");

mysqli_stmt_bind_param($packageStmt, "ii", $id, $company_id);
mysqli_stmt_execute($packageStmt);

$packageResult = mysqli_stmt_get_result($packageStmt);
$packageData = mysqli_fetch_assoc($packageResult);
$totalPackages = $packageData['total'];

/* DELETE PROCESS */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($totalPackages > 0) {
        header("Location: index.php?error=category_used");
        exit;
    }

    $deleteStmt = mysqli_prepare($conn, "
        DELETE FROM event_categories 
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
.delete-page{
    min-height:100vh;
    padding:45px;
    background:
        radial-gradient(circle at top left, #fee2e2 0, transparent 30%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.delete-container{
    max-width:760px;
    margin:auto;
}

.delete-card{
    background:rgba(255,255,255,.94);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:32px;
    padding:42px;
    box-shadow:0 25px 65px rgba(15,23,42,.12);
    text-align:center;
}

.warning-icon{
    width:100px;
    height:100px;
    margin:0 auto 24px;
    border-radius:50%;
    background:#fee2e2;
    color:#dc2626;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:46px;
}

.delete-card h1{
    font-size:34px;
    margin:0 0 12px;
    color:#111827;
}

.delete-card p{
    color:#64748b;
    line-height:1.7;
    margin-bottom:28px;
}

.category-preview{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:24px;
    padding:24px;
    margin-bottom:28px;
    text-align:left;
}

.preview-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 0;
    border-bottom:1px solid #e5e7eb;
}

.preview-row:last-child{
    border-bottom:none;
}

.preview-row span{
    color:#64748b;
    font-weight:700;
}

.preview-row strong{
    color:#111827;
    font-size:16px;
}

.danger-box{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
    padding:18px;
    border-radius:20px;
    margin-bottom:26px;
    font-weight:700;
    line-height:1.6;
}

.safe-box{
    background:#ecfdf5;
    color:#166534;
    border:1px solid #bbf7d0;
    padding:18px;
    border-radius:20px;
    margin-bottom:26px;
    font-weight:700;
    line-height:1.6;
}

.button-group{
    display:flex;
    gap:16px;
    justify-content:center;
}

.cancel-btn,
.delete-btn{
    padding:15px 26px;
    border-radius:16px;
    font-weight:900;
    text-decoration:none;
    border:none;
    cursor:pointer;
    font-size:15px;
}

.cancel-btn{
    background:#e5e7eb;
    color:#111827;
}

.delete-btn{
    background:linear-gradient(135deg,#ef4444,#991b1b);
    color:white;
    box-shadow:0 14px 28px rgba(239,68,68,.35);
}

.delete-btn:hover{
    transform:translateY(-2px);
}

.delete-btn:disabled{
    background:#cbd5e1;
    box-shadow:none;
    cursor:not-allowed;
}

.note-text{
    margin-top:22px;
    color:#94a3b8;
    font-size:13px;
    font-weight:600;
}

@media(max-width:768px){
    .delete-page{
        padding:20px;
    }

    .delete-card{
        padding:28px;
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

<div class="delete-page">

    <div class="delete-container">

        <div class="delete-card">

            <div class="warning-icon">
                ⚠️
            </div>

            <h1>Delete Category?</h1>

            <p>
                Please confirm before deleting this category.
                This action cannot be undone after you click confirm.
            </p>

            <div class="category-preview">

                <div class="preview-row">
                    <span>Category Name</span>
                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                </div>

                <div class="preview-row">
                    <span>Total Packages</span>
                    <strong><?= $totalPackages ?></strong>
                </div>

                <div class="preview-row">
                    <span>Status</span>
                    <strong><?= ($totalPackages > 0) ? "Cannot Delete" : "Ready to Delete" ?></strong>
                </div>

            </div>

            <?php if ($totalPackages > 0) { ?>

                <div class="danger-box">
                    This category cannot be deleted because it still has packages.
                    Please delete or move the packages first.
                </div>

            <?php } else { ?>

                <div class="safe-box">
                    This category has no packages. You can safely delete it.
                </div>

            <?php } ?>

            <form method="POST">

                <div class="button-group">

                    <a href="index.php" class="cancel-btn">
                        No, Cancel
                    </a>

                    <button 
                        type="submit" 
                        class="delete-btn"
                        <?= ($totalPackages > 0) ? "disabled" : "" ?>
                    >
                        Yes, Delete Category
                    </button>

                </div>

            </form>

            <div class="note-text">
                Deleted category data cannot be recovered.
            </div>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>