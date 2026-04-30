<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}
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
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: white;
            border-bottom: 1px solid #ddd;
        }

        .topbar a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            margin-right: 20px;
        }

        .logout {
            color: red;
        }

        /* MAIN */
        .container {
            padding: 40px;
        }

        .title {
            font-size: 28px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        /* GRID */
        .grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* CARD */
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            flex: 1;
            min-width: 250px;
        }

        .card h3 {
            margin-bottom: 10px;
        }

        /* BUTTON */
        .btn-primary {
            display: inline-block;
            padding: 12px 20px;
            background: #1677ff;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #0f5ed7;
        }
    </style>
</head>

<body>

<div class="topbar">
    <div>
        <a href="#">Dashboard</a>
    </div>

    <div>
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">

    <div class="title">Customer Dashboard</div>
    <div class="subtitle">Start your event booking here.</div>

    <div class="grid">

        <!-- ACCOUNT -->
        <div class="card">
            <h3>Account Info</h3>
            <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
            <p>Company ID: <?php echo $_SESSION['company_id']; ?></p>
        </div>

        <!-- MAIN ACTION -->
        <div class="card">
            <h3>Make Booking</h3>
            <p>Choose event, package and add-ons.</p>

            <a href="booking.php" class="btn-primary">
                Start Booking
            </a>
        </div>

    </div>

</div>

</body>
</html>
<a href="my_bookings.php" class="btn">View My Bookings</a>