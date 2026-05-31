<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../../auth/login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

$userQ = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id='$vendor_id' AND role='vendor'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user || $user['approval_status'] != 'active') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close') {

    $unavailable_date = $_POST['unavailable_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    if (strtotime($unavailable_date) < strtotime(date('Y-m-d'))) {
        echo "past_date";
        exit;
    }

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'open') {

    $unavailable_date = $_POST['unavailable_date'];

    if (strtotime($unavailable_date) < strtotime(date('Y-m-d'))) {
        echo "past_date";
        exit;
    }

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

$calendarEvents = [];

$unavailableCount = 0;
$pendingCount = 0;
$confirmedCount = 0;

$unavailableQ = mysqli_query($conn, "
    SELECT * FROM vendor_calendar
    WHERE vendor_user_id='$vendor_id'
    ORDER BY unavailable_date ASC
");

while ($row = mysqli_fetch_assoc($unavailableQ)) {

    $unavailableCount++;

    $calendarEvents[] = [
        'title' => 'Unavailable',
        'start' => $row['unavailable_date'],
        'backgroundColor' => '#ef4444',
        'borderColor' => '#ef4444',
        'textColor' => '#ffffff',
        'extendedProps' => [
            'type' => 'unavailable',
            'reason' => $row['reason']
        ]
    ];
}

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
        $pendingCount++;
        $color = '#facc15';
        $textColor = '#0f172a';
        $title = 'Pending Booking';
        $type = 'pending';
    } else {
        $confirmedCount++;
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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #0f172a;
        }

        .calendar-page {
            padding: 38px 55px;
        }

        .calendar-hero {
            background: linear-gradient(135deg, #0f766e, #14b8a6, #2563eb);
            color: white;
            padding: 35px 40px;
            border-radius: 28px;
            box-shadow: 0 18px 45px rgba(15,118,110,0.25);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .calendar-hero::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
        }

        .calendar-hero h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }

        .calendar-hero p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: .95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 14px 35px rgba(15,23,42,0.07);
            border: 1px solid #e5e7eb;
        }

        .stat-card span {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-card h2 {
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 800;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        .calendar-card {
            background: white;
            border-radius: 28px;
            padding: 26px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 16px 40px rgba(15,23,42,0.08);
        }

        #calendar {
            width: 100%;
        }

        .side-card {
            background: white;
            border-radius: 28px;
            padding: 26px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 16px 40px rgba(15,23,42,0.08);
        }

        .side-card h3 {
            margin: 0;
            font-size: 21px;
        }

        .side-card p {
            margin: 8px 0 20px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            font-weight: 600;
        }

        .legend-item:last-child {
            border-bottom: none;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 5px;
        }

        .red {
            background: #ef4444;
        }

        .yellow {
            background: #facc15;
        }

        .green {
            background: #22c55e;
        }

        .tips-box {
            margin-top: 22px;
            padding: 18px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
        }

        .tips-box strong {
            font-size: 14px;
        }

        .tips-box ul {
            padding-left: 18px;
            margin: 12px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.8;
        }

        .fc {
            font-family: 'Poppins', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 23px;
            font-weight: 800;
            color: #0f172a;
        }

        .fc .fc-button-primary {
            background: #2563eb;
            border: none;
            border-radius: 12px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: none;
        }

        .fc .fc-button-primary:hover {
            background: #1d4ed8;
        }

        .fc .fc-daygrid-day {
            transition: .2s;
        }

        .fc .fc-daygrid-day:hover {
            background: #f8fafc;
            cursor: pointer;
        }

        .fc .fc-day-today {
            background: #ecfeff !important;
        }

        .fc-event {
            border-radius: 10px !important;
            padding: 3px 6px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            border: none !important;
        }

        @media(max-width: 1100px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .calendar-page {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

<?php include('../header.php'); ?>

<div class="calendar-page">

    <div class="calendar-hero">
        <h1>Vendor Calendar</h1>
        <p>Manage unavailable dates, monitor booking status and keep your event schedule organized.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span>Unavailable Dates</span>
            <h2><?= $unavailableCount ?></h2>
        </div>

        <div class="stat-card">
            <span>Pending Bookings</span>
            <h2><?= $pendingCount ?></h2>
        </div>

        <div class="stat-card">
            <span>Confirmed Bookings</span>
            <h2><?= $confirmedCount ?></h2>
        </div>
    </div>

    <div class="layout-grid">

        <div class="calendar-card">
            <div id="calendar"></div>
        </div>

        <div class="side-card">
            <h3>Calendar Guide</h3>
            <p>Click any available future date to close it. Click an unavailable date again to reopen it.</p>

            <div class="legend-item">
                <span class="legend-dot red"></span>
                Unavailable Date
            </div>

            <div class="legend-item">
                <span class="legend-dot yellow"></span>
                Pending Booking
            </div>

            <div class="legend-item">
                <span class="legend-dot green"></span>
                Confirmed Booking
            </div>

            <div class="tips-box">
                <strong>Quick Tips</strong>
                <ul>
                    <li>Past dates cannot be edited.</li>
                    <li>Booked dates cannot be manually opened or closed.</li>
                    <li>Click booking events to view customer details.</li>
                </ul>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const calendarEl = document.getElementById('calendar');
    let calendarEvents = <?= json_encode($calendarEvents) ?>;

    const calendar = new FullCalendar.Calendar(calendarEl, {

        initialView: 'dayGridMonth',
        contentHeight: 640,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },

        events: calendarEvents,

        eventClick: function(info) {

            const eventType = info.event.extendedProps.type;

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

            if (eventType === "unavailable") {

                alert(
                    "Unavailable date\nReason: " +
                    info.event.extendedProps.reason
                );
            }
        },

        dateClick: function(info) {

            const clickedDate = info.dateStr;

            const today = new Date();
            today.setHours(0,0,0,0);

            const selectedDate = new Date(clickedDate);
            selectedDate.setHours(0,0,0,0);

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

            if (hasBooking) {
                alert("This date has a booking. You cannot manually open or close this date.");
                return;
            }

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

                const reason = prompt("Enter reason why you are unavailable:");

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