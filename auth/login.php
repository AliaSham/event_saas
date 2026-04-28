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

        // SAVE SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['company_id'] = $user['company_id'];

        // ROLE-BASED REDIRECT
        if ($user['role'] == 'admin') {

            // SaaS check (IMPORTANT)
            if ($user['subscription_status'] != 'Active') {
                echo "Subscription inactive";
                exit;
            }

            header("Location: ../admin/dashboard.php");
            exit;

        } else {
            echo "Only admin supported for now";
        }

    } else {
        echo "Invalid login";
    }
}
?>

<h2>Login</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>