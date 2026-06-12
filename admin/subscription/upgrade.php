<?php 
session_start();
include('../../config/db.php');
include('../header.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* AMBIL PELAN SEMASA SYARIKAT DARIPADA DATABASE */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$current_plan = strtolower($data['plan'] ?? 'basic'); // tukar ke huruf kecil untuk mengelakkan ralat ejaan


/* LOGIK DINAMIK: KESAN PENGGUNA DATANG DARI DASHBOARD ATAU CATEGORIES */
$previous_page = $_SERVER['HTTP_REFERER'] ?? '';
$back_url = "../categories/add.php"; // Tetapkan sebagai default (fallback)
$back_text = "← Return to Category Setup";

// Jika dalam URL sebelum ini ada perkataan 'dashboard' atau 'index.php' utama (bergantung kepada nama fail dashboard awak)
if (stripos($previous_page, 'dashboard') !== false || stripos($previous_page, 'home') !== false) {
    $back_url = "../dashboard/index.php"; // Sila suaikan laluan (path) ke fail dashboard utama awak jika perlu (contoh: "../index.php")
    $back_text = "← Return to Dashboard";
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.upgrade-page {
    min-height: 100vh;
    padding: 45px;
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pricing-header {
    text-align: center;
    max-width: 600px;
    margin-bottom: 50px;
}

.pricing-header h1 {
    font-size: 2.2rem;
    color: #0b1329;
    margin-bottom: 10px;
    font-weight: 800;
}

.pricing-header p {
    color: #64748b;
    font-size: 1rem;
    margin: 0;
}

.pricing-grid {
    display: flex;
    justify-content: center;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 30px;
    max-width: 1200px;
    width: 100%;
}

.pricing-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 40px 30px;
    width: 300px;
    display: flex;
    flex-direction: column;
    position: relative;
    box-shadow: 0 15px 35px rgba(15,23,42,.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 45px rgba(15,23,42,.08);
}

.pricing-card.popular {
    border: 2px solid #8b5cf6;
}

.pricing-card.current {
    border: 2px dashed #10b981; /* Border hijau dash jika ini plan aktif dia */
    background: #fdfdfd;
}

.popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: #8b5cf6;
    color: white;
    padding: 4px 18px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.current-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: #10b981;
    color: white;
    padding: 4px 18px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.plan-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0b1329;
    margin: 0 0 4px 0;
}

.plan-subtitle {
    color: #94a3b8;
    font-size: 13px;
    margin: 0 0 30px 0;
}

.plan-price {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0b1329;
    margin-bottom: 35px;
    display: flex;
    align-items: baseline;
}

.plan-price span {
    font-size: 14px;
    color: #94a3b8;
    font-weight: 400;
    margin-left: 6px;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 35px 0;
    flex-grow: 1;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    font-size: 14px;
    color: #334155;
}

.features-list li i.fa-circle-check {
    color: #8b5cf6;
    font-size: 14px;
}

.features-list li.disabled {
    color: #cbd5e1;
}

.features-list li i.fa-circle-xmark {
    color: #cbd5e1;
    font-size: 14px;
}

.contact-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    text-align: center;
}

/* GAYA: Butang Upgrade Utama (Warna Ungu Gradient Yang Menyerlah) */
.btn-upgrade-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9) !important;
    color: white !important;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s ease;
    border: none !important;
    box-shadow: 0 8px 20px rgba(109, 40, 217, 0.3);
}
.btn-upgrade-action:hover {
    background: linear-gradient(135deg, #6d28d9, #5b21b6) !important;
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(109, 40, 217, 0.4);
}

/* Tambahan Gaya Butang Renew (Hijau) */
.btn-renew-active {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background-color: #10b981 !important;
    color: white !important;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s ease;
    border: none !important;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
}
.btn-renew-active:hover {
    background-color: #059669 !important;
}

/* Tambahan Gaya Butang Downgrade (Jingga) */
.btn-downgrade-orange {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background-color: #f59e0b !important;
    color: white !important;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s ease;
    border: none !important;
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
}
.btn-downgrade-orange:hover {
    background-color: #d97706 !important;
}

/* Gaya Butang Disekat (Disabled) */
.btn-disabled {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background-color: #e2e8f0 !important;
    color: #94a3b8 !important;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid #cbd5e1 !important;
    cursor: not-allowed;
    pointer-events: none;
}

/* Notifikasi khusus untuk pelan Basic yang tidak boleh di-renew */
.basic-notice {
    font-size: 13px;
    color: #ef4444;
    background-color: #fef2f2;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #fee2e2;
    font-weight: 600;
}

.email-link {
    font-size: 13px;
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.email-link:hover {
    color: #8b5cf6;
    text-decoration: underline;
}

.email-link i {
    margin-right: 4px;
}

/* Gaya untuk link kembali */
.back-to-setup {
    margin-top: 40px;
    text-decoration: none;
    color: #2563eb;
    font-weight: 800;
    font-size: 15px;
    transition: color 0.2s;
}
.back-to-setup:hover {
    color: #1d4ed8;
    text-decoration: underline;
}
</style>

<div class="upgrade-page">

    <div class="pricing-header">
        <h1>SaaS Subscription Plans</h1>
        <p>Your current active subscription is <strong><?= ucfirst($current_plan) ?> Plan</strong>. Review tier limits or coordinate with administrative staff to alter workspace levels.</p>
    </div>

    <div class="pricing-grid">

        <!-- ================= KAD BASIC ================= -->
        <div class="pricing-card <?= ($current_plan == 'basic') ? 'current' : '' ?>">
            <?php if ($current_plan == 'basic') { echo '<div class="current-badge">Your Active Plan</div>'; } ?>
            <h3 class="plan-title">Basic Access</h3>
            <p class="plan-subtitle">Perfect choice for getting started</p>
            <div class="plan-price">RM0<span>/ month</span></div>
            
            <ul class="features-list">
                <li><i class="fa-solid fa-circle-check"></i> 1 Category Max</li>
                <li><i class="fa-solid fa-circle-check"></i> 1 Package per Category</li>
                <li><i class="fa-solid fa-circle-check"></i> 2 Services/Vendors Limit</li>
                <li class="disabled"><i class="fa-solid fa-circle-xmark"></i> 1 Addon per Booking</li>
                <li class="disabled"><i class="fa-solid fa-circle-xmark"></i> 1 Booking Limit Only</li>
            </ul>

            <div class="contact-actions">
                <?php if ($current_plan == 'basic') { ?>
                    <div class="basic-notice">
                        <i class="fa-solid fa-circle-exclamation"></i> Renewal not available. Please upgrade to Standard or Premium to continue.
                    </div>
                    <a href="mailto:wannazreen232@gmail.com?subject=Inquiry%20-%20Upgrade%20from%20Basic%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Contact admin via email
                    </a>
                <?php } else { ?>
                    <a href="#" class="btn-disabled">
                        <i class="fa-solid fa-lock"></i> Downgrade Locked
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- ================= KAD STANDARD ================= -->
        <div class="pricing-card <?= ($current_plan == 'standard') ? 'current' : 'popular' ?>">
            <?php 
            if ($current_plan == 'standard') { 
                echo '<div class="current-badge">Your Active Plan</div>'; 
            } else {
                echo '<div class="popular-badge">Most Popular</div>';
            }
            ?>
            <h3 class="plan-title">Standard Access</h3>
            <p class="plan-subtitle">Built specifically for scale businesses</p>
            <div class="plan-price">RM80<span>/ month</span></div>
            
            <ul class="features-list">
                <li><i class="fa-solid fa-circle-check"></i> 2 Categories Max</li>
                <li><i class="fa-solid fa-circle-check"></i> 2 Packages per Category</li>
                <li><i class="fa-solid fa-circle-check"></i> 5 Services/Vendors Limit</li>
                <li><i class="fa-solid fa-circle-check"></i> 3 Addons per Booking</li>
                <li><i class="fa-solid fa-circle-check"></i> 10 Bookings History Access</li>
            </ul>

            <div class="contact-actions">
                <?php if ($current_plan == 'standard') { ?>
                    <!-- Jika sedang aktif Standard, boleh Renew -->
                    <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20renew%20my%20Standard%20Access%20Plan." target="_blank" class="btn-renew-active">
                        <i class="fa-solid fa-rotate"></i> Renew Current Plan
                    </a>
                    <a href="mailto:wannazreen232@gmail.com?subject=Renew%20Request%20-%20Standard%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Or email admin
                    </a>
                <?php } elseif ($current_plan == 'premium') { ?>
                    <!-- Jika dia Premium, butang Standard bertukar jadi DOWNGRADE -->
                    <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20downgrade%20from%20Premium%20to%20the%20Standard%20Access%20Plan." target="_blank" class="btn-downgrade-orange">
                        <i class="fa-solid fa-arrow-down-wide-short"></i> Downgrade to Standard
                    </a>
                    <a href="mailto:wannazreen232@gmail.com?subject=Downgrade%20Request%20-%20Standard%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Or email admin
                    </a>
                <?php } else { ?>
                    <!-- Jika dia Basic, butang Upgrade bertukar jadi Ungu Premium -->
                    <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20upgrade%20from%20Basic%20to%20the%20Standard%20Access%20Plan." target="_blank" class="btn-upgrade-action">
                        <i class="fa-solid fa-crown"></i> Upgrade to Standard
                    </a>
                    <a href="mailto:wannazreen232@gmail.com?subject=Upgrade%20Request%20-%20Standard%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Or email admin
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- ================= KAD PREMIUM ================= -->
        <div class="pricing-card <?= ($current_plan == 'premium') ? 'current' : '' ?>">
            <?php if ($current_plan == 'premium') { echo '<div class="current-badge">Your Active Plan</div>'; } ?>
            <h3 class="plan-title">Premium Access</h3>
            <p class="plan-subtitle">Ultimate enterprise capabilities</p>
            <div class="plan-price">RM150<span>/ month</span></div>
            
            <ul class="features-list">
                <li><i class="fa-solid fa-circle-check"></i> Unlimited Categories</li>
                <li><i class="fa-solid fa-circle-check"></i> Unlimited Packages</li>
                <li><i class="fa-solid fa-circle-check"></i> 15 Services/Vendors Limit</li>
                <li><i class="fa-solid fa-circle-check"></i> 5 Addons per Booking</li>
                <li><i class="fa-solid fa-circle-check"></i> Unlimited Active Bookings</li>
            </ul>

            <div class="contact-actions">
                <?php if ($current_plan == 'premium') { ?>
                    <!-- Jika sedang aktif Premium, boleh Renew -->
                    <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20renew%20my%20Premium%20Access%20Plan." target="_blank" class="btn-renew-active">
                        <i class="fa-solid fa-rotate"></i> Renew Current Plan
                    </a>
                    <a href="mailto:wannazreen232@gmail.com?subject=Renew%20Request%20-%20Premium%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Or email admin
                    </a>
                <?php } else { ?>
                    <!-- Jika dia Basic atau Standard, butang Upgrade bertukar jadi Ungu Premium -->
                    <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20upgrade%20from%20<?= ucfirst($current_plan) ?>%20to%20the%20Premium%20Access%20Plan." target="_blank" class="btn-upgrade-action">
                        <i class="fa-solid fa-crown"></i> Upgrade to Premium
                    </a>
                    <a href="mailto:wannazreen232@gmail.com?subject=Upgrade%20Request%20-%20Premium%20Access" class="email-link">
                        <i class="fa-solid fa-envelope"></i> Or email admin
                    </a>
                <?php } ?>
            </div>
        </div>

    </div>

    <!-- PENGUBAHSUAIAN BIJAK: Menggunakan JavaScript untuk undur balik ke halaman asal tanpa ralat 404 -->
    <?php
    // Tetapkan teks butang secara dinamik berdasarkan halaman rujukan sebelum ini
    $previous_page = $_SERVER['HTTP_REFERER'] ?? '';
    $back_text = "← Return to Category Setup";

    if (stripos($previous_page, 'dashboard') !== false || stripos($previous_page, 'home') !== false || stripos($previous_page, 'index.php') !== false) {
        $back_text = "← Return to Dashboard";
    }
    ?>
    <a href="javascript:void(0);" onclick="window.history.back();" class="back-to-setup"><?= $back_text ?></a>

</div>

<?php include('../footer.php'); ?>