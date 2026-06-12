<?php 
session_start();
include('../../config/db.php');
include('../header.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$current_plan = strtolower($data['plan'] ?? 'basic');

$previous_page = $_SERVER['HTTP_REFERER'] ?? '';
$back_text = "← Return to Category Setup";

if (
    stripos($previous_page, 'dashboard') !== false || 
    stripos($previous_page, 'home') !== false || 
    stripos($previous_page, 'index.php') !== false
) {
    $back_text = "← Return to Dashboard";
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

.upgrade-page {
    min-height: 100vh;
    width: 100%;
    padding: 38px 42px 55px;
    background:
        radial-gradient(circle at 15% 10%, rgba(16,185,129,.28), transparent 26%),
        radial-gradient(circle at 85% 20%, rgba(139,92,246,.30), transparent 28%),
        radial-gradient(circle at 50% 100%, rgba(14,165,233,.18), transparent 30%),
        linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #f0fdfa 100%);
    position: relative;
    overflow: hidden;
}

.upgrade-page::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(15,23,42,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15,23,42,.04) 1px, transparent 1px);
    background-size: 38px 38px;
    mask-image: linear-gradient(to bottom, black, transparent 85%);
    pointer-events: none;
}

.upgrade-shell {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.pricing-hero {
    display: grid;
    grid-template-columns: 1.3fr .7fr;
    gap: 26px;
    align-items: stretch;
    margin-bottom: 28px;
}

.hero-main {
    border-radius: 34px;
    padding: 42px;
    color: white;
    background:
        linear-gradient(135deg, rgba(2,6,23,.96), rgba(15,23,42,.94)),
        radial-gradient(circle at top right, rgba(124,58,237,.8), transparent 38%);
    box-shadow: 0 30px 80px rgba(15,23,42,.22);
    position: relative;
    overflow: hidden;
}

.hero-main::after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    right: -70px;
    top: -80px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(16,185,129,.36), rgba(139,92,246,.30));
    filter: blur(2px);
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.22);
    color: #d1fae5;
    padding: 9px 16px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .5px;
    margin-bottom: 20px;
    position: relative;
    z-index: 2;
}

.hero-main h1 {
    font-size: 3rem;
    line-height: 1.05;
    margin: 0 0 16px;
    font-weight: 950;
    letter-spacing: -1.4px;
    position: relative;
    z-index: 2;
}

.hero-main p {
    max-width: 650px;
    color: #cbd5e1;
    font-size: 15px;
    line-height: 1.8;
    margin: 0;
    position: relative;
    z-index: 2;
}

.plan-highlight {
    color: #34d399;
    font-weight: 950;
}

.hero-side {
    border-radius: 34px;
    padding: 28px;
    background: rgba(255,255,255,.82);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.85);
    box-shadow: 0 24px 60px rgba(15,23,42,.10);
}

.status-card {
    height: 100%;
    border-radius: 26px;
    padding: 24px;
    background: linear-gradient(135deg, #ecfeff, #f5f3ff);
    border: 1px solid #e0e7ff;
}

.status-card .status-icon {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #10b981, #7c3aed);
    color: white;
    font-size: 22px;
    box-shadow: 0 16px 34px rgba(124,58,237,.25);
    margin-bottom: 18px;
}

.status-card h3 {
    margin: 0 0 8px;
    color: #020617;
    font-size: 20px;
    font-weight: 950;
}

.status-card p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 30px;
}

.stat-box {
    padding: 18px 20px;
    border-radius: 22px;
    background: rgba(255,255,255,.78);
    border: 1px solid rgba(226,232,240,.9);
    box-shadow: 0 16px 40px rgba(15,23,42,.06);
}

.stat-box strong {
    display: block;
    font-size: 22px;
    color: #020617;
    font-weight: 950;
    margin-bottom: 4px;
}

.stat-box span {
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
    width: 100%;
}

.pricing-card {
    min-height: 535px;
    padding: 30px 28px;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(226, 232, 240, 0.95);
    box-shadow: 0 20px 55px rgba(15, 23, 42, 0.09);
    backdrop-filter: blur(16px);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    transition: all .35s ease;
}

.pricing-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.30), transparent);
    pointer-events: none;
}

.pricing-card::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: -86px;
    top: -82px;
    background: rgba(124, 58, 237, 0.10);
    border-radius: 50%;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 34px 85px rgba(15, 23, 42, 0.16);
}

.pricing-card.current {
    border: 2px solid #10b981;
    box-shadow: 0 28px 80px rgba(16, 185, 129, 0.24);
}

.pricing-card.popular {
    border: 2px solid #7c3aed;
}

.premium-card {
    background:
        linear-gradient(180deg, rgba(255,255,255,.94), rgba(245,243,255,.92));
}

