<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

/* FETCH ACTIVE COMPANIES BESERTA JUMLAH BOOKING AKTIF */
$companies = mysqli_query($conn, "
    SELECT c.*, 
           (SELECT COUNT(*) FROM bookings b WHERE b.company_id = c.id AND b.status != 'Cancelled') AS total_active_bookings
    FROM companies c
    WHERE c.subscription_status = 'Active'
    ORDER BY c.company_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            color: #222;
        }

        .customer-container {
            padding: 40px 60px;
        }

        .hero {
            background: linear-gradient(135deg, #1677ff, #4dabff);
            color: white;
            padding: 38px;
            border-radius: 22px;
            margin-bottom: 30px;
        }

        .hero h1 { margin: 0 0 10px; font-size: 34px; }
        .hero p { margin: 0; opacity: 0.95; }

        .browse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .browse-header h2 { margin: 0; font-size: 25px; }

        .browse-tools {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .browse-tools input, .browse-tools select {
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }
        .browse-tools input { width: 260px; }

        .company-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .company-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: 0.25s ease;
        }

        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px rgba(0,0,0,0.12);
        }

        /* ─── BAHAGIAN GAMBAR & PALANG OVERLAY ─── */
        .company-image-container {
            position: relative;
            width: 100%;
            height: 190px;
            overflow: hidden;
            background: #1677ff;
        }

        .company-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        /* Efek hitam-putih & pudar bila syarikat ditutup */
        .company-card.locked-company .company-card-img {
            filter: grayscale(100%) brightness(70%);
        }
        .company-card.locked-company .company-placeholder {
            background: #64748b;
        }

        /* Palang "Not Available" Merentasi Gambar */
        .not-available-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55); /* Gelapkan sikit background belakang palang */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .not-available-banner {
            background: #ef4444; /* Warna merah menyala macam Shopee/Lazada */
            color: white;
            width: 120%;
            text-align: center;
            padding: 8px 0;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transform: rotate(-12px); /* Sengetkan palang sikit supaya nampak stail */
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .company-placeholder {
            height: 100%;
            color: white;
            font-size: 56px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ─── BAHAGIAN KANDUNGAN KAD ─── */
        .company-card-body { padding: 24px; }
        .company-card-body h3 { margin: 0 0 8px; font-size: 21px; }

        .company-plan {
            color: #1677ff;
            font-weight: 500;
            margin-bottom: 12px;
        }
        .company-card.locked-company .company-plan {
            color: #64748b;
        }

        .company-card-body p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        /* Butang Biru Asal */
        .book-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 18px;
            background: #1677ff;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
        }
        .book-btn:hover { background: #0f5ed7; }

        /* Butang Kelabu Mati */
        .btn-not-available {
            display: block;
            margin-top: 12px;
            padding: 12px 18px;
            background: #cbd5e1;
            color: #64748b;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            cursor: not-allowed;
            text-align: center;
            border: 1px solid #94a3b8;
        }

        .empty-message {
            background: white;
            padding: 25px;
            border-radius: 14px;
            color: #666;
        }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<div class="customer-container">

    <div class="hero">
        <h1>Find the best event company</h1>
        <p>Browse companies, compare services and start your event booking.</p>
    </div>

    <div class="browse-header">
        <div>
            <h2>Browse Companies</h2>
            <p class="muted">Choose a company before selecting date, package and vendor services.</p>
        </div>

        <div class="browse-tools">
            <input type="text" id="searchInput" placeholder="Search company...">
            <select id="sortSelect">
                <option value="az">Sort A - Z</option>
                <option value="za">Sort Z - A</option>
            </select>
        </div>
    </div>

    <?php if (mysqli_num_rows($companies) == 0) { ?>
        <div class="empty-message">No companies available right now.</div>
    <?php } ?>

    <div class="company-grid" id="companyGrid">

        <?php while($company = mysqli_fetch_assoc($companies)) { 
            
            // LOGIK SEMAKAN UTAMA
            $is_not_available = false;
            if (strtolower($company['plan']) == 'basic' && $company['total_active_bookings'] >= 1) {
                $is_not_available = true;
            }
        ?>

            <div class="company-card <?= $is_not_available ? 'locked-company' : '' ?>" data-name="<?= strtolower($company['company_name']) ?>">

                <div class="company-image-container">
                    
                    <?php if ($is_not_available) { ?>
                        <div class="not-available-overlay">
                            <div class="not-available-banner">Not Available</div>
                        </div>
                    <?php } ?>

                    <?php if (!empty($company['logo'])) { ?>
                        <img src="../uploads/companies/<?= $company['logo'] ?>" class="company-card-img" alt="Company Image">
                    <?php } else { ?>
                        <div class="company-placeholder">
                            <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="company-card-body">

                    <h3 class="company-name"><?= htmlspecialchars($company['company_name']) ?></h3>



                    <p><?= !empty($company['description']) ? htmlspecialchars($company['description']) : 'No description available.' ?></p>
                    <p><strong>Address:</strong> <?= !empty($company['address']) ? htmlspecialchars($company['address']) : 'Not provided' ?></p>
                    <p><strong>Phone:</strong> <?= !empty($company['phone']) ? htmlspecialchars($company['phone']) : 'Not provided' ?></p>

                    <?php if ($is_not_available) { ?>
                        <a href="javascript:void(0);" class="btn-not-available">
                            🚫 Not Available
                        </a>
                    <?php } else { ?>
                        <a href="company_details.php?company_id=<?= $company['id'] ?>" class="book-btn">
                            View Details
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

function filterCompanies() {
    const searchValue = searchInput.value.toLowerCase();
    const cards = document.querySelectorAll(".company-card");

    cards.forEach(card => {
        const name = card.dataset.name;
        if (name.includes(searchValue)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

function sortCompanies() {
    const cards = Array.from(document.querySelectorAll(".company-card"));
    const sortValue = sortSelect.value;

    cards.sort((a, b) => {
        const nameA = a.dataset.name;
        const nameB = b.dataset.name;
        return sortValue === "az" ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
    });

    cards.forEach(card => companyGrid.appendChild(card));
}

searchInput.addEventListener("keyup", filterCompanies);
sortSelect.addEventListener("change", sortCompanies);
</script>

</body>
</html>