<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

/* CHECK VENDOR APPROVAL */
$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../../auth/login.php");
    exit;
}

/* CLOSE DATE */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close') {

    $unavailable_date = $_POST['unavailable_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    // BLOCK PAST DATE
    if (strtotime($unavailable_date) < strtotime(date('Y-m-d'))) {
        echo "past_date";
        exit;
    }

    // CHECK EXISTING BOOKING
    $bookingCheck = mysqli_query($conn, "
        SELECT *
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id = '$vendor_id'
        AND b.event_date = '$unavailable_date'
        AND b.status IN ('Pending', 'Confirmed')
    ");

    if (mysqli_num_rows($bookingCheck) > 0) {
        echo "booking_exists";
        exit;
    }

    $check = mysqli_query($conn, "
        SELECT * FROM vendor_calendar
        WHERE vendor_user_id='$vendor_id'
        AND unavailable_date='$unavailable_date'
    ");

    if (mysqli_num_rows($check) == 0) {

        mysqli_query($conn, "
            INSERT INTO vendor_calendar
            (vendor_user_id, unavailable_date, reason)
            VALUES
            ('$vendor_id', '$unavailable_date', '$reason')
        ");
    }

    echo "success";
    exit;
}

/* OPEN DATE AGAIN */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'open') {

    $unavailable_date = $_POST['unavailable_date'];

    // BLOCK PAST DATE
    if (strtotime($unavailable_date) < strtotime(date('Y-m-d'))) {
        echo "past_date";
        exit;
    }

    // CHECK EXISTING BOOKING
    $bookingCheck = mysqli_query($conn, "
        SELECT *
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id = '$vendor_id'
        AND b.event_date = '$unavailable_date'
        AND b.status IN ('Pending', 'Confirmed')
    ");

    if (mysqli_num_rows($bookingCheck) > 0) {
        echo "booking_exists";
        exit;
    }

    mysqli_query($conn, "
        DELETE FROM vendor_calendar
        WHERE vendor_user_id='$vendor_id'
        AND unavailable_date='$unavailable_date'
    ");

    echo "success";
    exit;
}

/* FETCH CALENDAR EVENTS */
$calendarEvents = [];

/* 1. VENDOR UNAVAILABLE DATES - RED */
$unavailableQ = mysqli_query($conn, "
    SELECT * FROM vendor_calendar
    WHERE vendor_user_id='$vendor_id'
    ORDER BY unavailable_date ASC
");

while ($row = mysqli_fetch_assoc($unavailableQ)) {

    $calendarEvents[] = [
        'title' => 'Unavailable',
        'start' => $row['unavailable_date'],
        'backgroundColor' => '#ff4d4f',
        'borderColor' => '#ff4d4f',
        'textColor' => '#ffffff',
        'extendedProps' => [
            'type' => 'unavailable',
            'reason' => $row['reason']
        ]
    ];
}

/* 2. PENDING / CONFIRMED BOOKINGS */
$bookingQ = mysqli_query($conn, "
    SELECT 
        b.id AS booking_id,
        b.event_date,
        b.status,
        b.total_price,
        c.company_name,
        p.package_name,
        u.name AS customer_name
    FROM booking_vendors bv
    JOIN bookings b ON bv.booking_id = b.id
    JOIN companies c ON b.company_id = c.id
    JOIN packages p ON b.package_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE bv.vendor_id = '$vendor_id'
    AND b.status IN ('Pending', 'Confirmed')
    ORDER BY b.event_date ASC
");

while ($row = mysqli_fetch_assoc($bookingQ)) {

    if ($row['status'] == 'Pending') {

        $color = '#facc15';
        $textColor = '#000000';
        $title = 'Pending Booking';
        $type = 'pending';

    } else {

        $color = '#22c55e';
        $textColor = '#ffffff';
        $title = 'Confirmed Booking';
        $type = 'confirmed';
    }

    $calendarEvents[] = [
        'title' => $title,
        'start' => $row['event_date'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'type' => $type,
            'booking_id' => $row['booking_id'],
            'company_name' => $row['company_name'],
            'package_name' => $row['package_name'],
            'customer_name' => $row['customer_name'],
            'total_price' => $row['total_price']
        ]
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Calendar</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>

<body>

<?php include('../header.php'); ?>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Vendor Calendar</h2>
            <p class="muted">Manage unavailable dates and view booking status</p>
        </div>
    </div>

    <div class="calendar-wrapper">

        <div id="calendar"></div>

        <div class="calendar-info bottom-info">

            <span class="legend-box closed-box"></span>
            <span>Unavailable</span>

            <span class="legend-box pending-box"></span>
            <span>Pending Booking</span>

            <span class="legend-box confirmed-box"></span>
            <span>Confirmed Booking</span>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const calendarEl = document.getElementById('calendar');

    let calendarEvents = <?= json_encode($calendarEvents) ?>;

    const calendar = new FullCalendar.Calendar(calendarEl, {

        initialView: 'dayGridMonth',
        contentHeight: 500,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },

        events: calendarEvents,

        eventClick: function(info) {

            const eventType = info.event.extendedProps.type;

            // BOOKING DETAILS
            if (eventType === "pending" || eventType === "confirmed") {

                let details =
                    "Booking ID: #" + info.event.extendedProps.booking_id + "\n" +
                    "Status: " + info.event.title + "\n" +
                    "Company: " + info.event.extendedProps.company_name + "\n" +
                    "Customer: " + info.event.extendedProps.customer_name + "\n" +
                    "Package: " + info.event.extendedProps.package_name + "\n" +
                    "Total Price: RM " +
                    parseFloat(info.event.extendedProps.total_price).toFixed(2);

                alert(details);
            }

            // UNAVAILABLE DETAILS
            if (eventType === "unavailable") {

                alert(
                    "Unavailable date\nReason: " +
                    info.event.extendedProps.reason
                );
            }
        },

        dateClick: function(info) {

            const clickedDate = info.dateStr;

            // TODAY DATE
            const today = new Date();
            today.setHours(0,0,0,0);

            // CLICKED DATE
            const selectedDate = new Date(clickedDate);
            selectedDate.setHours(0,0,0,0);

            // BLOCK PAST DATES
            if (selectedDate < today) {
                alert("You cannot modify past dates.");
                return;
            }

            const eventsOnDate = calendarEvents.filter(function(event) {
                return event.start === clickedDate;
            });

            const hasBooking = eventsOnDate.some(function(event) {
                return event.extendedProps.type === "pending" ||
                       event.extendedProps.type === "confirmed";
            });

            const isUnavailable = eventsOnDate.some(function(event) {
                return event.extendedProps.type === "unavailable";
            });

            // BLOCK IF HAVE BOOKING
            if (hasBooking) {

                alert(
                    "This date has a booking. You cannot manually open or close this date."
                );

                return;
            }

            // OPEN DATE AGAIN
            if (isUnavailable) {

                const confirmOpen = confirm(
                    "This date is currently unavailable. Do you want to make it available again?"
                );

                if (confirmOpen) {

                    fetch("index.php", {

                        method: "POST",

                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },

                        body:
                            "action=open&unavailable_date=" +
                            encodeURIComponent(clickedDate)

                    })
                    .then(response => response.text())
                    .then(data => {

                        if (data === "past_date") {

                            alert("You cannot modify past dates.");

                        } else if (data === "booking_exists") {

                            alert("This date already has a booking.");

                        } else {

                            location.reload();
                        }
                    });
                }

            } else {

                // CLOSE DATE
                const reason = prompt(
                    "Enter reason why you are unavailable:"
                );

                if (reason !== null && reason.trim() !== "") {

                    fetch("index.php", {

                        method: "POST",

                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },

                        body:
                            "action=close&unavailable_date=" +
                            encodeURIComponent(clickedDate) +
                            "&reason=" +
                            encodeURIComponent(reason)

                    })
                    .then(response => response.text())
                    .then(data => {

                        if (data === "past_date") {

                            alert("You cannot modify past dates.");

                        } else if (data === "booking_exists") {

                            alert("This date already has a booking.");

                        } else {

                            location.reload();
                        }
                    });
                }
            }
        }
    });

    calendar.render();
});
</script>

</body>
</html>