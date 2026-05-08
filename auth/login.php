<?php
session_start();
include('../config/db.php');

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        /* CHECK VENDOR APPROVAL FIRST */
        if ($user['role'] == 'vendor' && $user['approval_status'] != 'active') {
            $error = "Your vendor account is still pending approval.";
        } 
        else {

            /* SaaS check only for company/admin users */
            if ($user['company_id'] != NULL) {
                $company_id = $user['company_id'];

                $company_query = mysqli_query($conn, 
                    "SELECT subscription_status FROM companies WHERE id='$company_id'"
                );

                $company = mysqli_fetch_assoc($company_query);

                if (!$company || $company['subscription_status'] != 'Active') {
                    $error = "Subscription inactive";
                }
            }

            /* SET SESSION ONLY IF NO ERROR */
            if ($error == "") {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];

                if ($user['role'] == 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit;
                } 
                elseif ($user['role'] == 'customer') {
                    header("Location: ../customer/dashboard.php");
                    exit;
                } 
                elseif ($user['role'] == 'vendor') {
                    header("Location: ../vendor/dashboard.php");
                    exit;
                } 
                else {
                    $error = "Invalid role";
                }
            }
        }

    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="overlay"></div>

    <!-- KEEP YOUR ANIMATION -->
    <h1 class="hello-text">hello</h1>

    <div class="auth-card">

        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-sub">Login to your account</p>

        <?php if ($error != "") { ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">

            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>

            <button class="btn btn-primary">Login</button>

        </form>

        <p class="auth-link">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>

    </div>

</div>

</body>
</html>