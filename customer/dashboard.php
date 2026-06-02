<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$companies = mysqli_query($conn, "
    SELECT c.*, 
    (SELECT COUNT(*) FROM bookings b 
     WHERE b.company_id = c.id AND b.status != 'Cancelled') AS total_active_bookings
    FROM companies c
    WHERE c.subscription_status = 'Active'
    ORDER BY c.company_name ASC
");

$totalCompanies = mysqli_num_rows($companies);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.18), transparent 35%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.18), transparent 35%),
        #f4f7fb;
    color: #0f172a;
}

.customer-container {
    padding: 38px 58px;
}

.hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a, #1d4ed8, #38bdf8);
    color: white;
    padding: 46px;
    border-radius: 30px;
    margin-bottom: 34px;
    box-shadow: 0 25px 55px rgba(37, 99, 235, .28);
}

.hero::before {
    content: "";
    position: absolute;
    width: 330px;
    height: 330px;
    border-radius: 50%;
    background: rgba(255,255,255,.13);
    right: -90px;
    top: -120px;
}

.hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.10);
    right: 190px;
    bottom: -80px;
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 720px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    font-size: 13px;
    margin-bottom: 18px;
    backdrop-filter: blur(10px);
}

.hero h1 {
    margin: 0 0 12px;
    font-size: 42px;
    line-height: 1.15;
    letter-spacing: -.8px;
}

.hero p {
    margin: 0;
    font-size: 15px;
    opacity: .92;
    line-height: 1.7;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 32px;
}

.stat-card {
    background: rgba(255,255,255,.86);
    border: 1px solid rgba(226,232,240,.9);
    box-shadow: 0 16px 40px rgba(15,23,42,.08);
    border-radius: 22px;
    padding: 20px 22px;
}

.stat-card span {
    font-size: 13px;
    color: #64748b;
}

.stat-card h3 {
    margin: 8px 0 0;
    font-size: 29px;
    color: #0f172a;
}

.browse-header {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.browse-header h2 {
    margin: 0;
    font-size: 28px;
    letter-spacing: -.4px;
}

.muted {
    margin: 6px 0 0;
    font-size: 13px;
    color: #64748b;
}

.browse-tools {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.search-box,
.sort-box {
    height: 46px;
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    background: white;
    padding: 0 16px;
    font-family: inherit;
    outline: none;
    box-shadow: 0 8px 22px rgba(15,23,42,.05);
}

.search-box {
    width: 290px;
}

.search-box:focus,
.sort-box:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,.12);
}

.company-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(285px, 1fr));
    gap: 26px;
}

.company-card {
    position: relative;
    background: rgba(255,255,255,.94);
    border: 1px solid rgba(226,232,240,.95);
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(15,23,42,.09);
    transition: .28s ease;
}

.company-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 28px 65px rgba(15,23,42,.15);
}

.company-image-container {
    position: relative;
    height: 205px;
    overflow: hidden;
    background: linear-gradient(135deg, #2563eb, #38bdf8);
}

.company-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: .35s ease;
}

.company-card:hover .company-card-img {
    transform: scale(1.07);
}

.company-placeholder {
    height: 100%;
    color: white;
    font-size: 66px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-gradient {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15,23,42,.72), transparent 58%);
}

.company-card-body {
    padding: 23px;
}

.company-name {
    margin: 0 0 10px;
    font-size: 20px;
    font-weight: 750;
    color: #0f172a;
}

.desc {
    font-size: 13px;
    color: #475569;
    line-height: 1.65;
    min-height: 64px;
}

.info-list {
    margin: 15px 0 18px;
    display: grid;
    gap: 10px;
}

.info-item {
    display: flex;
    gap: 9px;
    font-size: 13px;
    color: #334155;
}

.info-icon {
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
    border-radius: 10px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.book-btn {
    width: 100%;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 13px 18px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    border-radius: 15px;
    text-decoration: none;
    font-weight: 700;
    box-shadow: 0 12px 25px rgba(37,99,235,.25);
}

.book-btn:hover {
    filter: brightness(.96);
}

.locked-company .company-card-img {
    filter: grayscale(100%) brightness(65%);
}

.not-available-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15,23,42,.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}

.not-available-banner {
    background: #ef4444;
    color: white;
    width: 125%;
    text-align: center;
    padding: 10px 0;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    transform: rotate(-13deg);
    box-shadow: 0 12px 25px rgba(0,0,0,.25);
}

.btn-not-available {
    width: 100%;
    display: block;
    padding: 13px 18px;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 15px;
    text-decoration: none;
    font-weight: 700;
    cursor: not-allowed;
    text-align: center;
}

.empty-message {
    background: white;
    padding: 28px;
    border-radius: 22px;
    color: #64748b;
    box-shadow: 0 15px 35px rgba(15,23,42,.07);
}

.no-result {
    display: none;
    grid-column: 1 / -1;
    background: white;
    border-radius: 22px;
    padding: 30px;
    text-align: center;
    color: #64748b;
}

