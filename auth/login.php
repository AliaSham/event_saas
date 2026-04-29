<?php
session_start();
include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password == $user['password']) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['company_id'] = $user['company_id'];

        // SaaS check untuk user yang ada company_id
        if ($user['company_id'] != NULL) {
            $company_id = $user['company_id'];

            $company_query = mysqli_query($conn, 
                "SELECT subscription_status FROM companies WHERE id='$company_id'"
            );

            $company = mysqli_fetch_assoc($company_query);

            if (!$company || $company['subscription_status'] != 'Active') {
                echo "Subscription inactive";
                exit;
            }
        }

        // ROLE-BASED REDIRECT
        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;

        } elseif ($user['role'] == 'customer') {
            header("Location: ../customer/dashboard.php");
            exit;

        } elseif ($user['role'] == 'vendor') {
            header("Location: ../vendor/dashboard.php");
            exit;

        } else {
            echo "Invalid role";
        }

    } else {
        echo "Invalid email or password";
    }
}
?>

<h2>Login</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit">Login</button>
</form>

<br>

<p>Don't have an account?</p>
<a href="register.php">Register here</a>