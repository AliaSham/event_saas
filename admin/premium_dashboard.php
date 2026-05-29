<?php
session_start();
include('../config/db.php');

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

/* =========================
   PLAN SECURITY
========================= */
$companyQuery = mysqli_query($conn, "
    SELECT plan
    FROM companies
    WHERE id = '$company_id'
");

$companyData = mysqli_fetch_assoc($companyQuery);

if(strtolower($companyData['plan'] ?? '') != 'premium'){
    die("Access Denied");
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Analytics Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body{
            background: #f8fafc; /* Tukar dari gelap ke cerah ikut rupa asal dashboard */
            color: #1a202c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Topbar yang telah di-tambah baik rupa bentuknya */
        .topbar{
            width: 100%;
            background: #ffffff;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.01);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-left i {
            font-size: 24px;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            padding: 10px;
            border-radius: 12px;
        }

        .topbar h2{
            color: #1a202c;
            font-size: 22px;
            font-weight: 700;
        }

        /* Butang Kembali yang Clean & Profesional */
        .back-btn{
            background: #ffffff;
            color: #475569;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .back-btn:hover{
            background: #f1f5f9;
            color: #1e293b;
            border-color: #cbd5e1;
            transform: translateX(-2px);
        }

        /* Ruang Kandungan Utama */
        .main-content {
            flex: 1;
            padding: 30px 24px;
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
        }

        /* Kotak Bingkai iFrame (Glassmorphism Effect) */
        .iframe-container{
            width: 100%;
            height: calc(100vh - 170px); /* Kurangkan sikit ketinggian supaya ngam dengan layout */
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.02);
            padding: 15px; /* Kasi ada gap border putih sekeliling graf */
            overflow: hidden;
        }

        iframe{
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="topbar-left">
            <i class="fa-solid fa-chart-line"></i>
            <h2>Premium Analytics Dashboard</h2>
        </div>

        <a href="dashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="main-content">
        <div class="iframe-container">
            <iframe
                src="https://datastudio.google.com/embed/reporting/107ea33c-d1cd-4995-97f7-15cf16a5e891/page/qUPyF"
                allowfullscreen>
            </iframe>
        </div>
    </div>

</body>
</html>