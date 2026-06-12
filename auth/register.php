<?php
include "../config/db.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $role = "customer";
    $approval_status = "active";

    $check = mysqli_query($conn, "
        SELECT * FROM users 
        WHERE email='$email'
    ");

    if (mysqli_num_rows($check) > 0) {
        $error = "Email already exists!";
    } else {

        $query = "
            INSERT INTO users 
            (name, email, password, role, company_id, approval_status)
            VALUES 
            ('$name', '$email', '$password', '$role', NULL, '$approval_status')
        ";

        if (mysqli_query($conn, $query)) {
            $success = "Customer account registered successfully. You can login now.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="overlay"></div>

    <div class="auth-left">

        <div class="brand-area">

            <img src="/event_saas/assets/images/logo.png"
                 alt="Event SaaS Logo"
                 class="brand-logo">

            <div class="brand-text-register">

                <h2 class="page-heading">
                    Plan Your Perfect Event
                </h2>

            </div>

        </div>

        <h3>Book Events Effortlessly</h3>

        <p>
            Explore event packages, compare vendors,
            and manage all your bookings from a single platform.
        </p>

        <ul>
            <li>✓ Browse Event Companies</li>
            <li>✓ Book Packages Online</li>
            <li>✓ Manage Bookings</li>
            <li>✓ Track Booking Status</li>
        </ul>

    </div>

    <div class="auth-card">

        <h2 class="auth-title">Customer Register</h2>
        <p class="auth-sub">Create your customer account</p>

        <?php if ($error != "") { ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php } ?>

        <?php if ($success != "") { ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST">

            <input type="text" 
                   name="name" 
                   placeholder="Enter Name" 
                   required>

            <input type="email" 
                   name="email" 
                   placeholder="Enter Email" 
                   required>

            <input type="password" 
                   name="password" 
                   placeholder="Enter Password" 
                   required>

            <!-- ======================================================== -->
            <!-- TAMBAHAN: Kotak Penanda Syarat & Privasi Moden           -->
            <!-- ======================================================== -->
            <div class="terms-checkbox-container" style="margin: 15px 0 20px 0; text-align: left;">
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; color: #cbd5e1; line-height: 1.5;">
                    <!-- Atribut 'required' memaksa user untuk menanda kotak ini -->
                    <input type="checkbox" name="agree_terms" required style="margin-top: 3px; cursor: pointer; transform: scale(1.1);">
                    <span>
                        I agree to the 
                        <a href="terms.php?from=customer" target="_blank" style="color: #a78bfa; text-decoration: none; font-weight: 600;">Terms of Service</a> 
                        and 
                        <a href="privacy.php?from=customer" target="_blank" style="color: #a78bfa; text-decoration: none; font-weight: 600;">Privacy Policy</a>.
                    </span>
                </label>
            </div>
            <!-- ======================================================== -->

            <button class="btn btn-primary" name="register">
                Register
            </button>

        </form>

        <p class="auth-link">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

        <p class="auth-link">
            Register as vendor?
            <a href="vendor_register.php">Vendor Register</a>
        </p>

    </div>

</div>
<?php include('footer_auth.php'); ?>

</body>
</html>