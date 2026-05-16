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

if($companyData['plan'] != 'premium'){
    die("Access Denied");
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Premium Analytics Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background:#0f172a;
        }

        .topbar{
            width:100%;
            background:#111827;
            padding:20px 40px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:0 4px 20px rgba(0,0,0,0.2);
        }

        .topbar h2{
            color:white;
            font-size:28px;
            font-weight:600;
        }

        .back-btn{
            background:#2563eb;
            color:white;
            padding:12px 20px;
            border-radius:12px;
            text-decoration:none;
            font-weight:600;
            transition:0.3s;
        }

        .back-btn:hover{
            background:#1d4ed8;
        }

        .iframe-container{
            width:100%;
            height:calc(100vh - 90px);
        }

        iframe{
            width:100%;
            height:100%;
            border:none;
        }

    </style>

</head>

<body>

    <div class="topbar">

        <h2>Premium Analytics Dashboard</h2>

        <a href="dashboard.php" class="back-btn">
            Back to Dashboard
        </a>

    </div>

    <div class="iframe-container">

        <iframe
            src="https://datastudio.google.com/embed/reporting/107ea33c-d1cd-4995-97f7-15cf16a5e891/page/qUPyF"
            allowfullscreen>
        </iframe>

    </div>

</body>
</html>