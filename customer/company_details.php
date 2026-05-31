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
$user_id = $_SESSION['user_id'];

$profileCheckQ = mysqli_query($conn, "
    SELECT name, phone, address FROM users 
    WHERE id = '$user_id' AND role = 'customer'
");

$profileData = mysqli_fetch_assoc($profileCheckQ);
$is_profile_incomplete = (empty($profileData['name']) || empty($profileData['phone']) || empty($profileData['address']));

$companyQ = mysqli_query($conn, "
    SELECT * FROM companies
    WHERE id='$company_id'
");

if (mysqli_num_rows($companyQ) == 0) {
    header("Location: dashboard.php");
    exit;
}

$company = mysqli_fetch_assoc($companyQ);

if (!empty($company['created_at'])) {
    $today = new DateTime();
    $registration_date = new DateTime($company['created_at']);
    $expiry_date = clone $registration_date;
    $expiry_date->modify('+30 days');

    if ($today >= $expiry_date || $company['subscription_status'] !== 'Active') {
        echo "<script>
            alert('We are sorry. This company services are currently unavailable due to subscription maintenance.');
            window.location.href = 'dashboard.php';
        </script>";
        exit;
    }
}

$categories = mysqli_query($conn, "
    SELECT * FROM event_categories
    WHERE company_id='$company_id'
    ORDER BY name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($company['company_name']) ?> Details</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 34%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.14), transparent 36%),
        #f4f7fb;
    color: #0f172a;
}

.container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 38px 24px 70px;
}

.back-link {
    display: inline-block;
    margin-bottom: 22px;
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.company-hero {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 0;
    background: white;
    border-radius: 32px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(15,23,42,.12);
    border: 1px solid #e2e8f0;
    margin-bottom: 34px;
}

.company-img {
    width: 100%;
    height: 330px;
    object-fit: cover;
}

.company-placeholder {
    height: 330px;
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    color: white;
    font-size: 80px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.company-info {
    padding: 38px;
}

.plan-badge {
    display: inline-block;
    padding: 9px 15px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 15px;
    text-transform: uppercase;
}

.company-info h1 {
    margin: 0 0 12px;
    font-size: 34px;
    letter-spacing: -.5px;
}

.company-desc {
    color: #475569;
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 22px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
}

.info-box small {
    display: block;
    color: #64748b;
    margin-bottom: 6px;
    font-size: 12px;
}

.info-box strong {
    font-size: 13px;
    word-break: break-word;
}

.section-header {
    margin-bottom: 22px;
}

.section-header span {
    display: inline-block;
    padding: 8px 14px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 10px;
}

.section-header h2 {
    margin: 0 0 6px;
    font-size: 30px;
}

.section-header p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
}

.category-card {
    position: relative;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 26px;
    padding: 26px;
    overflow: hidden;
    box-shadow: 0 18px 42px rgba(15,23,42,.08);
    transition: .25s ease;
}

.category-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 28px 60px rgba(15,23,42,.14);
}

.category-card::after {
    content: "";
    position: absolute;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: rgba(37,99,235,.08);
    right: -45px;
    top: -45px;
}

.category-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    background: linear-gradient(135deg, #dbeafe, #e0f2fe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin-bottom: 18px;
}

.category-card h3 {
    margin: 0 0 10px;
    font-size: 21px;
}

.category-card p {
    margin: 0 0 22px;
    color: #64748b;
    line-height: 1.6;
    font-size: 13px;
}

.select-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    text-decoration: none;
    font-weight: 800;
    font-size: 13px;
    box-shadow: 0 12px 25px rgba(37,99,235,.25);
}

.empty-state {
    background: white;
    padding: 34px;
    border-radius: 24px;
    box-shadow: 0 18px 42px rgba(15,23,42,.08);
    border: 1px dashed #cbd5e1;
    text-align: center;
}

.empty-state h3 {
    margin: 0 0 8px;
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

@media(max-width: 900px) {
    .company-hero {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .company-img,
    .company-placeholder {
        height: 260px;
    }
}

@media(max-width: 600px) {
    .container {
        padding: 28px 16px 50px;
    }

    .company-info {
        padding: 26px;
    }

    .company-info h1 {
        font-size: 28px;
    }
}
</style>

<script>
function checkProfileBeforeBooking(event, isIncomplete) {
    if (isIncomplete) {
        event.preventDefault();
        alert('Please complete your profile details (Name, Phone Number, and Address) before making a booking.');
        window.location.href = 'account.php';
    }
}
</script>
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <a href="dashboard.php" class="back-link">← Back to companies</a>

    <section class="company-hero">

        <?php if (!empty($company['logo'])) { ?>
            <img src="../uploads/companies/<?= htmlspecialchars($company['logo']) ?>" class="company-img" alt="Company Image">
        <?php } else { ?>
            <div class="company-placeholder">
                <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
            </div>
        <?php } ?>

        <div class="company-info">
            <div class="plan-badge"><?= htmlspecialchars(ucfirst($company['plan'])) ?> Plan</div>

            <h1><?= htmlspecialchars($company['company_name']) ?></h1>

            <p class="company-desc">
                <?= !empty($company['description']) ? htmlspecialchars($company['description']) : 'No description available.' ?>
            </p>

            <div class="info-grid">
                <div class="info-box">
                    <small>Address</small>
                    <strong><?= !empty($company['address']) ? htmlspecialchars($company['address']) : 'Not provided' ?></strong>
                </div>

                <div class="info-box">
                    <small>Phone</small>
                    <strong><?= !empty($company['phone']) ? htmlspecialchars($company['phone']) : 'Not provided' ?></strong>
                </div>

                <div class="info-box">
                    <small>Email</small>
                    <strong><?= !empty($company['email']) ? htmlspecialchars($company['email']) : 'Not provided' ?></strong>
                </div>
            </div>
        </div>

    </section>

    <div class="section-header">
        <span>Event Category</span>
        <h2>Choose Event Category</h2>
        <p>Select the type of event you want to book with this company.</p>
    </div>

    <?php if (mysqli_num_rows($categories) == 0) { ?>
        <div class="empty-state">
            <h3>No categories available</h3>
            <p>This company has not added any event categories yet.</p>
        </div>
    <?php } ?>

    <div class="category-grid">

        <?php while($cat = mysqli_fetch_assoc($categories)) { ?>

            <div class="category-card">
                <div class="category-icon">📅</div>

                <h3><?= htmlspecialchars($cat['name']) ?></h3>

                <p>
                    View available packages, choose your event date and continue your booking for this category.
                </p>

                <a href="booking.php?company_id=<?= $company_id ?>&category_id=<?= $cat['id'] ?>"
                   class="select-btn"
                   onclick="checkProfileBeforeBooking(event, <?= $is_profile_incomplete ? 'true' : 'false' ?>)">
                    Select Event →
                </a>
            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>