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

/* PLAN */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* COUNT */
$countQ = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM packages 
    WHERE company_id='$company_id'
");
$countData = mysqli_fetch_assoc($countQ);

/* FETCH */
$result = mysqli_query($conn, "
    SELECT p.*, c.name as category_name
    FROM packages p
    JOIN event_categories c ON p.event_category_id = c.id
    WHERE p.company_id='$company_id'
    ORDER BY p.id DESC
");
?>

<style>
.package-page{
    min-height:100vh;
    padding:45px;
    background:
        radial-gradient(circle at top left, #d1fae5 0, transparent 28%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.package-container{
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

.hero-actions{
    display:flex;
    gap:12px;
    align-items:center;
}

.add-btn{
    background:linear-gradient(135deg,#10b981,#047857);
    color:white;
    text-decoration:none;
    padding:15px 24px;
    border-radius:16px;
    font-weight:800;
    box-shadow:0 12px 25px rgba(16,185,129,.35);
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:18px;
    margin-bottom:28px;
}

.stat-card{
    background:rgba(255,255,255,.9);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.7);
    border-radius:24px;
    padding:22px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
}

.stat-card span{
    color:#64748b;
    font-size:13px;
    font-weight:700;
}

.stat-card h3{
    margin:8px 0 0;
    font-size:28px;
    color:#0f172a;
}

.alert-success{
    background:#dcfce7;
    color:#166534;
    padding:16px 18px;
    border-radius:18px;
    margin-bottom:22px;
    font-weight:800;
}

.package-grid{
    display:grid;
    grid-template-columns:repeat(2, 1fr);
    gap:24px;
}

.package-card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 20px 45px rgba(15,23,42,.09);
    transition:.2s;
}

.package-card:hover{
    transform:translateY(-5px);
    box-shadow:0 28px 60px rgba(15,23,42,.14);
}

.package-image{
    width:100%;
    height:230px;
    object-fit:cover;
    display:block;
}

.package-body{
    padding:24px;
}

.package-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:14px;
    margin-bottom:16px;
}

.package-title{
    font-size:22px;
    font-weight:900;
    color:#111827;
    margin-bottom:6px;
}

.category-badge{
    background:#ecfdf5;
    color:#047857;
    padding:8px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:800;
    white-space:nowrap;
}

.price{
    font-size:20px;
    font-weight:900;
    color:#059669;
}

.info-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin:18px 0;
}

.info-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:14px;
}

.info-box small{
    color:#64748b;
    font-weight:700;
}

.info-box div{
    margin-top:5px;
    color:#111827;
    font-weight:800;
}

.package-text{
    color:#475569;
    font-size:14px;
    line-height:1.7;
    margin-bottom:12px;
}

.package-text strong{
    color:#111827;
}

.card-actions{
    display:flex;
    gap:12px;
    margin-top:22px;
}

.edit-btn,
.delete-btn{
    flex:1;
    text-align:center;
    padding:13px 16px;
    border-radius:15px;
    text-decoration:none;
    font-weight:800;
}

.edit-btn{
    background:#dcfce7;
    color:#166534;
}

.delete-btn{
    background:#fee2e2;
    color:#991b1b;
}

.edit-btn:hover{
    background:#bbf7d0;
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

.empty-state h3{
    font-size:26px;
    margin-bottom:8px;
}

.empty-state p{
    color:#64748b;
}

.no-image{
    height:230px;
    background:#f1f5f9;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#94a3b8;
    font-weight:800;
}

@media(max-width:900px){
    .package-grid,
    .stats-grid{
        grid-template-columns:1fr;
    }

    .hero-card{
        flex-direction:column;
        align-items:flex-start;
    }
}

@media(max-width:768px){
    .package-page{
        padding:20px;
    }

    .hero-card{
        padding:26px;
    }

    .hero-card h1{
        font-size:28px;
    }

    .info-row{
        grid-template-columns:1fr;
    }

    .card-actions{
        flex-direction:column;
    }
}
</style>

<div class="package-page">

    <div class="package-container">

        <div class="hero-card">
            <div>
                <span class="hero-label">Package Management</span>
                <h1>Manage Packages</h1>
                <p>View, edit and manage all event packages in one premium dashboard.</p>
            </div>

            <div class="hero-actions">
                <a href="add.php" class="add-btn">+ Add Package</a>
            </div>
        </div>

        <?php if(isset($_GET['deleted'])) { ?>
            <div class="alert-success">
                Package deleted successfully.
            </div>
        <?php } ?>

        <?php if(isset($_GET['updated'])) { ?>
            <div class="alert-success">
                Package updated successfully.
            </div>
        <?php } ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span>Total Packages</span>
                <h3><?= $countData['total'] ?></h3>
            </div>

            <div class="stat-card">
                <span>Package Limit</span>
                <h3><?= $limits['packages_per_category'] ?></h3>
            </div>

            <div class="stat-card">
                <span>Current Status</span>
                <h3>Active</h3>
            </div>
        </div>

        <?php if(mysqli_num_rows($result) == 0){ ?>

            <div class="empty-state">
                <h3>No packages yet</h3>
                <p>Create your first package to start selling your event services.</p>
            </div>

        <?php } else { ?>

            <div class="package-grid">

                <?php while($row = mysqli_fetch_assoc($result)){ ?>

                    <div class="package-card">

                        <?php if (!empty($row['image'])) { ?>
                            <img 
                                src="../../uploads/packages/<?= htmlspecialchars($row['image']) ?>" 
                                alt="Package Image" 
                                class="package-image"
                            >
                        <?php } else { ?>
                            <div class="no-image">
                                No Image
                            </div>
                        <?php } ?>

                        <div class="package-body">

                            <div class="package-top">
                                <div>
                                    <div class="package-title">
                                        <?= htmlspecialchars($row['package_name']) ?>
                                    </div>

                                    <div class="price">
                                        RM <?= number_format($row['base_price'], 2) ?>
                                    </div>
                                </div>

                                <div class="category-badge">
                                    <?= htmlspecialchars($row['category_name']) ?>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-box">
                                    <small>Pax</small>
                                    <div><?= htmlspecialchars($row['pax']) ?> pax</div>
                                </div>

                                <div class="info-box">
                                    <small>Package ID</small>
                                    <div>#<?= htmlspecialchars($row['id']) ?></div>
                                </div>
                            </div>

                            <div class="package-text">
                                <strong>Description:</strong>
                                <?= htmlspecialchars($row['description']) ?>
                            </div>

                            <div class="package-text">
                                <strong>Menu:</strong>
                                <?= htmlspecialchars($row['menu']) ?>
                            </div>

                            <div class="card-actions">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="edit-btn">
                                    Edit Package
                                </a>

                                <a href="delete.php?id=<?= $row['id'] ?>" class="delete-btn">
                                    Delete
                                </a>
                            </div>

                        </div>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</div>

<?php include('../footer.php'); ?>