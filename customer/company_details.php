<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['company_id'])) {
    header("Location: dashboard.php");
    exit;
}

$company_id = $_GET['company_id'];

/* GET COMPANY WITH LIVE EXPIRY CHECK */
$companyQ = mysqli_query($conn, "
    SELECT * FROM companies
    WHERE id='$company_id'
");

if (mysqli_num_rows($companyQ) == 0) {
    header("Location: dashboard.php");
    exit;
}

$company = mysqli_fetch_assoc($companyQ);

// ========================================================
// LOGIK SEKATAN REAL-TIME: KIRA TARIKH LUPUT 30 HARI
// ========================================================
if (!empty($company['created_at'])) {
    $today = new DateTime(); // Tarikh hari ini
    $registration_date = new DateTime($company['created_at']);
    $expiry_date = clone $registration_date;
    $expiry_date->modify('+30 days'); // Tempoh tamat 30 hari

    // JIKA HARI INI SUDAH LEPAS TARIKH LUPUT ATAU STATUS DI DB MEMANG INACTIVE
    if ($today >= $expiry_date || $company['subscription_status'] !== 'Active') {
        echo "<script>
            alert('We are sorry. This company services are currently unavailable due to subscription maintenance.');
            window.location.href = 'dashboard.php';
        </script>";
        exit;
    }
}
// ========================================================

/* GET CATEGORIES FOR THIS COMPANY */
$categories = mysqli_query($conn, "
    SELECT * FROM event_categories
    WHERE company_id='$company_id'
    ORDER BY name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $company['company_name'] ?> Details</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <a href="dashboard.php" class="back-link">← Back to companies</a>

    <div class="company-detail-card">

        <?php if (!empty($company['logo'])) { ?>
            <img src="../uploads/companies/<?= $company['logo'] ?>" 
                 class="company-detail-img"
                 alt="Company Image">
        <?php } else { ?>
            <div class="company-detail-placeholder">
                <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
            </div>
        <?php } ?>

        <div class="company-detail-info">
            <h2><?= $company['company_name'] ?></h2>

            <p class="muted">
                <?= ucfirst($company['plan']) ?> Plan
            </p>

            <p>
                <?= !empty($company['description']) ? $company['description'] : 'No description available.' ?>
            </p>

            <p><strong>Address:</strong> <?= !empty($company['address']) ? $company['address'] : 'Not provided' ?></p>
            <p><strong>Phone:</strong> <?= !empty($company['phone']) ? $company['phone'] : 'Not provided' ?></p>
            <p><strong>Email:</strong> <?= !empty($company['email']) ? $company['email'] : 'Not provided' ?></p>
        </div>

    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Choose Event Category</h2>
            <p class="muted">Select the type of event you want to book</p>
        </div>
    </div>

    <?php if (mysqli_num_rows($categories) == 0) { ?>
        <div class="empty-state">
            <h3>No categories available</h3>
            <p>This company has not added any event categories yet.</p>
        </div>
    <?php } ?>

    <div class="category-grid">

        <?php while($cat = mysqli_fetch_assoc($categories)) { ?>

            <div class="category-customer-card">

                <div class="category-icon">
                    📅
                </div>

                <h3><?= $cat['name'] ?></h3>

                <p>
                    View available packages and choose your event date for this category.
                </p>

                <a href="booking.php?company_id=<?= $company_id ?>&category_id=<?= $cat['id'] ?>" 
                   class="btn btn-primary">
                    Select Event
                </a>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>