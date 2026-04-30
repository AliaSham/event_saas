<?php
session_start();
include('../config/db.php');
include('../config/plan.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

/* ===== GET COMPANY PLAN ===== */
$res = mysqli_query($conn, "SELECT plan FROM companies WHERE id='$company_id'");
$data = mysqli_fetch_assoc($res);
$limits = getPlanLimits($data['plan']);

/* ===== FETCH DATA ===== */
$catResult = mysqli_query($conn, "SELECT * FROM event_categories WHERE company_id='$company_id'");
$packageResult = mysqli_query($conn, "SELECT * FROM packages WHERE company_id='$company_id'");
$serviceResult = mysqli_query($conn, "SELECT * FROM vendor_services WHERE company_id='$company_id'");

/* ===== SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $package_id = $_POST['package_id'];
    $date = $_POST['event_date'];
    $services = $_POST['services'] ?? [];

    /* 🔥 CHECK ACTIVE BOOKING LIMIT */
    $countQ = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE company_id='$company_id'");
    $countData = mysqli_fetch_assoc($countQ);

    if ($countData['total'] >= $limits['bookings']) {
        die("❌ Your company reached booking limit for this plan.");
    }

    /* 🔥 GET PACKAGE PRICE */
    $priceQuery = mysqli_query($conn, "SELECT base_price FROM packages WHERE id='$package_id'");
    $priceData = mysqli_fetch_assoc($priceQuery);

    if (!$priceData) {
        die("Invalid package.");
    }

    $total = $priceData['base_price'];

    /* 🔥 ADD SERVICE PRICE */
    foreach ($services as $sid) {
        $q = mysqli_query($conn, "SELECT price FROM vendor_services WHERE id='$sid'");
        $d = mysqli_fetch_assoc($q);
        if ($d) {
            $total += $d['price'];
        }
    }

    /* 🔥 INSERT BOOKING */
    mysqli_query($conn, "INSERT INTO bookings 
    (user_id, package_id, event_date, total_price, status, company_id)
    VALUES ('$user_id','$package_id','$date','$total','Pending','$company_id')");

    $booking_id = mysqli_insert_id($conn);

    /* 🔥 INSERT SELECTED SERVICES */
    foreach ($services as $sid) {
        mysqli_query($conn, "INSERT INTO booking_addons (booking_id, vendor_service_id)
        VALUES ('$booking_id','$sid')");
    }

    header("Location: dashboard.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Booking</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

<style>
body {
    font-family:'Poppins', sans-serif;
    background:#f5f6fa;
}

.container {
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box {
    background:white;
    padding:30px;
    border-radius:12px;
    width:420px;
    box-shadow:0 6px 20px rgba(0,0,0,0.1);
}

h2 { margin-bottom:20px; }

select, input {
    width:100%;
    padding:10px;
    margin-bottom:15px;
    border-radius:6px;
    border:1px solid #ccc;
}

.section-title {
    margin-top:10px;
    margin-bottom:10px;
    font-weight:600;
}

/* SERVICE CARD */
.service-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px;
    border:1px solid #eee;
    border-radius:10px;
    margin-bottom:10px;
    background:#fafafa;
    cursor:pointer;
    transition:0.2s;
}

.service-item:hover {
    background:#f0f5ff;
}

.service-text {
    display:flex;
    flex-direction:column;
}

.service-name { font-weight:500; }
.service-price { font-size:14px; color:#666; }

.service-item input {
    width:18px;
    height:18px;
}

.total {
    font-weight:600;
    margin:10px 0;
}

button {
    width:100%;
    padding:12px;
    background:#1677ff;
    color:white;
    border:none;
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container">
<div class="box">

<h2>Book Event</h2>

<form method="POST">

<select id="category">
<option value="">Select Category</option>
<?php while($c=mysqli_fetch_assoc($catResult)){ ?>
<option value="<?=$c['id']?>"><?=$c['name']?></option>
<?php } ?>
</select>

<select name="package_id" id="package" required>
<option value="">Select Package</option>
<?php while($p=mysqli_fetch_assoc($packageResult)){ ?>
<option value="<?=$p['id']?>"
        data-cat="<?=$p['event_category_id']?>"
        data-price="<?=$p['base_price']?>">
<?=$p['package_name']?> (RM <?=$p['base_price']?>)
</option>
<?php } ?>
</select>

<h4 class="section-title">Vendor Services</h4>

<?php while($s=mysqli_fetch_assoc($serviceResult)){ ?>
<label class="service-item">
    <div class="service-text">
        <div class="service-name"><?=$s['service_name']?></div>
        <div class="service-price">RM <?=$s['price']?></div>
    </div>
    <input type="checkbox" name="services[]" value="<?=$s['id']?>" data-price="<?=$s['price']?>">
</label>
<?php } ?>

<input type="date" name="event_date" required>

<div class="total">
Total: RM <span id="total">0</span>
</div>

<button type="submit">Submit Booking</button>

</form>

</div>
</div>

<script>
document.getElementById('category').addEventListener('change', function(){
    let cat = this.value;
    document.querySelectorAll('#package option').forEach(o=>{
        if(o.value==="" || o.dataset.cat===cat){
            o.style.display='block';
        } else {
            o.style.display='none';
        }
    });
});

function calc(){
    let total = 0;

    let pkg = document.querySelector('#package option:checked');
    if(pkg && pkg.dataset.price){
        total += parseFloat(pkg.dataset.price);
    }

    document.querySelectorAll('input[name="services[]"]:checked').forEach(s=>{
        total += parseFloat(s.dataset.price);
    });

    document.getElementById('total').innerText = total;
}

document.getElementById('package').addEventListener('change', calc);
document.querySelectorAll('input[name="services[]"]').forEach(el=>{
    el.addEventListener('change', calc);
});
</script>

</body>
</html>