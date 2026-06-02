<?php
session_start();
include('../config/db.php');
include('../config/plan.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['company_id']) || !isset($_GET['category_id'])) {
    header("Location: dashboard.php");
    exit;
}

$company_id = $_GET['company_id'];
$category_id = $_GET['category_id'];

$error = "";
$available = false;
$event_date = "";

$packages = [];
$vendors = [];

$today_date = date('Y-m-d');

$companyQ = mysqli_query($conn, "
    SELECT * FROM companies
    WHERE id='$company_id'
    AND subscription_status='Active'
");

if (mysqli_num_rows($companyQ) == 0) {
    header("Location: dashboard.php");
    exit;
}

$company = mysqli_fetch_assoc($companyQ);

$limits = getPlanLimits($company['plan']);
$maxAddons = $limits['addons_per_booking'];

$catQ = mysqli_query($conn, "
    SELECT * FROM event_categories
    WHERE id='$category_id'
    AND company_id='$company_id'
");

if (mysqli_num_rows($catQ) == 0) {
    header("Location: company_details.php?company_id=$company_id");
    exit;
}

$category = mysqli_fetch_assoc($catQ);

function isCompanyAvailable($conn, $company_id, $event_date) {
    $calendarCheck = mysqli_query($conn, "
        SELECT * FROM company_calendar
        WHERE company_id='$company_id'
        AND event_date='$event_date'
    ");

    if (mysqli_num_rows($calendarCheck) > 0) {
        return false;
    }

    $bookingCheck = mysqli_query($conn, "
        SELECT * FROM bookings
        WHERE company_id='$company_id'
        AND event_date='$event_date'
        AND status IN ('Pending', 'Confirmed')
    ");

    if (mysqli_num_rows($bookingCheck) > 0) {
        return false;
    }

    return true;
}

function isVendorAvailable($conn, $vendor_id, $event_date) {
    $calendarCheck = mysqli_query($conn, "
        SELECT * FROM vendor_calendar
        WHERE vendor_user_id='$vendor_id'
        AND unavailable_date='$event_date'
    ");

    if (mysqli_num_rows($calendarCheck) > 0) {
        return false;
    }

    $bookingCheck = mysqli_query($conn, "
        SELECT bv.*
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id='$vendor_id'
        AND b.event_date='$event_date'
        AND b.status IN ('Pending', 'Confirmed')
    ");

    if (mysqli_num_rows($bookingCheck) > 0) {
        return false;
    }

    return true;
}

function loadBookingOptions($conn, $company_id, $category_id, $event_date, &$packages, &$vendors) {
    $packages = [];
    $vendors = [];

    $packageQ = mysqli_query($conn, "
        SELECT *
        FROM packages
        WHERE company_id='$company_id'
        AND event_category_id='$category_id'
        ORDER BY package_name ASC
    ");

    while ($p = mysqli_fetch_assoc($packageQ)) {
        $packages[] = $p;
    }

    $vendorQ = mysqli_query($conn, "
        SELECT 
            u.id AS vendor_id,
            u.name,
            vp.phone,
            vp.service_type,
            vp.price,
            vp.description,
            vp.service_details,
            vp.equipment,
            vp.image
        FROM company_vendors cv
        JOIN users u ON cv.vendor_user_id = u.id
        JOIN vendor_profiles vp ON u.id = vp.user_id
        WHERE cv.company_id='$company_id'
        AND u.role='vendor'
        AND u.approval_status='active'
        ORDER BY u.name ASC
    ");

    while ($v = mysqli_fetch_assoc($vendorQ)) {
        if (isVendorAvailable($conn, $v['vendor_id'], $event_date)) {
            $vendors[] = $v;
        }
    }
}

if (isset($_POST['check_availability'])) {
    $event_date = $_POST['event_date'];

    if ($event_date == "") {
        $error = "Please select event date.";
    } elseif ($event_date < $today_date) {
        $error = "You cannot check availability or book for a past date.";
    } elseif (!isCompanyAvailable($conn, $company_id, $event_date)) {
        $error = "This company is not available on the selected date.";
    } else {
        $available = true;
        loadBookingOptions($conn, $company_id, $category_id, $event_date, $packages, $vendors);
    }
}

if (isset($_POST['submit_booking'])) {
    $event_date = $_POST['event_date'];
    $package_id = $_POST['package_id'];
    $vendor_ids = isset($_POST['vendor_ids']) ? $_POST['vendor_ids'] : [];

    if ($event_date == "" || $package_id == "") {
        $error = "Please select date and package.";
    } elseif ($event_date < $today_date) {
        $error = "You cannot complete a booking on a past date.";
    } elseif (count($vendor_ids) > $maxAddons) {
        $error = "You can only select up to $maxAddons add-on(s).";
    } elseif (!isCompanyAvailable($conn, $company_id, $event_date)) {
        $error = "This company is not available on the selected date.";
    } else {

        $packageQ = mysqli_query($conn, "
            SELECT *
            FROM packages
            WHERE id='$package_id'
            AND company_id='$company_id'
            AND event_category_id='$category_id'
        ");

        if (mysqli_num_rows($packageQ) == 0) {
            $error = "Invalid package selected.";
        } else {
            $package = mysqli_fetch_assoc($packageQ);
            $total_price = $package['base_price'];
            $selectedVendorsData = [];

            foreach ($vendor_ids as $vendor_id) {
                $vendorQ = mysqli_query($conn, "
                    SELECT 
                        u.id AS vendor_id,
                        vp.price
                    FROM company_vendors cv
                    JOIN users u ON cv.vendor_user_id = u.id
                    JOIN vendor_profiles vp ON u.id = vp.user_id
                    WHERE cv.company_id='$company_id'
                    AND u.id='$vendor_id'
                    AND u.role='vendor'
                    AND u.approval_status='active'
                ");

                if (mysqli_num_rows($vendorQ) == 0) {
                    $error = "Invalid vendor selected.";
                    break;
                }

                if (!isVendorAvailable($conn, $vendor_id, $event_date)) {
                    $error = "One of the selected vendors is not available on this date.";
                    break;
                }

                $vendor = mysqli_fetch_assoc($vendorQ);
                $total_price += $vendor['price'];

                $selectedVendorsData[] = [
                    'vendor_id' => $vendor['vendor_id'],
                    'price' => $vendor['price']
                ];
            }

            if ($error == "") {
                mysqli_query($conn, "
                    INSERT INTO bookings
                    (user_id, package_id, event_date, total_price, status, company_id, vendor_id)
                    VALUES
                    ('$user_id', '$package_id', '$event_date', '$total_price', 'Payment Pending', '$company_id', NULL)
                ");

                $booking_id = mysqli_insert_id($conn);

                foreach ($selectedVendorsData as $vendorData) {
                    mysqli_query($conn, "
                        INSERT INTO booking_vendors
                        (booking_id, vendor_id, price)
                        VALUES
                        ('".$booking_id."', '".$vendorData['vendor_id']."', '".$vendorData['price']."')
                    ");
                }

                header("Location: payment.php?booking_id=$booking_id");
                exit;
            }
        }
    }

    if ($event_date != "" && $event_date >= $today_date && isCompanyAvailable($conn, $company_id, $event_date)) {
        $available = true;
        loadBookingOptions($conn, $company_id, $category_id, $event_date, $packages, $vendors);
    }
}

$blockedDates = [];

$closedQ = mysqli_query($conn, "
    SELECT event_date
    FROM company_calendar
    WHERE company_id='$company_id'
");

while ($row = mysqli_fetch_assoc($closedQ)) {
    $blockedDates[] = [
        'title' => 'Unavailable',
        'start' => $row['event_date'],
        'backgroundColor' => '#ef4444',
        'borderColor' => '#ef4444',
        'textColor' => '#ffffff'
    ];
}

$bookingDateQ = mysqli_query($conn, "
    SELECT event_date
    FROM bookings
    WHERE company_id='$company_id'
    AND status IN ('Pending', 'Confirmed')
");

while ($row = mysqli_fetch_assoc($bookingDateQ)) {
    $blockedDates[] = [
        'title' => 'Unavailable',
        'start' => $row['event_date'],
        'backgroundColor' => '#ef4444',
        'borderColor' => '#ef4444',
        'textColor' => '#ffffff'
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Book Event</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

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
    grid-template-columns: 340px 1fr;
    gap: 26px;
    background: white;
    border-radius: 30px;
    overflow: hidden;
    box-shadow: 0 22px 50px rgba(15,23,42,.10);
    border: 1px solid #e2e8f0;
    margin-bottom: 28px;
}

.company-img {
    height: 280px;
    width: 100%;
    object-fit: cover;
}

.company-placeholder {
    height: 280px;
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    color: white;
    font-size: 72px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.company-info {
    padding: 34px;
}

.badge {
    display: inline-block;
    padding: 8px 14px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.company-info h1 {
    margin: 0 0 10px;
    font-size: 32px;
}

.company-info p {
    color: #475569;
    line-height: 1.7;
    font-size: 14px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 18px;
}

.info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px;
}

.info-box small {
    display: block;
    color: #64748b;
    margin-bottom: 5px;
}

.info-box strong {
    font-size: 13px;
}

.booking-title {
    margin-bottom: 20px;
}

.booking-title h2 {
    margin: 0 0 6px;
    font-size: 28px;
}

.booking-title p {
    margin: 0;
    color: #64748b;
}

.alert.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 15px 18px;
    border-radius: 16px;
    margin-bottom: 22px;
    font-weight: 600;
}

.step-card {
    background: white;
    border-radius: 28px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 20px 45px rgba(15,23,42,.08);
    padding: 28px;
    margin-bottom: 26px;
}

.step-header {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 22px;
}

.step-no {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-header h3 {
    margin: 0;
    font-size: 22px;
}

.step-header p {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 13px;
}

.calendar-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 24px;
    align-items: start;
}

#customerCalendar {
    background: white;
    border-radius: 22px;
    overflow: hidden;
}

.fc .fc-toolbar-title {
    font-size: 22px;
}

.fc .fc-button-primary {
    background: #2563eb;
    border-color: #2563eb;
    border-radius: 10px;
}

.side-panel {
    background: linear-gradient(135deg, #f8fafc, #eff6ff);
    border: 1px solid #dbeafe;
    border-radius: 22px;
    padding: 20px;
}

.side-panel h4 {
    margin: 0 0 10px;
}

.selected-date-box {
    margin: 18px 0;
    padding: 18px;
    border-radius: 18px;
    background: white;
    border: 1px solid #e2e8f0;
}

.selected-date-box small {
    display: block;
    color: #64748b;
    margin-bottom: 6px;
}

.selected-date-box strong {
    font-size: 18px;
}

.legend {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 13px;
    margin-top: 12px;
}

.legend-box {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    background: #ef4444;
}

.btn-primary {
    width: 100%;
    border: none;
    padding: 14px 18px;
    border-radius: 15px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 12px 25px rgba(37,99,235,.25);
}

.package-grid,
.vendor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.select-card {
    cursor: pointer;
}

.select-card input {
    display: none;
}

.package-card,
.vendor-card {
    border: 2px solid #e2e8f0;
    border-radius: 24px;
    overflow: hidden;
    background: #fff;
    transition: .25s ease;
    height: 100%;
}

.select-card input:checked + .package-card,
.select-card input:checked + .vendor-card {
    border-color: #2563eb;
    box-shadow: 0 18px 40px rgba(37,99,235,.18);
    transform: translateY(-4px);
}

.package-img,
.vendor-img {
    width: 100%;
    height: 190px;
    object-fit: cover;
}

.package-placeholder {
    height: 190px;
    background: linear-gradient(135deg, #dbeafe, #e0f2fe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
}

.card-body {
    padding: 20px;
}

.card-body h3 {
    margin: 0 0 8px;
    font-size: 20px;
}

.price {
    color: #2563eb;
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 12px;
}

.card-body p {
    margin: 8px 0;
    color: #475569;
    line-height: 1.6;
    font-size: 13px;
}

.empty-state {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    padding: 26px;
    border-radius: 22px;
    text-align: center;
    color: #64748b;
}

.summary-card {
    position: relative;
    margin-top: 26px;
    background: linear-gradient(135deg, #0f172a, #1d4ed8);
    color: white;
    border-radius: 28px;
    padding: 26px;
    box-shadow: 0 25px 55px rgba(37,99,235,.28);
}

.summary-card h3 {
    margin: 0 0 10px;
}

.summary-card p {
    margin: 8px 0;
    opacity: .92;
}

.summary-total {
    margin: 18px 0;
    font-size: 30px;
    font-weight: 800;
}

.submit-btn {
    width: 100%;
    border: none;
    background: white;
    color: #1d4ed8;
    padding: 15px 20px;
    border-radius: 16px;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
}

@media(max-width: 900px) {
    .company-hero,
    .calendar-layout,
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <a href="company_details.php?company_id=<?= $company_id ?>" class="back-link">← Back to company details</a>

    <section class="company-hero">
        <?php if (!empty($company['logo'])) { ?>
            <img src="../uploads/companies/<?= htmlspecialchars($company['logo']) ?>" class="company-img">
        <?php } else { ?>
            <div class="company-placeholder"><?= strtoupper(substr($company['company_name'], 0, 1)) ?></div>
        <?php } ?>

        <div class="company-info">
            <div class="badge"><?= htmlspecialchars($category['name']) ?> Booking</div>
            <h1><?= htmlspecialchars($company['company_name']) ?></h1>
            <p><?= !empty($company['description']) ? htmlspecialchars($company['description']) : 'No description available.' ?></p>

            <div class="info-grid">
                <div class="info-box">
                    <small>Plan</small>
                    <strong><?= htmlspecialchars($company['plan']) ?></strong>
                </div>
                <div class="info-box">
                    <small>Phone</small>
                    <strong><?= htmlspecialchars($company['phone']) ?></strong>
                </div>
                <div class="info-box">
                    <small>Location</small>
                    <strong><?= htmlspecialchars($company['address']) ?></strong>
                </div>
            </div>
        </div>
    </section>

    <div class="booking-title">
        <h2>Book Your Event</h2>
        <p>Choose event date, select package and add vendor services if needed.</p>
    </div>

    <?php if ($error != "") { ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <div class="step-card">
        <div class="step-header">
            <div class="step-no">1</div>
            <div>
                <h3>Choose Event Date</h3>
                <p>Select an available date from the calendar. Red dates are unavailable.</p>
            </div>
        </div>

        <div class="calendar-layout">
            <div id="customerCalendar"></div>

            <div class="side-panel">
                <h4>Booking Date</h4>
                <p style="color:#64748b;font-size:13px;">Click one available date from the calendar.</p>

                <form method="POST" id="dateForm">
                    <input type="hidden" name="event_date" id="selectedDate" value="<?= htmlspecialchars($event_date) ?>" required>

                    <div class="selected-date-box">
                        <small>Selected Date</small>
                        <strong id="selectedDateText">
                            <?= $event_date != "" ? htmlspecialchars($event_date) : "No date selected" ?>
                        </strong>
                    </div>

                    <button class="btn-primary" name="check_availability">Check Availability</button>
                </form>

                <div class="legend">
                    <span class="legend-box"></span>
                    <span>Unavailable date</span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($available) { ?>

        <?php if (count($packages) == 0) { ?>
            <div class="empty-state">
                <h3>No packages available</h3>
                <p>This company has no package under this event category.</p>
            </div>
        <?php } else { ?>

            <form method="POST">
                <input type="hidden" name="event_date" value="<?= htmlspecialchars($event_date) ?>">

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-no">2</div>
                        <div>
                            <h3>Choose Package</h3>
                            <p>Select one package that matches your event needs.</p>
                        </div>
                    </div>

                    <div class="package-grid">
                        <?php foreach($packages as $package) { ?>
                            <label class="select-card">
                                <input type="radio" name="package_id" value="<?= $package['id'] ?>" data-price="<?= $package['base_price'] ?>" required>

                                <div class="package-card">
                                    <?php if (!empty($package['image'])) { ?>
                                        <img src="../uploads/packages/<?= htmlspecialchars($package['image']) ?>" class="package-img">
                                    <?php } else { ?>
                                        <div class="package-placeholder">📦</div>
                                    <?php } ?>

                                    <div class="card-body">
                                        <h3><?= htmlspecialchars($package['package_name']) ?></h3>
                                        <div class="price">RM <?= number_format($package['base_price'], 2) ?></div>
                                        <p><strong>Pax:</strong> <?= htmlspecialchars($package['pax']) ?> pax</p>
                                        <p><strong>Description:</strong> <?= htmlspecialchars($package['description']) ?></p>
                                        <p><strong>Menu:</strong> <?= htmlspecialchars($package['menu']) ?></p>
                                    </div>
                                </div>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-no">3</div>
                        <div>
                            <h3>Choose Add-On / Vendor Service</h3>
                            <p>You can select up to <?= $maxAddons ?> add-on(s). Only available vendors are shown.</p>
                        </div>
                    </div>

                    <?php if (count($vendors) == 0) { ?>
                        <div class="empty-state">
                            <h3>No add-on available</h3>
                            <p>You can continue booking without vendor add-on.</p>
                        </div>
                    <?php } else { ?>
                        <div class="vendor-grid">
                            <?php foreach($vendors as $vendor) { ?>
                                <label class="select-card">
                                    <input type="checkbox" name="vendor_ids[]" value="<?= $vendor['vendor_id'] ?>" data-price="<?= $vendor['price'] ?>" class="vendor-checkbox">

                                    <div class="vendor-card">
                                        <?php if (!empty($vendor['image'])) { ?>
                                            <img src="../uploads/vendors/<?= htmlspecialchars($vendor['image']) ?>" class="vendor-img">
                                        <?php } else { ?>
                                            <div class="package-placeholder">🎤</div>
                                        <?php } ?>

                                        <div class="card-body">
                                            <h3><?= htmlspecialchars($vendor['name']) ?></h3>
                                            <div class="price">RM <?= number_format($vendor['price'], 2) ?></div>
                                            <p><strong>Service:</strong> <?= htmlspecialchars($vendor['service_type']) ?></p>
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($vendor['phone']) ?></p>
                                            <p><strong>Description:</strong> <?= htmlspecialchars($vendor['description']) ?></p>
                                            <p><strong>Details:</strong> <?= htmlspecialchars($vendor['service_details']) ?></p>
                                            <p><strong>Equipment:</strong> <?= htmlspecialchars($vendor['equipment']) ?></p>
                                        </div>
                                    </div>
                                </label>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="summary-card">
                    <h3>Booking Summary</h3>
                    <p>Event Date: <strong><?= htmlspecialchars($event_date) ?></strong></p>
                    <div class="summary-total">RM <span id="totalPrice">0.00</span></div>
                    <button class="submit-btn" name="submit_booking">Submit Booking & Continue Payment</button>
                </div>
            </form>

        <?php } ?>

    <?php } ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('customerCalendar');

    if (calendarEl) {
        let blockedDates = <?= json_encode($blockedDates) ?>;
        const todayStr = new Date().toLocaleDateString('en-CA');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            contentHeight: 560,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            events: blockedDates,
            dateClick: function(info) {
                const clickedDate = info.dateStr;

                if (clickedDate < todayStr) {
                    alert("Past dates are unavailable for booking. Please select a current or future date.");
                    return;
                }

                const isBlocked = blockedDates.some(function(event) {
                    return event.start === clickedDate;
                });

                if (isBlocked) {
                    alert("This date is unavailable. Please choose another date.");
                    return;
                }

                document.getElementById("selectedDate").value = clickedDate;
                document.getElementById("selectedDateText").innerText = clickedDate;
            }
        });

        calendar.render();
    }
});
</script>

<script>
const maxAddons = <?= $maxAddons ?>;

function calculateTotal() {
    let total = 0;

    const selectedPackage = document.querySelector('input[name="package_id"]:checked');
    const selectedVendors = document.querySelectorAll('input[name="vendor_ids[]"]:checked');

    if (selectedPackage) {
        total += parseFloat(selectedPackage.dataset.price);
    }

    selectedVendors.forEach(vendor => {
        total += parseFloat(vendor.dataset.price);
    });

    const totalBox = document.getElementById("totalPrice");
    if (totalBox) {
        totalBox.innerText = total.toFixed(2);
    }
}

document.querySelectorAll('input[name="package_id"]').forEach(input => {
    input.addEventListener("change", calculateTotal);
});

document.querySelectorAll(".vendor-checkbox").forEach(checkbox => {
    checkbox.addEventListener("change", function() {
        const checked = document.querySelectorAll(".vendor-checkbox:checked");

        if (checked.length > maxAddons) {
            this.checked = false;
            alert("You can select up to " + maxAddons + " add-on(s) only.");
        }

        calculateTotal();
    });
});
</script>

</body>
</html>