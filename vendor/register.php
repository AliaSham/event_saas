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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>Vendor Register</h2>
            <p class="muted">Create your vendor account</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= $success ?></div>
            <?php } ?>

            <form method="POST">

                <input type="text" 
                       name="name" 
                       placeholder="Vendor Name"
                       required>

                <input type="email" 
                       name="email" 
                       placeholder="Email Address"
                       required>

                <input type="password" 
                       name="password" 
                       placeholder="Password"
                       required>

                <input type="password" 
                       name="confirm_password" 
                       placeholder="Confirm Password"
                       required>

                <button class="btn btn-primary">
                    Register
                </button>

            </form>

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