.badge {
    position: absolute;
    top: 18px;
    right: 18px;
    padding: 8px 13px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: .45px;
    z-index: 2;
}

.current-badge {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 12px 26px rgba(16,185,129,.28);
}

.popular-badge {
    background: linear-gradient(135deg, #7c3aed, #4c1d95);
    color: white;
    box-shadow: 0 12px 26px rgba(124,58,237,.30);
}

.best-badge {
    background: linear-gradient(135deg, #f97316, #ef4444);
    color: white;
    box-shadow: 0 12px 26px rgba(249,115,22,.30);
}

.plan-icon {
    width: 56px;
    height: 56px;
    border-radius: 19px;
    display: grid;
    place-items: center;
    margin-bottom: 22px;
    font-size: 22px;
    color: white;
    box-shadow: 0 16px 30px rgba(15,23,42,.16);
    position: relative;
    z-index: 2;
}

.icon-basic {
    background: linear-gradient(135deg, #475569, #0f172a);
}

.icon-standard {
    background: linear-gradient(135deg, #10b981, #047857);
}

.icon-premium {
    background: linear-gradient(135deg, #8b5cf6, #4c1d95);
}

.plan-title {
    font-size: 1.45rem;
    font-weight: 950;
    color: #020617;
    margin: 0 0 7px;
    position: relative;
    z-index: 2;
}

.plan-subtitle {
    color: #64748b;
    font-size: 13px;
    margin: 0 0 26px;
    line-height: 1.5;
    position: relative;
    z-index: 2;
}

.plan-price {
    font-size: 2.55rem;
    font-weight: 950;
    color: #020617;
    margin-bottom: 26px;
    display: flex;
    align-items: baseline;
    position: relative;
    z-index: 2;
}

.plan-price span {
    font-size: 13px;
    color: #64748b;
    font-weight: 700;
    margin-left: 7px;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 28px;
    flex-grow: 1;
    position: relative;
    z-index: 2;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 14px;
    font-size: 13px;
    color: #334155;
    font-weight: 750;
}

.features-list li i.fa-circle-check {
    color: #7c3aed;
}

.features-list li.disabled {
    color: #cbd5e1;
}

.features-list li i.fa-circle-xmark {
    color: #cbd5e1;
}

.contact-actions {
    display: flex;
    flex-direction: column;
    gap: 11px;
    text-align: center;
    position: relative;
    z-index: 2;
}

.btn-upgrade-action,
.btn-renew-active,
.btn-downgrade-orange,
.btn-disabled {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 15px 16px;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 950;
    font-size: 13px;
    transition: all .25s ease;
}

.btn-upgrade-action {
    background: linear-gradient(135deg, #8b5cf6, #6d28d9, #4c1d95) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 16px 36px rgba(109,40,217,.35);
}

.btn-upgrade-action:hover,
.btn-renew-active:hover,
.btn-downgrade-orange:hover {
    transform: translateY(-3px);
}

.btn-renew-active {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 16px 36px rgba(16,185,129,.30);
}

.btn-downgrade-orange {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: white !important;
    border: none !important;
}

.btn-disabled {
    background: #e2e8f0 !important;
    color: #94a3b8 !important;
    border: 1px solid #cbd5e1 !important;
    cursor: not-allowed;
    pointer-events: none;
}

.basic-notice {
    font-size: 12px;
    color: #dc2626;
    background: #fff1f2;
    padding: 12px;
    border-radius: 14px;
    border: 1px solid #fecdd3;
    font-weight: 850;
    line-height: 1.5;
}

.email-link {
    font-size: 12px;
    color: #475569;
    text-decoration: none;
    font-weight: 800;
}

.email-link:hover {
    color: #7c3aed;
    text-decoration: underline;
}

.back-to-setup {
    width: fit-content;
    margin: 34px auto 0;
    display: flex;
    text-decoration: none;
    color: #0f172a;
    font-weight: 950;
    font-size: 13px;
    background: white;
    padding: 13px 22px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 16px 35px rgba(15,23,42,.09);
    transition: all .25s ease;
}

.back-to-setup:hover {
    transform: translateY(-2px);
    color: #7c3aed;
}

@media (max-width: 1050px) {
    .pricing-hero {
        grid-template-columns: 1fr;
    }

    .pricing-grid {
        grid-template-columns: 1fr;
    }

    .pricing-card {
        max-width: 460px;
        width: 100%;
        margin: 0 auto;
    }

    .quick-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .upgrade-page {
        padding: 26px 16px 40px;
    }

    .hero-main {
        padding: 30px 24px;
    }

    .hero-main h1 {
        font-size: 2.15rem;
    }

    .pricing-card {
        padding: 28px 24px;
    }
}
</style>

<div class="upgrade-page">
    <div class="upgrade-shell">

        <div class="pricing-hero">
            <div class="hero-main">
                <div class="hero-pill">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    EVENTPRO SAAS SUBSCRIPTION
                </div>

                <h1>Upgrade Your Event Business Like a Pro</h1>

                <p>
                    Your current active subscription is 
                    <span class="plan-highlight"><?= ucfirst($current_plan) ?> Plan</span>. 
                    Unlock more categories, packages, vendors, addons and booking access to manage your event business smoothly.
                </p>
            </div>

            <div class="hero-side">
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3><?= ucfirst($current_plan) ?> Plan Active</h3>
                    <p>Manage your subscription level and contact admin directly through WhatsApp or email.</p>
                </div>
            </div>
        </div>

        <div class="quick-stats">
            <div class="stat-box">
                <strong>3</strong>
                <span>Plan Options</span>
            </div>
            <div class="stat-box">
                <strong>RM80</strong>
                <span>Standard Plan</span>
            </div>
            <div class="stat-box">
                <strong>∞</strong>
                <span>Premium Categories</span>
            </div>
        </div>

        <div class="pricing-grid">

            <div class="pricing-card <?= ($current_plan == 'basic') ? 'current' : '' ?>">
                <?php if ($current_plan == 'basic') { ?>
                    <div class="badge current-badge">Active Plan</div>
                <?php } ?>

                <div class="plan-icon icon-basic">
                    <i class="fa-solid fa-seedling"></i>
                </div>

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
                            <i class="fa-solid fa-circle-exclamation"></i> Renewal not available. Please upgrade to continue.
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

            <div class="pricing-card <?= ($current_plan == 'standard') ? 'current' : 'popular' ?>">
                <?php if ($current_plan == 'standard') { ?>
                    <div class="badge current-badge">Active Plan</div>
                <?php } else { ?>
                    <div class="badge popular-badge">Most Popular</div>
                <?php } ?>

                <div class="plan-icon icon-standard">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <h3 class="plan-title">Standard Access</h3>
                <p class="plan-subtitle">Built specifically for growing businesses</p>

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

                        <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20renew%20my%20Standard%20Access%20Plan." target="_blank" class="btn-renew-active">
                            <i class="fa-solid fa-rotate"></i> Renew Current Plan
                        </a>

                        <a href="mailto:wannazreen232@gmail.com?subject=Renew%20Request%20-%20Standard%20Access" class="email-link">
                            <i class="fa-solid fa-envelope"></i> Or email admin
                        </a>

                    <?php } elseif ($current_plan == 'premium') { ?>

                        <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20downgrade%20from%20Premium%20to%20the%20Standard%20Access%20Plan." target="_blank" class="btn-downgrade-orange">
                            <i class="fa-solid fa-arrow-down-wide-short"></i> Downgrade to Standard
                        </a>

                        <a href="mailto:wannazreen232@gmail.com?subject=Downgrade%20Request%20-%20Standard%20Access" class="email-link">
                            <i class="fa-solid fa-envelope"></i> Or email admin
                        </a>

                    <?php } else { ?>

                        <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20upgrade%20from%20Basic%20to%20the%20Standard%20Access%20Plan." target="_blank" class="btn-upgrade-action">
                            <i class="fa-solid fa-crown"></i> Upgrade to Standard
                        </a>

                        <a href="mailto:wannazreen232@gmail.com?subject=Upgrade%20Request%20-%20Standard%20Access" class="email-link">
                            <i class="fa-solid fa-envelope"></i> Or email admin
                        </a>

                    <?php } ?>
                </div>
            </div>

            <div class="pricing-card premium-card <?= ($current_plan == 'premium') ? 'current' : '' ?>">
                <?php if ($current_plan == 'premium') { ?>
                    <div class="badge current-badge">Active Plan</div>
                <?php } else { ?>
                    <div class="badge best-badge">Best Value</div>
                <?php } ?>

                <div class="plan-icon icon-premium">
                    <i class="fa-solid fa-gem"></i>
                </div>

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

                        <a href="https://wa.me/601129389680?text=Hi%20Admin,%20I%20want%20to%20renew%20my%20Premium%20Access%20Plan." target="_blank" class="btn-renew-active">
                            <i class="fa-solid fa-rotate"></i> Renew Current Plan
                        </a>

                        <a href="mailto:wannazreen232@gmail.com?subject=Renew%20Request%20-%20Premium%20Access" class="email-link">
                            <i class="fa-solid fa-envelope"></i> Or email admin
                        </a>

                    <?php } else { ?>

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

        <a href="javascript:void(0);" onclick="window.history.back();" class="back-to-setup">
            <?= $back_text ?>
        </a>

    </div>
</div>

<?php include('../footer.php'); ?>