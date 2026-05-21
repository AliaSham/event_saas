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

$today_date = date('Y-m-d'); // Mengambil tarikh hari ini untuk semakan backend

/* GET COMPANY */
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

/* GET PLAN LIMIT */
$limits = getPlanLimits($company['plan']);
$maxAddons = $limits['addons_per_booking'];

/* GET CATEGORY */
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

/* CHECK COMPANY AVAILABLE */
function isCompanyAvailable($conn, $company_id, $event_date) {

    /* 1. Check date closed by admin */
    $calendarCheck = mysqli_query($conn, "
        SELECT * FROM company_calendar
        WHERE company_id='$company_id'
        AND event_date='$event_date'
    ");

    if (mysqli_num_rows($calendarCheck) > 0) {
        return false;
    }

    /* 2. Check existing pending / confirmed booking */
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

/* CHECK VENDOR AVAILABLE */
function isVendorAvailable($conn, $vendor_id, $event_date) {

    /* Vendor close date sendiri */
    $calendarCheck = mysqli_query($conn, "
        SELECT * FROM vendor_calendar
        WHERE vendor_user_id='$vendor_id'
        AND unavailable_date='$event_date'
    ");

    if (mysqli_num_rows($calendarCheck) > 0) {
        return false;
    }

    /* Vendor already booked / pending */
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

/* LOAD PACKAGES AND AVAILABLE VENDORS */
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

/* CHECK AVAILABILITY */
if (isset($_POST['check_availability'])) {

    $event_date = $_POST['event_date'];

    if ($event_date == "") {
        $error = "Please select event date.";
    }
    // SEKATAN BACKEND: Menghalang cubaan semakan tarikh lepas
    elseif ($event_date < $today_date) {
        $error = "You cannot check availability or book for a past date.";
    }
    elseif (!isCompanyAvailable($conn, $company_id, $event_date)) {
        $error = "This company is not available on the selected date.";
    }
    else {
        $available = true;
        loadBookingOptions($conn, $company_id, $category_id, $event_date, $packages, $vendors);
    }
}

/* SUBMIT BOOKING */
if (isset($_POST['submit_booking'])) {

    $event_date = $_POST['event_date'];
    $package_id = $_POST['package_id'];
    $vendor_ids = isset($_POST['vendor_ids']) ? $_POST['vendor_ids'] : [];

    if ($event_date == "" || $package_id == "") {
        $error = "Please select date and package.";
    }
    // SEKATAN BACKEND: Menghalang penyerahan borang bertarikh lepas
    elseif ($event_date < $today_date) {
        $error = "You cannot complete a booking on a past date.";
    }
    elseif (count($vendor_ids) > $maxAddons) {
        $error = "You can only select up to $maxAddons add-on(s).";
    }
    elseif (!isCompanyAvailable($conn, $company_id, $event_date)) {
        $error = "This company is not available on the selected date.";
    }
    else {

        $packageQ = mysqli_query($conn, "
            SELECT *
            FROM packages
            WHERE id='$package_id'
            AND company_id='$company_id'
            AND event_category_id='$category_id'
        ");

        if (mysqli_num_rows($packageQ) == 0) {
            $error = "Invalid package selected.";
        } 
        else {

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
                    ('$user_id', '$package_id', '$event_date', '$total_price', 'Pending', '$company_id', NULL)
                ");

                $booking_id = mysqli_insert_id($conn);

                foreach ($selectedVendorsData as $vendorData) {
                    $v_id = $vendorData['vendor_id'];
                    $v_price = $vendorData['price'];

                    mysqli_query($conn, "
                        INSERT INTO booking_vendors
                        (booking_id, vendor_id, price)
                        VALUES
                        ('$booking_id', '$v_id', '$v_price')
                    ");
                }

                header("Location: my_bookings.php");
                exit;
            }
        }
    }

    // Pastikan opsyen tidak dipanggil jika ralat tarikh berlaku
    if ($event_date != "" && $event_date >= $today_date && isCompanyAvailable($conn, $company_id, $event_date)) {
        $available = true;
        loadBookingOptions($conn, $company_id, $category_id, $event_date, $packages, $vendors);
    }
}

/* GET BLOCKED DATES FOR CUSTOMER CALENDAR */
$blockedDates = [];

/* 1. Admin closed dates */
$closedQ = mysqli_query($conn, "
    SELECT event_date
    FROM company_calendar
    WHERE company_id='$company_id'
");

while ($row = mysqli_fetch_assoc($closedQ)) {
    $blockedDates[] = [
        'title' => 'Unavailable',
        'start' => $row['event_date'],
        'backgroundColor' => '#ff4d4f',
        'borderColor' => '#ff4d4f',
        'textColor' => '#ffffff'
    ];
}

/* 2. Pending / Confirmed booking dates */
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
        'backgroundColor' => '#ff4d4f',
        'borderColor' => '#ff4d4f',
        'textColor' => '#ffffff'
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Event</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <a href="company_details.php?company_id=<?= $company_id ?>" class="back-link">
        ← Back to company details
    </a>

    <div class="booking-wrapper">

        <div class="booking-header">
            <h2>Book Event</h2>
            <p>
                <strong><?= $company['company_name'] ?></strong> • 
                <?= $category['name'] ?>
            </p>
        </div>

        <?php if ($error != "") { ?>
            <div class="alert error"><?= $error ?></div>
        <?php } ?>

        <div class="booking-step-card">

            <h3>Step 1: Choose Event Date</h3>
            <p class="muted">
                Select an available date from the calendar. Red dates are unavailable.
            </p>

            <div class="customer-calendar-wrapper">
                <div id="customerCalendar"></div>

                <div class="calendar-info bottom-info">
                    <span class="legend-box closed-box"></span>
                    <span>Unavailable</span>
                </div>
            </div>

            <form method="POST" id="dateForm">

                <input type="hidden"
                       name="event_date"
                       id="selectedDate"
                       value="<?= $event_date ?>"
                       required>

                <p class="selected-date-text">
                    Selected Date:
                    <strong id="selectedDateText">
                        <?= $event_date != "" ? $event_date : "No date selected" ?>
                    </strong>
                </p>

                <button class="btn btn-primary" name="check_availability">
                    Check Availability
                </button>

            </form>

        </div>

        <?php if ($available) { ?>

            <?php if (count($packages) == 0) { ?>

                <div class="empty-state">
                    <h3>No packages available</h3>
                    <p>This company has no package under this event category.</p>
                </div>

            <?php } else { ?>

                <form method="POST">

                    <input type="hidden" name="event_date" value="<?= $event_date ?>">

                    <div class="booking-step-card">

                        <h3>Step 2: Choose Package</h3>

                        <div class="package-grid">

                            <?php foreach($packages as $package) { ?>

                                <label class="select-card">

                                    <input type="radio"
                                           name="package_id"
                                           value="<?= $package['id'] ?>"
                                           data-price="<?= $package['base_price'] ?>"
                                           required>

                                    <div class="package-customer-card">

                                        <?php if (!empty($package['image'])) { ?>
                                            <img src="../uploads/packages/<?= $package['image'] ?>" 
                                                 class="package-customer-img"
                                                 alt="Package Image">
                                        <?php } else { ?>
                                            <div class="package-placeholder">📦</div>
                                        <?php } ?>

                                        <div class="package-customer-body">

                                            <h3><?= $package['package_name'] ?></h3>

                                            <p class="package-meta">
                                                RM <?= number_format($package['base_price'], 2) ?>
                                            </p>

                                            <p><strong>Pax:</strong> <?= $package['pax'] ?> pax</p>
                                            <p><strong>Description:</strong> <?= $package['description'] ?></p>
                                            <p><strong>Menu:</strong> <?= $package['menu'] ?></p>

                                        </div>

                                    </div>

                                </label>

                            <?php } ?>

                        </div>

                    </div>

                    <div class="booking-step-card">

                        <h3>Step 3: Choose Add-On / Vendor Service</h3>
                        <p class="muted">
                            You can select up to <?= $maxAddons ?> add-on(s). Only available vendors are shown.
                        </p>

                        <?php if (count($vendors) == 0) { ?>

                            <div class="empty-state">
                                <h3>No add-on available</h3>
                                <p>You can continue booking without vendor add-on.</p>
                            </div>

                        <?php } else { ?>

                            <div class="vendor-grid">

                                <?php foreach($vendors as $vendor) { ?>

                                    <label class="select-card">

                                        <input type="checkbox"
                                               name="vendor_ids[]"
                                               value="<?= $vendor['vendor_id'] ?>"
                                               data-price="<?= $vendor['price'] ?>"
                                               class="vendor-checkbox">

                                        <div class="vendor-card">

                                            <?php if (!empty($vendor['image'])) { ?>
                                                <img src="../uploads/vendors/<?= $vendor['image'] ?>" 
                                                     class="vendor-card-img"
                                                     alt="Vendor Image">
                                            <?php } ?>

                                            <div class="vendor-card-body">

                                                <h3><?= $vendor['name'] ?></h3>

                                                <p class="vendor-type">
                                                    <?= $vendor['service_type'] ?> • 
                                                    RM <?= number_format($vendor['price'], 2) ?>
                                                </p>

                                                <p><strong>Phone:</strong> <?= $vendor['phone'] ?></p>
                                                <p><strong>Description:</strong> <?= $vendor['description'] ?></p>
                                                <p><strong>Details:</strong> <?= $vendor['service_details'] ?></p>
                                                <p><strong>Equipment:</strong> <?= $vendor['equipment'] ?></p>

                                            </div>

                                        </div>

                                    </label>

                                <?php } ?>

                            </div>

                        <?php } ?>

                    </div>

                    <div class="booking-step-card">

                        <h3>Booking Summary</h3>

                        <p>
                            Event Date:
                            <strong><?= $event_date ?></strong>
                        </p>

                        <h2>
                            Total: RM <span id="totalPrice">0.00</span>
                        </h2>

                        <button class="btn btn-primary" name="submit_booking">
                            Submit Booking
                        </button>

                    </div>

                </form>

            <?php } ?>

        <?php } ?>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const calendarEl = document.getElementById('customerCalendar');

    if (calendarEl) {

        let blockedDates = <?= json_encode($blockedDates) ?>;
        
        // Mengambil tarikh hari ini mengikut format standard YYYY-MM-DD
        const todayStr = new Date().toLocaleDateString('en-CA'); 

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            contentHeight: 500,

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },

            events: blockedDates,

            dateClick: function(info) {
                const clickedDate = info.dateStr;

                // 1. SEKATAN JAVASCRIPT: Menghalang klik tarikh lepas
                if (clickedDate < todayStr) {
                    alert("Past dates are unavailable for booking. Please select a current or future date.");
                    return;
                }

                // 2. Semak jika tarikh tersebut telah disekat/penuh
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