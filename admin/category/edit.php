<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = intval($_SESSION['company_id']);
$error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid");
    exit;
}

$id = intval($_GET['id']);

$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

/* GET CURRENT CATEGORY */
$stmt = mysqli_prepare($conn, "
    SELECT * 
    FROM event_categories 
    WHERE id = ? 
    AND company_id = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: index.php?error=notfound");
    exit;
}

/* GET PACKAGE COUNT */
$countStmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total 
    FROM packages 
    WHERE event_category_id = ? 
    AND company_id = ?
");

mysqli_stmt_bind_param($countStmt, "ii", $id, $company_id);
mysqli_stmt_execute($countStmt);

$countResult = mysqli_stmt_get_result($countStmt);
$countData = mysqli_fetch_assoc($countResult);
$totalPackages = $countData['total'];

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!empty($_POST['category_name'])) {

        $name = $_POST['category_name'];

        $checkStmt = mysqli_prepare($conn, "
            SELECT id 
            FROM event_categories 
            WHERE name = ? 
            AND company_id = ? 
            AND id != ?
        ");

        mysqli_stmt_bind_param($checkStmt, "sii", $name, $company_id, $id);
        mysqli_stmt_execute($checkStmt);

        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $error = "Category already exists.";
        } else {

            $updateStmt = mysqli_prepare($conn, "
                UPDATE event_categories 
                SET name = ? 
                WHERE id = ? 
                AND company_id = ?
            ");

            mysqli_stmt_bind_param($updateStmt, "sii", $name, $id, $company_id);
            mysqli_stmt_execute($updateStmt);

            header("Location: index.php?updated=1");
            exit;
        }

    } else {
        $error = "Please select a category.";
    }
}
?>

<style>
.category-page{
    min-height:100vh;
    padding:45px;
    background:
        radial-gradient(circle at top left, #d1fae5 0, transparent 28%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.category-container{
    max-width:1100px;
    margin:auto;
}

.hero-card{
    background:#052e1b;
    color:white;
    padding:32px 36px;
    border-radius:30px;
    margin-bottom:28px;
    box-shadow:0 20px 45px rgba(5,46,27,.25);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
}

.hero-card h1{
    margin:8px 0;
    font-size:36px;
    color:white;
}

.hero-card p{
    margin:0;
    color:#bbf7d0;
}

.hero-label{
    font-size:13px;
    color:#86efac;
    font-weight:800;
    letter-spacing:.8px;
    text-transform:uppercase;
}

.status-badge{
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.25);
    color:white;
    padding:14px 18px;
    border-radius:18px;
    font-weight:800;
    white-space:nowrap;
}

.content-grid{
    display:grid;
    grid-template-columns:1.1fr .8fr;
    gap:26px;
}

.form-card,
.preview-card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:28px;
    padding:34px;
    box-shadow:0 22px 55px rgba(15,23,42,.10);
}

.card-title{
    font-size:24px;
    font-weight:900;
    color:#111827;
    margin-bottom:8px;
}

.card-subtitle{
    color:#64748b;
    font-size:14px;
    margin-bottom:24px;
}

.form-group{
    display:flex;
    flex-direction:column;
    margin-bottom:22px;
}

.form-group label{
    color:#0f172a;
    font-size:14px;
    font-weight:800;
    margin-bottom:10px;
}

.form-group select{
    width:100%;
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:15px 18px;
    font-size:14px;
    outline:none;
    box-shadow:0 8px 20px rgba(15,23,42,.04);
    transition:.2s;
}

.form-group select:focus{
    border-color:#10b981;
    box-shadow:0 0 0 4px rgba(16,185,129,.12);
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:16px 18px;
    border-radius:16px;
    margin-bottom:22px;
    font-weight:800;
}

.action-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-top:26px;
}

.back-btn{
    text-decoration:none;
    color:#2563eb;
    font-weight:800;
}

