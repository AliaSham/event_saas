<?php
session_start();
include('../../config/db.php');
include('../header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

$result = mysqli_query($conn, "
    SELECT c.*,
    (
        SELECT COUNT(*) 
        FROM packages p 
        WHERE p.event_category_id = c.id 
        AND p.company_id = '$company_id'
    ) AS total_packages
    FROM event_categories c
    WHERE c.company_id='$company_id'
    ORDER BY c.id DESC
");

$countQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM event_categories 
    WHERE company_id='$company_id'
");

$countData = mysqli_fetch_assoc($countQ);
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
    max-width:1200px;
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

.add-btn{
    background:linear-gradient(135deg,#10b981,#047857);
    color:white;
    text-decoration:none;
    padding:15px 24px;
    border-radius:16px;
    font-weight:800;
    box-shadow:0 12px 25px rgba(16,185,129,.35);
    white-space:nowrap;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:28px;
}

.stat-card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.7);
    border-radius:24px;
    padding:22px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
}

.stat-card span{
    color:#64748b;
    font-size:13px;
    font-weight:800;
}

.stat-card h3{
    margin:8px 0 0;
    font-size:30px;
    color:#0f172a;
}

.category-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
}

.category-card{
    background:rgba(255,255,255,.94);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:28px;
    padding:26px;
    box-shadow:0 20px 45px rgba(15,23,42,.09);
    transition:.2s;
    position:relative;
    overflow:hidden;
}

.category-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:7px;
    background:linear-gradient(135deg,#10b981,#047857);
}

.category-card:hover{
    transform:translateY(-5px);
    box-shadow:0 28px 60px rgba(15,23,42,.14);
}

.category-icon{
    width:58px;
    height:58px;
    border-radius:20px;
    background:#ecfdf5;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
    margin-bottom:18px;
}

.category-name{
    font-size:22px;
    font-weight:900;
    color:#111827;
    margin-bottom:8px;
}

.category-meta{
    color:#64748b;
    font-size:14px;
    margin-bottom:20px;
}

.package-count{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    padding:14px 16px;
    border-radius:18px;
    margin-bottom:22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.package-count span{
    color:#64748b;
    font-weight:700;
    font-size:13px;
}

.package-count strong{
    color:#111827;
    font-size:18px;
}

.card-actions{
    display:flex;
    gap:12px;
}

.delete-btn{
    flex:1;
    text-align:center;
    padding:13px 16px;
    border-radius:15px;
    text-decoration:none;
    font-weight:800;
    background:#fee2e2;
    color:#991b1b;
}

.delete-btn:hover{
    background:#fecaca;
}

.empty-state{
    background:white;
    border-radius:28px;
    padding:60px 30px;
    text-align:center;
    box-shadow:0 20px 45px rgba(15,23,42,.08);
}

.empty-icon{
    font-size:50px;
    margin-bottom:14px;
}

.empty-state h3{
    font-size:26px;
    margin-bottom:8px;
    color:#111827;
}

.empty-state p{
    color:#64748b;
}

@media(max-width:1000px){
    .category-grid,
    .stats-grid{
        grid-template-columns:1fr 1fr;
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

    .category-grid,
    .stats-grid{
        grid-template-columns:1fr;
    }

    .add-btn{
        width:100%;
        text-align:center;
    }
}
</style>

<div class="category-page">

    <div class="category-container">

        <div class="hero-card">
            <div>
                <span class="hero-label">Event Category Management</span>
                <h1>Manage Categories</h1>
                <p>Organize your event services into clean and professional categories.</p>
            </div>

            <a href="add.php" class="add-btn">
                + Add Category
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span>Total Categories</span>
                <h3><?= $countData['total'] ?></h3>
            </div>

            <div class="stat-card">
                <span>System Status</span>
                <h3>Active</h3>
            </div>

            <div class="stat-card">
                <span>Category Type</span>
                <h3>Event</h3>
            </div>
        </div>

        <?php if(mysqli_num_rows($result) == 0){ ?>

            <div class="empty-state">
                <div class="empty-icon">📂</div>
                <h3>No categories yet</h3>
                <p>Start by adding your first event category.</p>
            </div>

        <?php } else { ?>

            <div class="category-grid">

                <?php while($row=mysqli_fetch_assoc($result)){ ?>

                    <div class="category-card">

                        <div class="category-icon">
                            📁
                        </div>

                        <div class="category-name">
                            <?= htmlspecialchars($row['name']) ?>
                        </div>

                        <div class="category-meta">
                            Event service category
                        </div>

                        <div class="package-count">
                            <span>Total Packages</span>
                            <strong><?= $row['total_packages'] ?></strong>
                        </div>

                        <div class="card-actions">
                            <a 
                                href="delete.php?id=<?= $row['id'] ?>" 
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this category?');"
                            >
                                Delete Category
                            </a>
                        </div>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</div>

<?php include('../footer.php'); ?>