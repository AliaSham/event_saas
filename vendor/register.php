<?php
session_start();
include('../config/db.php');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($name == "" || $email == "" || $password == "" || $confirm_password == "") {
        $error = "All fields are required.";
    }
    elseif ($password != $confirm_password) {
        $error = "Password and confirm password do not match.";
    }
    else {

        $check = mysqli_query($conn, "
            SELECT * FROM users 
            WHERE email='$email'
        ");

        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } 
        else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insertUser = mysqli_query($conn, "
                INSERT INTO users 
                (name, email, password, role, company_id, approval_status)
                VALUES 
                ('$name', '$email', '$hashed_password', 'vendor', NULL, 'pending')
            ");

            if ($insertUser) {
                $success = "Vendor account registered successfully. Please wait for approval.";
            } 
            else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20,184,166,0.25), transparent 35%),
                radial-gradient(circle at bottom right, rgba(37,99,235,0.25), transparent 35%),
                #f4f7fb;
            color: #0f172a;
        }

        .register-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
        }

        .register-left {
            padding: 55px 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 50px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: linear-gradient(135deg, #0f766e, #14b8a6, #2563eb);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(37,99,235,0.25);
        }

        .brand h3 {
            margin: 0;
            font-size: 23px;
        }

        .headline {
            max-width: 650px;
        }

        .headline h1 {
            margin: 0;
            font-size: 48px;
            line-height: 1.15;
            font-weight: 800;
            color: #0f172a;
        }

        .headline span {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .headline p {
            margin-top: 20px;
            font-size: 15px;
            color: #64748b;
            line-height: 1.8;
        }

        .feature-grid {
            margin-top: 35px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 650px;
        }

        .feature-card {
            background: rgba(255,255,255,0.72);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
            backdrop-filter: blur(10px);
        }

        .feature-card h4 {
            margin: 0 0 6px;
            font-size: 14px;
        }

        .feature-card p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        .register-right {
            padding: 45px 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-card {
            width: 100%;
            max-width: 470px;
            background: white;
            border-radius: 30px;
            padding: 38px;
            box-shadow: 0 22px 60px rgba(15,23,42,0.12);
            border: 1px solid #e5e7eb;
        }

        .form-card h2 {
            margin: 0;
            font-size: 30px;
            font-weight: 800;
        }

        .form-card .subtitle {
            margin: 8px 0 28px;
            color: #64748b;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 15px;
            border: 1px solid #cbd5e1;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            transition: .25s;
            background: #fff;
        }

        .input-group input:focus {
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20,184,166,0.12);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 13px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .register-btn {
            width: 100%;
            border: none;
            background: linear-gradient(135deg, #0f766e, #2563eb);
            color: white;
            padding: 15px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: .25s;
            margin-top: 8px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(37,99,235,0.25);
        }

        .auth-link {
            text-align: center;
            margin: 18px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        .auth-link a {
            color: #0f766e;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-link a:hover {
            text-decoration: underline;
        }

        .approval-note {
            margin-top: 22px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            padding: 15px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        @media(max-width: 950px) {
            .register-page {
                grid-template-columns: 1fr;
            }

            .register-left {
                padding: 35px 28px 10px;
            }

            .register-right {
                padding: 25px 28px 40px;
            }

            .headline h1 {
                font-size: 34px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="register-page">

    <div class="register-left">

        <div class="brand">
            <div class="brand-icon">E</div>
            <h3>Event Booking</h3>
        </div>

        <div class="headline">
            <h1>Grow your event service business with <span>premium bookings.</span></h1>
            <p>
                Register as a vendor and showcase your service to customers looking for event packages,
                bookings, vendors, and professional event support.
            </p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <h4>📋 Manage Bookings</h4>
                <p>View customer jobs and event requests from your vendor dashboard.</p>
            </div>

            <div class="feature-card">
                <h4>📅 Control Availability</h4>
                <p>Close unavailable dates and manage your calendar professionally.</p>
            </div>

            <div class="feature-card">
                <h4>🎉 Showcase Services</h4>
                <p>Upload your portfolio, pricing, service details and equipment list.</p>
            </div>

            <div class="feature-card">
                <h4>✅ Admin Approval</h4>
                <p>Your account will be reviewed first before it becomes active.</p>
            </div>
        </div>

    </div>

    <div class="register-right">

        <div class="form-card">

            <h2>Vendor Register</h2>
            <p class="subtitle">Create your vendor account and wait for admin approval.</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= $success ?></div>
            <?php } ?>

            <form method="POST">

                <div class="input-group">
                    <label>Vendor / Business Name</label>
                    <input type="text" 
                           name="name" 
                           placeholder="Example: Siti Dessert & Pastry Tier"
                           required>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" 
                           name="email" 
                           placeholder="Example: vendor@gmail.com"
                           required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" 
                           name="password" 
                           placeholder="Create password"
                           required>
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" 
                           name="confirm_password" 
                           placeholder="Confirm your password"
                           required>
                </div>

                <button class="register-btn" type="submit">
                    Create Vendor Account
                </button>

            </form>

            <div class="approval-note">
                After registration, your account status will be set as <b>Pending</b>.
                Admin must approve your account before you can log in as an active vendor.
            </div>

            <p class="auth-link">
                Already have an account? 
                <a href="../auth/login.php">Login here</a>
            </p>

            <p class="auth-link">
                Register as customer?
                <a href="../auth/register.php">Customer Register</a>
            </p>

        </div>

    </div>

</div>

</body>
</html>