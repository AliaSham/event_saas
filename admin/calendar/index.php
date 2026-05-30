<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$today_date = date('Y-m-d');

/* CLOSE DATE */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close') {

    $event_date = $_POST['event_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    if ($event_date < $today_date) {
        echo "error_past_date";
        exit;
    }

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

    if ($event_date < $today_date) {
        echo "error_past_date";
        exit;
    }

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
$closedCount = 0;
$pendingCount = 0;
$confirmedCount = 0;

/* CLOSED DATES */
$closedResult = mysqli_query($conn, "
    SELECT * FROM company_calendar
    WHERE company_id='$company_id'
    ORDER BY event_date ASC
");

while ($row = mysqli_fetch_assoc($closedResult)) {
    $closedCount++;

    $calendarEvents[] = [
        'title' => 'Closed',
        'start' => $row['event_date'],
        'backgroundColor' => '#ef4444',
        'borderColor' => '#ef4444',
        'textColor' => '#ffffff'
    ];
}

/* BOOKING DATES */
$bookingResult = mysqli_query($conn, "
    SELECT * FROM bookings
    WHERE company_id='$company_id'
    ORDER BY event_date ASC
");

while ($row = mysqli_fetch_assoc($bookingResult)) {

    if ($row['status'] == 'Pending') {
        $pendingCount++;
        $color = '#facc15';
        $textColor = '#111827';
    } elseif ($row['status'] == 'Confirmed') {
        $confirmedCount++;
        $color = '#22c55e';
        $textColor = '#ffffff';
    } else {
        $color = '#94a3b8';
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

<style>
.calendar-page{
    min-height:100vh;
    padding:45px;
    background:
        radial-gradient(circle at top left, #d1fae5 0, transparent 28%),
        linear-gradient(135deg,#f8fafc,#eef2ff);
}

.calendar-container{
    max-width:1200px;
    margin:auto;
}

.calendar-hero{
    background:#052e1b;
    color:white;
    padding:32px 36px;
    border-radius:30px;
    margin-bottom:26px;
    box-shadow:0 20px 45px rgba(5,46,27,.25);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
}

.calendar-hero h1{
    margin:8px 0;
    font-size:36px;
    color:white;
}

.calendar-hero p{
    margin:0;
    color:#bbf7d0;
}

.hero-label{
    font-size:13px;
    color:#86efac;
    font-weight:800;
    letter-spacing:.8px;
    text-transform:uppercase;
}

.today-badge{
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.25);
    color:white;
    padding:14px 18px;
    border-radius:18px;
    font-weight:800;
    white-space:nowrap;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:26px;
}

.stat-card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.7);
    border-radius:24px;
    padding:22px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
}

.stat-card span{
    color:#64748b;
    font-size:13px;
    font-weight:800;
}

.stat-card h3{
    margin:8px 0 0;
    font-size:30px;
    color:#0f172a;
}

.calendar-layout{
    display:grid;
    grid-template-columns:1.7fr .7fr;
    gap:24px;
}

.calendar-card,
.info-card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:28px;
    padding:26px;
    box-shadow:0 22px 55px rgba(15,23,42,.10);
}

.info-card{
    height:fit-content;
}

.info-card h3{
    margin:0 0 12px;
    font-size:22px;
    color:#111827;
}

.info-card p{
    color:#64748b;
    font-size:14px;
    line-height:1.7;
    margin-bottom:20px;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px;
    border-radius:16px;
    background:#f8fafc;
    margin-bottom:12px;
    font-weight:700;
    color:#334155;
}

.legend-dot{
    width:14px;
    height:14px;
    border-radius:50%;
}

.closed-dot{ background:#ef4444; }
.pending-dot{ background:#facc15; }
.confirmed-dot{ background:#22c55e; }

.tips-box{
    margin-top:20px;
    background:#ecfdf5;
    border:1px solid #bbf7d0;
    color:#166534;
    padding:16px;
    border-radius:18px;
    font-size:13px;
    line-height:1.7;
    font-weight:600;
}

.fc{
    font-family:'Poppins', sans-serif;
}

.fc .fc-toolbar-title{
    font-size:24px;
    font-weight:900;
    color:#111827;
}

.fc .fc-button{
    background:#10b981 !important;
    border:none !important;
    border-radius:12px !important;
    padding:9px 14px !important;
    font-weight:800 !important;
    text-transform:capitalize !important;
    box-shadow:0 8px 18px rgba(16,185,129,.25);
}

.fc .fc-button:hover{
    background:#047857 !important;
}

.fc .fc-button-active{
    background:#052e1b !important;
}

.fc-col-header-cell{
    background:#f0fdf4;
}

.fc-col-header-cell-cushion,
.fc-daygrid-day-number{
    text-decoration:none !important;
    color:#111827;
    font-weight:700;
}

.fc-daygrid-day{
    transition:.2s;
}

.fc-daygrid-day:hover{
    background:#f8fafc;
    cursor:pointer;
}

.fc-day-today{
    background:#fef9c3 !important;
}

.fc-event{
    border-radius:10px !important;
    padding:3px 6px !important;
    font-size:12px !important;
    font-weight:800 !important;
    border:none !important;
}

@media(max-width:1000px){
    .calendar-layout{
        grid-template-columns:1fr;
    }

    .stats-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){
    .calendar-page{
        padding:20px;
    }

    .calendar-hero{
        flex-direction:column;
        align-items:flex-start;
        padding:26px;
    }

    .calendar-hero h1{
        font-size:28px;
    }

    .calendar-card,
    .info-card{
        padding:18px;
    }
}
</style>

<div class="calendar-page">

    <div class="calendar-container">

        <div class="calendar-hero">
            <div>
                <span class="hero-label">Booking Availability</span>
                <h1>Manage Calendar</h1>
                <p>Close unavailable dates and monitor booking status in one smart dashboard.</p>
            </div>

            <div class="today-badge">
                Today: <?= date('d M Y') ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span>Closed Dates</span>
                <h3><?= $closedCount ?></h3>
            </div>

            <div class="stat-card">
                <span>Pending Bookings</span>
                <h3><?= $pendingCount ?></h3>
            </div>

            <div class="stat-card">
                <span>Confirmed Bookings</span>
                <h3><?= $confirmedCount ?></h3>
            </div>
        </div>

        <div class="calendar-layout">

            <div class="calendar-card">
                <div id="calendar"></div>
            </div>

            <div class="info-card">
                <h3>Calendar Guide</h3>
                <p>
                    Click any available future date to close it. 
                    Click a closed date again to reopen it for booking.
                </p>

                <div class="legend-item">
                    <span class="legend-dot closed-dot"></span>
                    Closed Date
                </div>

                <div class="legend-item">
                    <span class="legend-dot pending-dot"></span>
                    Pending Booking
                </div>

                <div class="legend-item">
                    <span class="legend-dot confirmed-dot"></span>
                    Confirmed Booking
                </div>

                <div class="tips-box">
                    Note: Past dates cannot be changed. Dates with bookings cannot be closed here to avoid conflict.
                </div>
            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const calendarEl = document.getElementById('calendar');
    let calendarEvents = <?= json_encode($calendarEvents) ?>;

    const todayStr = new Date().toLocaleDateString('en-CA');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        contentHeight: 620,
        height: 'auto',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },

        buttonText: {
            today: 'Today',
            month: 'Month',
            list: 'List'
        },

        events: calendarEvents,

        dateClick: function(info) {

            const clickedDate = info.dateStr;

            if (clickedDate < todayStr) {
                alert("You cannot manage or change status for past dates.");
                return;
            }

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

<?php include('../footer.php'); ?>