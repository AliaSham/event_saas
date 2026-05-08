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

    mysqli_query($conn, "
        DELETE FROM vendor_calendar
        WHERE vendor_user_id='$vendor_id'
        AND unavailable_date='$unavailable_date'
    ");

    echo "success";
    exit;
}

/* FETCH VENDOR CLOSED DATES */
$calendarEvents = [];

$result = mysqli_query($conn, "
    SELECT * FROM vendor_calendar
    WHERE vendor_user_id='$vendor_id'
    ORDER BY unavailable_date ASC
");

while ($row = mysqli_fetch_assoc($result)) {
    $calendarEvents[] = [
        'title' => 'Unavailable',
        'start' => $row['unavailable_date'],
        'backgroundColor' => '#ff4d4f',
        'borderColor' => '#ff4d4f',
        'textColor' => '#ffffff'
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

<div class="navbar">
    <div class="nav-left">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../profile.php">Profile</a>
        <a href="index.php">Calendar</a>
    </div>

    <div class="nav-right">
        <a href="../../auth/logout.php">Logout</a>
    </div>
</div>

<div class="container">

    <div class="page-header">
        <div>
            <h2 class="page-title">Vendor Calendar</h2>
            <p class="muted">Close dates when you are not available</p>
        </div>
    </div>

    <div class="calendar-wrapper">

        <div id="calendar"></div>

        <div class="calendar-info bottom-info">
            <span class="legend-box closed-box"></span>
            <span>Unavailable Date</span>
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

            const isUnavailable = calendarEvents.some(function(event) {
                return event.start === clickedDate && event.title === "Unavailable";
            });

            if (isUnavailable) {
                const confirmOpen = confirm("This date is currently unavailable. Do you want to make it available again?");

                if (confirmOpen) {
                    fetch("index.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "action=open&unavailable_date=" + encodeURIComponent(clickedDate)
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
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
                        body: "action=close&unavailable_date=" + encodeURIComponent(clickedDate) + "&reason=" + encodeURIComponent(reason)
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

</body>
</html>