.submit-btn{
    background:linear-gradient(135deg,#10b981,#047857);
    color:white;
    border:none;
    padding:15px 28px;
    border-radius:16px;
    font-size:15px;
    font-weight:900;
    cursor:pointer;
    box-shadow:0 12px 25px rgba(16,185,129,.35);
    transition:.2s;
}

.submit-btn:hover{
    background:linear-gradient(135deg,#059669,#065f46);
    transform:translateY(-2px);
}

.preview-icon{
    width:72px;
    height:72px;
    border-radius:24px;
    background:#ecfdf5;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
    margin-bottom:22px;
}

.preview-name{
    font-size:28px;
    font-weight:900;
    color:#111827;
    margin-bottom:8px;
}

.preview-meta{
    color:#64748b;
    margin-bottom:24px;
    font-size:14px;
}

.info-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    padding:16px 18px;
    border-radius:18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:14px;
}

.info-box span{
    color:#64748b;
    font-weight:800;
    font-size:13px;
}

.info-box strong{
    color:#111827;
    font-size:18px;
}

.tips-box{
    margin-top:22px;
    background:#ecfdf5;
    border:1px solid #bbf7d0;
    color:#166534;
    padding:16px;
    border-radius:18px;
    font-size:13px;
    line-height:1.7;
    font-weight:700;
}

.category-list{
    display:grid;
    gap:12px;
}

.category-pill{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:13px 15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:800;
    color:#334155;
}

.category-pill.active{
    background:#ecfdf5;
    border-color:#10b981;
    color:#047857;
}

.active-badge{
    background:#10b981;
    color:white;
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
}

@media(max-width:900px){
    .content-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){
    .category-page{
        padding:20px;
    }

    .hero-card{
        flex-direction:column;
        align-items:flex-start;
        padding:26px;
    }

    .hero-card h1{
        font-size:28px;
    }

    .form-card,
    .preview-card{
        padding:25px;
    }

    .action-row{
        flex-direction:column;
        align-items:stretch;
    }

    .submit-btn{
        width:100%;
    }
}
</style>

<div class="category-page">

    <div class="category-container">

        <div class="hero-card">
            <div>
                <span class="hero-label">Event Category Management</span>
                <h1>Edit Category</h1>
                <p>Update your event category and keep your package system organized.</p>
            </div>

            <div class="status-badge">
                Editing Mode
            </div>
        </div>

        <div class="content-grid">

            <div class="form-card">

                <div class="card-title">Update Category</div>
                <div class="card-subtitle">
                    Select a new category name from the available list.
                </div>

                <?php if ($error != "") { ?>
                    <div class="alert-error">
                        <?= $error ?>
                    </div>
                <?php } ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Select Category</label>

                        <select name="category_name" required>
                            <option value="">Choose Event Category</option>

                            <?php foreach ($categories as $cat) { ?>
                                <option 
                                    value="<?= htmlspecialchars($cat) ?>"
                                    <?php if ($row['name'] == $cat) echo "selected"; ?>
                                >
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="action-row">
                        <a href="index.php" class="back-btn">
                            ← Back to categories
                        </a>

                        <button class="submit-btn">
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>

            <div class="preview-card">

                <div class="preview-icon">
                    <?php
                        if ($row['name'] == "Wedding") echo "💍";
                        elseif ($row['name'] == "Birthday") echo "🎂";
                        elseif ($row['name'] == "Corporate") echo "🏢";
                        elseif ($row['name'] == "Engagement") echo "💐";
                        elseif ($row['name'] == "Party") echo "🎉";
                        else echo "📁";
                    ?>
                </div>

                <div class="preview-name">
                    <?= htmlspecialchars($row['name']) ?>
                </div>

                <div class="preview-meta">
                    Current category preview
                </div>

                <div class="info-box">
                    <span>Category ID</span>
                    <strong>#<?= htmlspecialchars($row['id']) ?></strong>
                </div>

                <div class="info-box">
                    <span>Total Packages</span>
                    <strong><?= $totalPackages ?></strong>
                </div>

                <div class="info-box">
                    <span>Status</span>
                    <strong>Active</strong>
                </div>

                <div class="tips-box">
                    Tip: Updating category name will affect how related packages are grouped in your system.
                </div>

            </div>

        </div>

        <br>

        <div class="preview-card">

            <div class="card-title">Available Category Options</div>
            <div class="card-subtitle">
                Current selected category is highlighted below.
            </div>

            <div class="category-list">

                <?php foreach ($categories as $cat) { ?>

                    <div class="category-pill <?= ($row['name'] == $cat) ? 'active' : '' ?>">
                        <span><?= htmlspecialchars($cat) ?></span>

                        <?php if ($row['name'] == $cat) { ?>
                            <span class="active-badge">Current</span>
                        <?php } ?>
                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>