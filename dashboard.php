<?php
include 'config.php';
session_start();

// Kalau belum login, tendang balik pergi login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Ambil maklumat user yang login
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Event SaaS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f4; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .menu { margin-top: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .logout { background: #dc3545; }
    </style>
</head>
<body>

    <div class="card">
        <h1>Selamat Datang, <?php echo $user_data['name']; ?>! 👋</h1>
        <p>Anda login sebagai: <strong><?php echo strtoupper($role); ?></strong></p>
        <hr>

        <div class="menu">
            <h3>Menu Utama:</h3>
            
            <?php if($role == 'admin'): ?>
                <a href="manage_packages.php" class="btn">📦 Urus Pakej</a>
                <a href="view_bookings.php" class="btn">📅 Lihat Semua Tempahan</a>
                <a href="manage_users.php" class="btn">👥 Urus Staff/User</a>

            <?php elseif($role == 'vendor'): ?>
                <a href="my_services.php" class="btn">🛠 Servis Saya</a>
                <a href="job_list.php" class="btn">📋 Senarai Job</a>

            <?php else: ?>
                <a href="browse_packages.php" class="btn">🛒 Tempah Pakej</a>
                <a href="my_bookings.php" class="btn">📜 Sejarah Tempahan</a>
            <?php endif; ?>

            <br><br><br>
            <a href="logout.php" class="btn logout">Logout</a>
        </div>
    </div>

</body>
</html>