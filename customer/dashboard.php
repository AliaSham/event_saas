<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

/* FETCH ACTIVE COMPANIES */
$companies = mysqli_query($conn, "
    SELECT * FROM companies
    WHERE subscription_status = 'Active'
    ORDER BY company_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
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

        .customer-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .customer-navbar .brand {
            font-size: 20px;
            font-weight: 600;
            color: #1677ff;
        }

        .customer-navbar a {
            text-decoration: none;
            color: #333;
            margin-left: 22px;
            font-weight: 500;
        }

        .customer-navbar a:hover {
            color: #1677ff;
        }

        .logout {
            color: #ff4d4f !important;
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

        .hero h1 {
            margin: 0 0 10px;
            font-size: 34px;
        }

        .hero p {
            margin: 0;
            opacity: 0.95;
        }

        .browse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .browse-header h2 {
            margin: 0;
            font-size: 25px;
        }

        .browse-tools {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .browse-tools input,
        .browse-tools select {
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .browse-tools input {
            width: 260px;
        }

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

        .company-card-img {
            width: 100%;
            height: 190px;
            object-fit: cover;
        }

        .company-placeholder {
            height: 190px;
            background: #1677ff;
            color: white;
            font-size: 56px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-card-body {
            padding: 24px;
        }

        .company-card-body h3 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .company-plan {
            color: #1677ff;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .company-card-body p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 10px;
        }

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

        .book-btn:hover {
            background: #0f5ed7;
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

<div class="customer-navbar">
    <div class="brand">
        Event Booking
    </div>

    <div>
        <a href="dashboard.php">Browse</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="account.php">My Account</a>
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</div>

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
        <div class="empty-message">
            No companies available right now.
        </div>
    <?php } ?>

    <div class="company-grid" id="companyGrid">

        <?php while($company = mysqli_fetch_assoc($companies)) { ?>

            <div class="company-card" data-name="<?= strtolower($company['company_name']) ?>">

                <?php if (!empty($company['logo'])) { ?>
                    <img src="../uploads/companies/<?= $company['logo'] ?>" 
                         class="company-card-img"
                         alt="Company Image">
                <?php } else { ?>
                    <div class="company-placeholder">
                        <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
                    </div>
                <?php } ?>

                <div class="company-card-body">

                    <h3 class="company-name"><?= $company['company_name'] ?></h3>

                    <div class="company-plan">
                        <?= ucfirst($company['plan']) ?> Plan
                    </div>

                    <p>
                        <?= !empty($company['description']) ? $company['description'] : 'No description available.' ?>
                    </p>

                    <p>
                        <strong>Address:</strong>
                        <?= !empty($company['address']) ? $company['address'] : 'Not provided' ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?= !empty($company['phone']) ? $company['phone'] : 'Not provided' ?>
                    </p>

                    <a href="company_details.php?company_id=<?= $company['id'] ?>" 
                    class="book-btn">
                        View Details
                    </a>

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

        if (sortValue === "az") {
            return nameA.localeCompare(nameB);
        } else {
            return nameB.localeCompare(nameA);
        }
    });

    cards.forEach(card => companyGrid.appendChild(card));
}

searchInput.addEventListener("keyup", filterCompanies);
sortSelect.addEventListener("change", sortCompanies);
</script>

</body>
</html>