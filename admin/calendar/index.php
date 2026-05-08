<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* CLOSE DATE */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close') {

    $event_date = $_POST['event_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    $check = mysqli_query($conn, "
        SELECT * FROM company_calendar
        WHERE company_id='$company_id'
        AND event_date='$event_date'
    ");

    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "
            INSERT INTO company_calendar
            (company_id, event_date, status, reason)
            VALUES
            ('$company_id', '$event_date', 'closed', '$reason')
        ");
    }

    echo "success";
    exit;
}

/* OPEN DATE AGAIN */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'open') {

    $event_date = $_POST['event_date'];

    mysqli_query($conn, "
        DELETE FROM company_calendar
        WHERE company_id='$company_id'
        AND event_date='$event_date'
    ");

    echo "success";
    exit;
}

/* FETCH CALENDAR EVENTS */
$calendarEvents = [];

/* 1. CLOSED DATES FROM ADMIN */
$closedResult = mysqli_query($conn, "
    SELECT * FROM company_calendar
    WHERE company_id='$company_id'
    ORDER BY event_date ASC
");

while ($row = mysqli_fetch_assoc($closedResult)) {
    $calendarEvents[] = [
        'title' => 'Closed',
        'start' => $row['event_date'],
        'backgroundColor' => '#ff4d4f',
        'borderColor' => '#ff4d4f',
        'textColor' => '#ffffff'
    ];
}

/* 2. BOOKING DATES */
$bookingResult = mysqli_query($conn, "
    SELECT * FROM bookings
    WHERE company_id='$company_id'
    ORDER BY event_date ASC
");

while ($row = mysqli_fetch_assoc($bookingResult)) {

    if ($row['status'] == 'Pending') {
        $color = '#facc15';
        $textColor = '#000000';
    } 
    elseif ($row['status'] == 'Confirmed') {
        $color = '#22c55e';
        $textColor = '#ffffff';
    } 
    else {
        $color = '#9ca3af';
        $textColor = '#ffffff';
    }

    $calendarEvents[] = [
        'title' => $row['status'] . ' Booking',
        'start' => $row['event_date'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor
    ];
}

include('../header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Manage Calendar</h2>
            <p class="muted">Close dates that are unavailable for booking</p>
        </div>
    </div>

    <div class="calendar-wrapper">

        <div id="calendar"></div>

        <div class="calendar-info bottom-info">
            <span class="legend-box closed-box"></span>
            <span>Closed</span>

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

        dateClick: function(info) {
            const clickedDate = info.dateStr;

            const isClosed = calendarEvents.some(function(event) {
                return event.start === clickedDate && event.title === "Closed";
            });

            const hasBooking = calendarEvents.some(function(event) {
                return event.start === clickedDate && event.title !== "Closed";
            });

            if (hasBooking) {
                alert("This date already has a booking. You cannot close/open it here.");
                return;
            }

            if (isClosed) {
                const confirmOpen = confirm("This date is currently closed. Do you want to open it again?");

                if (confirmOpen) {
                    fetch("index.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "action=open&event_date=" + encodeURIComponent(clickedDate)
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    });
                }

            } else {
                const reason = prompt("Enter reason to close this date:");

                if (reason !== null && reason.trim() !== "") {
                    fetch("index.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "action=close&event_date=" + encodeURIComponent(clickedDate) + "&reason=" + encodeURIComponent(reason)
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    });
                }
            }
        }
    });

    calendar.render();
});
</script>