@media (max-width: 768px) {
    .customer-container {
        padding: 24px 18px;
    }

    .hero {
        padding: 32px 26px;
        border-radius: 24px;
    }

    .hero h1 {
        font-size: 31px;
    }

    .stats-row {
        grid-template-columns: 1fr;
    }

    .search-box {
        width: 100%;
    }

    .browse-tools {
        width: 100%;
    }

    .sort-box {
        width: 100%;
    }
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="customer-container">

    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">✨ Premium Event Booking System</div>
            <h1>Find the perfect event company for your dream occasion.</h1>
            <p>Browse trusted event companies, compare their services and start your booking smoothly in one professional dashboard.</p>
        </div>
    </section>

    <section class="stats-row">
        <div class="stat-card">
            <span>Active Companies</span>
            <h3><?= $totalCompanies ?></h3>
        </div>
        <div class="stat-card">
            <span>Booking Process</span>
            <h3>Easy</h3>
        </div>
        <div class="stat-card">
            <span>Service Category</span>
            <h3>Event</h3>
        </div>
    </section>

    <div class="browse-header">
        <div>
            <h2>Browse Companies</h2>
            <p class="muted">Choose your preferred company before selecting date, package and vendor services.</p>
        </div>

        <div class="browse-tools">
            <input type="text" id="searchInput" class="search-box" placeholder="Search company name...">
            <select id="sortSelect" class="sort-box">
                <option value="az">Sort A - Z</option>
                <option value="za">Sort Z - A</option>
            </select>
        </div>
    </div>

    <?php if ($totalCompanies == 0) { ?>
        <div class="empty-message">No companies available right now.</div>
    <?php } ?>

    <div class="company-grid" id="companyGrid">

        <div class="no-result" id="noResult">
            No company found. Try another keyword.
        </div>

        <?php 
        mysqli_data_seek($companies, 0);
        while($company = mysqli_fetch_assoc($companies)) { 

            $is_not_available = false;

            if (isset($company['plan']) && strtolower($company['plan']) == 'basic' && $company['total_active_bookings'] >= 1) {
                $is_not_available = true;
            }

        ?>

        <div class="company-card <?= $is_not_available ? 'locked-company' : '' ?>" data-name="<?= strtolower(htmlspecialchars($company['company_name'])) ?>">

            <div class="company-image-container">

                <?php if ($is_not_available) { ?>
                    <div class="not-available-overlay">
                        <div class="not-available-banner">Not Available</div>
                    </div>
                <?php } ?>

                <?php if (!empty($company['logo'])) { ?>
                    <img src="../uploads/companies/<?= htmlspecialchars($company['logo']) ?>" class="company-card-img" alt="Company Image">
                <?php } else { ?>
                    <div class="company-placeholder">
                        <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
                    </div>
                <?php } ?>

                <div class="image-gradient"></div>
            </div>

            <div class="company-card-body">
                <h3 class="company-name"><?= htmlspecialchars($company['company_name']) ?></h3>

                <p class="desc">
                    <?= !empty($company['description']) 
                        ? htmlspecialchars($company['description']) 
                        : 'No description available for this company.' ?>
                </p>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon">📍</div>
                        <div>
                            <strong>Address:</strong><br>
                            <?= !empty($company['address']) ? htmlspecialchars($company['address']) : 'Not provided' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div>
                            <strong>Phone:</strong><br>
                            <?= !empty($company['phone']) ? htmlspecialchars($company['phone']) : 'Not provided' ?>
                        </div>
                    </div>
                </div>

                <?php if ($is_not_available) { ?>
                    <a href="javascript:void(0);" class="btn-not-available">🚫 Not Available</a>
                <?php } else { ?>
                    <a href="company_details.php?company_id=<?= $company['id'] ?>" class="book-btn">
                        View Details →
                    </a>
                <?php } ?>
            </div>
        </div>

        <?php } ?>

    </div>
</div>

<script>
const searchInput = document.getElementById("searchInput");
const sortSelect = document.getElementById("sortSelect");
const companyGrid = document.getElementById("companyGrid");
const noResult = document.getElementById("noResult");

function filterCompanies() {
    const searchValue = searchInput.value.toLowerCase();
    const cards = document.querySelectorAll(".company-card");
    let visibleCount = 0;

    cards.forEach(card => {
        const name = card.dataset.name;

        if (name.includes(searchValue)) {
            card.style.display = "block";
            visibleCount++;
        } else {
            card.style.display = "none";
        }
    });

    noResult.style.display = visibleCount === 0 ? "block" : "none";
}

function sortCompanies() {
    const cards = Array.from(document.querySelectorAll(".company-card"));
    const sortValue = sortSelect.value;

    cards.sort((a, b) => {
        const nameA = a.dataset.name;
        const nameB = b.dataset.name;
        return sortValue === "az" 
            ? nameA.localeCompare(nameB) 
            : nameB.localeCompare(nameA);
    });

    cards.forEach(card => companyGrid.appendChild(card));
    companyGrid.appendChild(noResult);
    filterCompanies();
}

searchInput.addEventListener("keyup", filterCompanies);
sortSelect.addEventListener("change", sortCompanies);
</script>

</body>
</html>