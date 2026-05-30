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

$categories = ["Wedding", "Birthday", "Corporate", "Engagement", "Party"];

$error = "";

/* GET COMPANY PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* FUNCTION: GET CATEGORY COUNT */
function getCategoryCount($conn, $company_id) {
    $q = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM event_categories 
        WHERE company_id='$company_id'
    ");

    $row = mysqli_fetch_assoc($q);
    return $row['total'];
}

/* GET CURRENT CATEGORY COUNT */
$currentCount = getCategoryCount($conn, $company_id);
$limitReached = ($currentCount >= $limits['categories']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['category_name'];

    $currentCount = getCategoryCount($conn, $company_id);

    if ($currentCount >= $limits['categories']) {
        $error = "You have reached your category limit for this plan.";
    }
    elseif ($name == "") {
        $error = "Please select a category.";
    } 
    else {

        $check = mysqli_query($conn,
            "SELECT * FROM event_categories 
             WHERE name='$name' AND company_id='$company_id'"
        );

        if (mysqli_num_rows($check) > 0) {
            $error = "Category already exists.";
        } else {

            mysqli_query($conn,
                "INSERT INTO event_categories (name, company_id)
                 VALUES ('$name','$company_id')"
            );

            header("Location: index.php?added=1");
            exit;
        }
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

.limit-badge{
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

.progress-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:20px;
    padding:18px;
    margin-bottom:24px;
}

.progress-top{
    display:flex;
    justify-content:space-between;
    font-size:14px;
    font-weight:800;
    color:#334155;
    margin-bottom:12px;
}

.progress-bar{
    width:100%;
    height:12px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    background:linear-gradient(135deg,#10b981,#047857);
    border-radius:999px;
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

.back-btn{
    display:inline-block;
    margin-top:22px;
    text-decoration:none;
    color:#2563eb;
    font-weight:800;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:16px 18px;
    border-radius:16px;
    margin-bottom:22px;
    font-weight:800;
}

.upgrade-box{
    background:#fff7ed;
    border:1px solid #fed7aa;
    color:#9a3412;
    padding:18px;
    border-radius:18px;
    margin-bottom:22px;
    font-weight:700;
    line-height:1.6;
}

.upgrade-btn{
    display:inline-block;
    background:linear-gradient(135deg,#f97316,#c2410c);
    color:white;
    text-decoration:none;
    padding:14px 24px;
    border-radius:16px;
    font-weight:900;
    box-shadow:0 12px 25px rgba(249,115,22,.25);
}

.category-list{
    display:grid;
    gap:14px;
}

.category-option{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:15px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.category-option span{
    font-weight:800;
    color:#111827;
}

.category-option small{
    color:#64748b;
    font-weight:700;
}

.category-icon{
    width:42px;
    height:42px;
    border-radius:15px;
    background:#ecfdf5;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
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

    .submit-btn,
    .upgrade-btn{
        width:100%;
        text-align:center;
    }
}
</style>

<div class="category-page">

    <div class="category-container">

        <div class="hero-card">
            <div>
                <span class="hero-label">Event Category Setup</span>
                <h1>Add Category</h1>
                <p>Select a category to organize your event packages professionally.</p>
            </div>

            <div class="limit-badge">
                <?= $currentCount ?> / <?= $limits['categories'] ?> Used
            </div>
        </div>

        <div class="content-grid">

            <div class="form-card">

                <div class="card-title">Create New Category</div>
                <div class="card-subtitle">
                    Choose one category from the available event category list.
                </div>

                <div class="progress-box">
                    <div class="progress-top">
                        <span>Category Usage</span>
                        <span><?= $currentCount ?> / <?= $limits['categories'] ?></span>
                    </div>

                    <div class="progress-bar">
                        <div 
                            class="progress-fill" 
                            style="width: <?= ($limits['categories'] > 0) ? min(100, ($currentCount / $limits['categories']) * 100) : 0 ?>%;">
                        </div>
                    </div>
                </div>

                <?php if ($error != "") { ?>
                    <div class="alert-error">
                        <?= $error ?>
                    </div>
                <?php } ?>

                <?php if ($limitReached) { ?>

                    <div class="upgrade-box">
                        You reached your category limit. Upgrade your plan to add more categories.
                    </div>

                    <a href="../subscription/upgrade.php" class="upgrade-btn">
                        Upgrade Plan
                    </a>

                <?php } else { ?>

                    <form method="POST">

                        <div class="form-group">
                            <label>Select Category</label>

                            <select name="category_name" required>
                                <option value="">Choose Event Category</option>

                                <?php foreach ($categories as $cat) { ?>
                                    <option value="<?= $cat ?>">
                                        <?= $cat ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <button class="submit-btn">
                            Add Category
                        </button>

                    </form>

                <?php } ?>

                <a href="index.php" class="back-btn">
                    ← Back to categories
                </a>

            </div>

            <div class="preview-card">

                <div class="card-title">Available Categories</div>
                <div class="card-subtitle">
                    These categories can be used to group your event packages.
                </div>

                <div class="category-list">

                    <?php foreach ($categories as $cat) { ?>

                        <div class="category-option">
                            <div>
                                <span><?= $cat ?></span><br>
                                <small>Event service type</small>
                            </div>

                            <div class="category-icon">
                                <?php
                                    if ($cat == "Wedding") echo "💍";
                                    elseif ($cat == "Birthday") echo "🎂";
                                    elseif ($cat == "Corporate") echo "🏢";
                                    elseif ($cat == "Engagement") echo "💐";
                                    elseif ($cat == "Party") echo "🎉";
                                    else echo "📁";
                                ?>
                            </div>
                        </div>

                    <?php } ?>

                </div>

                <div class="tips-box">
                    Tip: Categories help customers find packages faster and make your system look more organized.
                </div>

            </div>

        </div>

    </div>

</div>

<?php include('../footer.php'); ?>