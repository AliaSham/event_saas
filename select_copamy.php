<?php
include 'config.php';
session_start();

// Hanya tunjuk syarikat yang melanggan (Active) [cite: 75, 193]
$companies = mysqli_query($conn, "SELECT * FROM companies WHERE subscription_status = 'Active'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pilih Syarikat - Event SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <h2 class="text-center mb-4">Pilih Syarikat Untuk Tempahan</h2>
    <div class="row">
        <?php while($row = mysqli_fetch_assoc($companies)): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm text-center p-3">
                    <h5><?php echo $row['company_name']; ?></h5>
                    <p class="text-muted">Pelan: <?php echo $row['subscription_plan']; ?></p>
                    <a href="browse_packages.php?company_id=<?php echo $row['id']; ?>" class="btn btn-outline-primary">Lihat Pakej</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>